<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer; // PHPMailer|null
    private $config;
    private $transport;

    public function __construct() {
        $config = require __DIR__ . '/../config/mail.php';
        $this->config = $config;
        $this->transport = $config['transport'] ?? 'smtp';
        $this->logInfo('Mailer init. Transport: ' . $this->transport);

        if ($this->transport === 'smtp') {
            if (!class_exists(PHPMailer::class)) {
                $autoloadPath = __DIR__ . '/../vendor/autoload.php';
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                }
            }
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = $config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['username'];
            $this->mailer->Password = $config['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $config['port'];

            // Opciones SSL para entornos Windows/XAMPP con problemas de CA
            $options = [
                'ssl' => [
                    'verify_peer' => $config['verify_peer'] ?? true,
                    'verify_peer_name' => $config['verify_peer_name'] ?? true,
                    'allow_self_signed' => $config['allow_self_signed'] ?? false,
                ],
            ];
            if (!empty($config['cafile'])) {
                $options['ssl']['cafile'] = $config['cafile'];
            }
            $this->mailer->SMTPOptions = $options;

            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom($config['from_email'], $config['from_name']);

            // Opcional: activar depuración SMTP
            if (!empty($config['debug'])) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER; // salida detallada
                $this->mailer->Debugoutput = function($str, $level) use ($config) {
                    $dir = $config['log_dir'] ?? sys_get_temp_dir();
                    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                    $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'smtp_' . date('Y-m-d') . '.log';
                    file_put_contents($file, '[' . date('H:i:s') . "] ($level) $str\n", FILE_APPEND);
                };
            }
        } else {
            $this->mailer = null; // API
            if (!function_exists('curl_init')) {
                $this->logError('cURL no está habilitado en PHP. Cambiando a SMTP.');
                $this->transport = 'smtp';
                // Reintentar configurar SMTP rápidamente
                if (!class_exists(PHPMailer::class)) {
                    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
                    if (file_exists($autoloadPath)) { require_once $autoloadPath; }
                }
                $this->mailer = new PHPMailer(true);
                $this->mailer->isSMTP();
                $this->mailer->Host = $config['host'];
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $config['username'];
                $this->mailer->Password = $config['password'];
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mailer->Port = $config['port'];
                $this->mailer->CharSet = 'UTF-8';
                $this->mailer->setFrom($config['from_email'], $config['from_name']);
            }
        }
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        if ($this->transport === 'smtp') {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($toEmail, $toName);
                $this->mailer->isHTML(true);
                $this->mailer->Subject = $subject;
                $this->mailer->Body = $htmlBody;
                $this->mailer->AltBody = $textBody ?: strip_tags($htmlBody);
                $result = $this->mailer->send();
                $this->logInfo('SMTP send result: ' . ($result ? 'OK' : 'FAIL'));
                return $result;
            } catch (Exception $e) {
                $this->logError('Mailer error: ' . $e->getMessage());
                return false;
            }
        }
        $ok = $this->sendViaSendGridApi($toEmail, $toName, $subject, $htmlBody, $textBody);
        $this->logInfo('SendGrid API send result: ' . ($ok ? 'OK' : 'FAIL'));
        return $ok;
    }

    private function sendViaSendGridApi(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        $apiKey = $this->config['sendgrid_api_key'] ?? ($this->config['password'] ?? '');
        if (empty($apiKey)) {
            $this->logError('SendGrid API key no configurada.');
            return false;
        }

        $payload = [
            'personalizations' => [[
                'to' => [[ 'email' => $toEmail, 'name' => $toName ]],
                'subject' => $subject,
            ]],
            'from' => [ 'email' => $this->config['from_email'], 'name' => $this->config['from_name'] ],
            'content' => [
                [ 'type' => 'text/plain', 'value' => $textBody ?: strip_tags($htmlBody) ],
                [ 'type' => 'text/html', 'value' => $htmlBody ],
            ],
        ];

        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 202) {
            return true;
        }
        $this->logError('SendGrid API error: HTTP ' . $httpCode . ' - ' . ($responseBody ?: $curlErr));
        return false;
    }

    private function logError(string $message): void {
        error_log($message);
        $dir = $this->config['log_dir'] ?? sys_get_temp_dir();
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mail_' . date('Y-m-d') . '.log';
        file_put_contents($file, '[' . date('H:i:s') . "] $message\n", FILE_APPEND);
    }

    private function logInfo(string $message): void {
        $dir = $this->config['log_dir'] ?? sys_get_temp_dir();
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mail_' . date('Y-m-d') . '.log';
        file_put_contents($file, '[' . date('H:i:s') . "] $message\n", FILE_APPEND);
    }
}


