<?php
/**
 * Notificador de Email para citas programadas
 * 
 * Esta clase maneja el envío de emails a clientes con información
 * sobre citas programadas y códigos de seguridad
 */

// Verificar si PHPMailer está disponible
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotifier {
    private $config;
    private $debugMode;
    
    public function __construct($debugMode = false) {
        $this->debugMode = $debugMode;
        $this->loadConfig();
    }
    
    /**
     * Carga la configuración de email
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/../config/email.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Configuración por defecto usando variables de entorno o valores locales
            $this->config = [
                'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
                'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
                'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
                'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
                'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',
                'from_email' => $_ENV['FROM_EMAIL'] ?? 'no-reply@techonway.com',
                'from_name' => $_ENV['FROM_NAME'] ?? 'TechonWay',
                'reply_to' => $_ENV['REPLY_TO_EMAIL'] ?? 'info@techonway.com'
            ];
        }
    }
    
    /**
     * Envía email de notificación de cita programada al cliente
     * 
     * @param array $client Datos del cliente
     * @param array $ticket Datos del ticket
     * @param array $technician Datos del técnico
     * @return bool Éxito o fallo del envío
     */
    public function sendAppointmentNotification($client, $ticket, $technician) {
        if (empty($client['email'])) {
            $this->logError("No se puede enviar email: el cliente {$client['name']} no tiene email");
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor
            if ($this->debugMode) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = !empty($this->config['smtp_username']);
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_secure'];
            $mail->Port = $this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Destinatarios
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($client['email'], $client['name']);
            $mail->addReplyTo($this->config['reply_to'], $this->config['from_name']);
            
            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Cita de Mantenimiento Programada - TechonWay';
            
            // Formatear fecha y hora
            $appointmentDate = $this->formatDate($ticket['scheduled_date']);
            $appointmentTime = $this->formatTime($ticket['scheduled_time']);
            
            $mail->Body = $this->generateEmailTemplate($client, $ticket, $technician, $appointmentDate, $appointmentTime);
            $mail->AltBody = $this->generatePlainTextEmail($client, $ticket, $technician, $appointmentDate, $appointmentTime);
            
            $mail->send();
            $this->logInfo("Email enviado correctamente a {$client['email']} para ticket #{$ticket['id']}");
            return true;
            
        } catch (Exception $e) {
            $this->logError("Error enviando email: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Genera el template HTML del email
     */
    private function generateEmailTemplate($client, $ticket, $technician, $date, $time) {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Cita de Mantenimiento Programada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 20px; margin: 20px 0; }
                .security-code { background: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
                .code-number { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; }
                .footer { text-align: center; color: #6c757d; margin-top: 30px; font-size: 12px; }
                .highlight { color: #007bff; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔧 TechonWay</h1>
                    <h2>Cita de Mantenimiento Programada</h2>
                </div>
                
                <div class='content'>
                    <p>Estimado/a <strong>{$client['name']}</strong>,</p>
                    
                    <p>Le informamos que se ha programado una visita técnica para el mantenimiento en su propiedad.</p>
                    
                    <div class='info-box'>
                        <h3>📅 Detalles de la Cita</h3>
                        <p><strong>Fecha:</strong> <span class='highlight'>{$date}</span></p>
                        <p><strong>Hora:</strong> <span class='highlight'>{$time}</span></p>
                        <p><strong>Técnico asignado:</strong> {$technician['name']}</p>
                        <p><strong>Dirección:</strong> {$client['address']}</p>
                    </div>
                    
                    <div class='security-code'>
                        <h3>🔒 Código de Seguridad</h3>
                        <p>Para su seguridad, el técnico le proporcionará el siguiente código al llegar:</p>
                        <div class='code-number'>{$ticket['security_code']}</div>
                        <p><em>Solicite este código al técnico antes de permitir el acceso a su propiedad.</em></p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>📝 Trabajo a realizar</h3>
                        <p>{$ticket['description']}</p>
                    </div>
                    
                    <p><strong>Instrucciones importantes:</strong></p>
                    <ul>
                        <li>El técnico llegará en el horario programado</li>
                        <li>Verifique el código de seguridad antes de permitir el acceso</li>
                        <li>Si necesita reprogramar, contacte con nosotros con anticipación</li>
                        <li>Mantenga despejada el área de trabajo</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Este email fue generado automáticamente por el sistema TechonWay</p>
                    <p>Si tiene alguna consulta, no dude en contactarnos</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Genera la versión de texto plano del email
     */
    private function generatePlainTextEmail($client, $ticket, $technician, $date, $time) {
        return "
TECHONWAY - CITA DE MANTENIMIENTO PROGRAMADA

Estimado/a {$client['name']},

Le informamos que se ha programado una visita técnica para el mantenimiento en su propiedad.

DETALLES DE LA CITA:
- Fecha: {$date}
- Hora: {$time}
- Técnico asignado: {$technician['name']}
- Dirección: {$client['address']}

CÓDIGO DE SEGURIDAD: {$ticket['security_code']}
Solicite este código al técnico antes de permitir el acceso a su propiedad.

TRABAJO A REALIZAR:
{$ticket['description']}

INSTRUCCIONES IMPORTANTES:
- El técnico llegará en el horario programado
- Verifique el código de seguridad antes de permitir el acceso
- Si necesita reprogramar, contacte con nosotros con anticipación
- Mantenga despejada el área de trabajo

Este email fue generado automáticamente por el sistema TechonWay.
Si tiene alguna consulta, no dude en contactarnos.
        ";
    }
    
    /**
     * Formatea la fecha para mostrar
     */
    private function formatDate($date) {
        if (empty($date)) return 'No programada';
        
        $dateObj = new DateTime($date);
        $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        $dayName = $days[$dateObj->format('w')];
        $day = $dateObj->format('d');
        $month = $months[(int)$dateObj->format('m')];
        $year = $dateObj->format('Y');
        
        return "$dayName, $day de $month de $year";
    }
    
    /**
     * Formatea la hora para mostrar
     */
    private function formatTime($time) {
        if (empty($time)) return 'No programada';
        
        $timeObj = new DateTime($time);
        return $timeObj->format('H:i') . ' hs';
    }
    
    /**
     * Log de información
     */
    private function logInfo($message) {
        if ($this->debugMode) {
            error_log("[EmailNotifier INFO] $message");
        }
    }
    
    /**
     * Log de errores
     */
    private function logError($message) {
        error_log("[EmailNotifier ERROR] $message");
    }
}
