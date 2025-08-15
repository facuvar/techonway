<?php
/**
 * SMS Notifier Class (Twilio)
 * Envío de SMS como respaldo cuando la notificación de WhatsApp falla
 */
class SmsNotifier {
    private $provider;
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $countryCode;
    private $baseUrl;
    private $debugMode;

    public function __construct($debugMode = false) {
        $configFile = __DIR__ . '/../config/sms.php';
        if (!file_exists($configFile)) {
            throw new Exception("Archivo de configuración de SMS no encontrado: {$configFile}");
        }

        $config = require $configFile;
        if (!is_array($config)) {
            throw new Exception("El archivo de configuración de SMS no devolvió un array válido");
        }

        $this->provider = $config['provider'] ?? 'twilio';
        $this->accountSid = $config['account_sid'] ?? '';
        $this->authToken = $config['auth_token'] ?? '';
        $this->fromNumber = $config['from_number'] ?? '';
        $this->countryCode = $config['country_code'] ?? '54';
        $this->baseUrl = $config['base_url'] ?? '';
        $this->debugMode = $debugMode;

        if (empty($this->accountSid) || empty($this->authToken) || empty($this->fromNumber)) {
            $this->logError('Configuración de SMS incompleta: SID, token o from_number vacío');
        }
    }

    /**
     * Envía SMS de notificación de nuevo ticket al técnico
     */
    public function sendTicketNotification($technician, $ticket, $client) {
        if (empty($technician['phone'])) {
            $this->logError("No se puede enviar SMS: el técnico {$technician['name']} (ID: {$technician['id']}) no tiene teléfono");
            return false;
        }

        $to = $this->formatPhoneE164($technician['phone']);
        $this->logInfo("Enviando SMS a técnico: {$technician['name']} (ID: {$technician['id']}) - {$to}");

        $desc = isset($ticket['description']) ? trim($ticket['description']) : '';
        if (strlen($desc) > 80) {
            $desc = substr($desc, 0, 77) . '...';
        }
        $clientName = $client['name'] ?? 'Cliente';
        $ticketId = $ticket['id'] ?? '';
        
        $body = "Nuevo ticket #{$ticketId} asignado. Cliente: {$clientName}. {$desc}";
        
        // Agregar información de cita programada si existe
        if (!empty($ticket['scheduled_date']) && !empty($ticket['scheduled_time'])) {
            $appointmentDate = date('d/m/Y', strtotime($ticket['scheduled_date']));
            $appointmentTime = date('H:i', strtotime($ticket['scheduled_time']));
            $body .= " CITA: {$appointmentDate} {$appointmentTime}";
            
            if (!empty($ticket['security_code'])) {
                $body .= " Codigo: {$ticket['security_code']}";
            }
        }
        
        $url = $this->baseUrl ? rtrim($this->baseUrl, '/') . '/admin/tickets.php?action=view&id=' . $ticketId : '';
        if ($url) {
            $body .= " Ver: {$url}";
        }

        return $this->sendTextMessage($to, $body);
    }

    /**
     * Envía un SMS de texto simple usando Twilio
     */
    public function sendTextMessage($to, $body) {
        if ($this->provider !== 'twilio') {
            $this->logError('Proveedor SMS no soportado: ' . $this->provider);
            return false;
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->accountSid . '/Messages.json';

        $postFields = http_build_query([
            'From' => $this->fromNumber,
            'To' => $to,
            'Body' => $body
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);
        if (!$success) {
            $this->logError("Error al enviar SMS (HTTP {$httpCode}): {$response}");
            if (!empty($curlError)) {
                $this->logError("cURL: {$curlError}");
            }
        } else {
            $this->logInfo('SMS enviado correctamente. Respuesta: ' . $response);
        }
        return $success;
    }

    private function formatPhoneE164($phone) {
        // Similar al formateo de WhatsApp pero asegurando prefijo +
        $original = $phone;
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
        }
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        if (substr($phone, 0, 2) === '15') {
            $phone = substr($phone, 2);
        }
        if (substr($phone, 0, 3) !== '549') {
            if (substr($phone, 0, 2) === '54') {
                if (substr($phone, 2, 1) !== '9') {
                    $phone = '549' . substr($phone, 2);
                }
            } else {
                // Prepend country code
                $phone = '549' . $phone;
            }
        }
        $e164 = '+' . $phone;
        $this->logInfo("Formateado a E.164 SMS: '{$original}' -> '{$e164}'");
        return $e164;
    }

    private function writeToLogFile($logFile, $message) {
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        if (substr($message, -1) !== "\n") {
            $message .= "\n";
        }
        file_put_contents($logFile, $message, FILE_APPEND);
    }

    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] ERROR: {$message}";
        error_log($logMessage);
        $logFile = dirname(__DIR__) . '/logs/sms_' . date('Y-m-d') . '.log';
        $this->writeToLogFile($logFile, $logMessage);
        if ($this->debugMode) {
            echo $logMessage . "<br>";
        }
    }

    private function logInfo($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] INFO: {$message}";
        error_log($logMessage);
        $logFile = dirname(__DIR__) . '/logs/sms_' . date('Y-m-d') . '.log';
        $this->writeToLogFile($logFile, $logMessage);
        if ($this->debugMode) {
            echo $logMessage . "<br>";
        }
    }
}




