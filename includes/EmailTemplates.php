<?php
/**
 * Manejador de Templates de Email Mejorados para TechonWay
 * 
 * Esta clase maneja la generación de emails con diseño profesional
 * usando templates HTML responsive
 */

class EmailTemplates {
    private $templatesPath;
    private $defaultVars;

    public function __construct() {
        $this->templatesPath = __DIR__ . '/../templates/emails/';
        $this->setDefaultVars();
    }

    /**
     * Establece variables por defecto para todos los templates
     */
    private function setDefaultVars() {
        $this->defaultVars = [
            'CONTACT_EMAIL' => 'info@techonway.com',
            'CONTACT_PHONE' => '+54 11 1234-5678',
            'ADMIN_URL' => $this->getBaseUrl() . 'admin/dashboard.php',
            'CALENDAR_URL' => $this->getBaseUrl() . 'admin/calendar.php',
            'CURRENT_YEAR' => date('Y'),
            'COMPANY_NAME' => 'TechonWay'
        ];
    }

    /**
     * Obtiene la URL base del sistema
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'demo.techonway.com';
        return $protocol . '://' . $host . '/';
    }

    /**
     * Genera email de cita programada para cliente
     */
    public function generateAppointmentEmail($client, $ticket, $technician, $isReschedule = false) {
        $templateFile = $this->templatesPath . 'appointment.html';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template de cita no encontrado: $templateFile");
        }

        $template = file_get_contents($templateFile);

        // Determinar el tipo de mensaje
        $headerSubtitle = $isReschedule ? 'Cita Reprogramada' : 'Cita de Mantenimiento Programada';
        $introMessage = $isReschedule 
            ? 'Le informamos que su cita de mantenimiento ha sido <strong>reprogramada</strong>. A continuación encontrará los nuevos detalles:'
            : 'Le informamos que se ha programado una visita técnica para el mantenimiento en su propiedad.';

        // Formatear fecha y hora
        $appointmentDate = $this->formatDate($ticket['scheduled_date']);
        $appointmentTime = $this->formatTime($ticket['scheduled_time']);

        // Generar sección de código de seguridad
        $securitySection = '';
        $securityInstruction = 'Por favor, esté disponible en el horario programado';
        
        if (!empty($ticket['security_code'])) {
            $securitySection = '
            <div class="security-card">
                <h3>🔒 Código de Seguridad</h3>
                <p>Para su seguridad, el técnico le proporcionará el siguiente código al llegar:</p>
                <div class="security-code">
                    <p class="code-number">' . htmlspecialchars($ticket['security_code']) . '</p>
                </div>
                <p class="security-note">Solicite este código al técnico antes de permitir el acceso a su propiedad.</p>
            </div>';
            
            $securityInstruction = 'Verifique el código de seguridad antes de permitir el acceso';
        }

        // Variables para reemplazar
        $variables = array_merge($this->defaultVars, [
            'HEADER_SUBTITLE' => $headerSubtitle,
            'CLIENT_NAME' => htmlspecialchars($client['name']),
            'INTRO_MESSAGE' => $introMessage,
            'APPOINTMENT_DATE' => $appointmentDate,
            'APPOINTMENT_TIME' => $appointmentTime,
            'TECHNICIAN_NAME' => htmlspecialchars($technician['name'] ?? 'Por asignar'),
            'CLIENT_ADDRESS' => htmlspecialchars($client['address'] ?? ''),
            'WORK_DESCRIPTION' => nl2br(htmlspecialchars($ticket['description'])),
            'SECURITY_CODE_SECTION' => $securitySection,
            'SECURITY_INSTRUCTION' => $securityInstruction
        ]);

        return $this->replaceVariables($template, $variables);
    }

    /**
     * Genera email de solicitud de servicio para admin
     */
    public function generateServiceRequestEmail($name, $phone, $address, $detail) {
        $templateFile = $this->templatesPath . 'service_request.html';
        
        if (!file_exists($templateFile)) {
            throw new Exception("Template de solicitud no encontrado: $templateFile");
        }

        $template = file_get_contents($templateFile);

        // Variables para reemplazar
        $variables = array_merge($this->defaultVars, [
            'CLIENT_NAME' => htmlspecialchars($name),
            'CLIENT_PHONE' => htmlspecialchars($phone),
            'CLIENT_ADDRESS' => htmlspecialchars($address),
            'REQUEST_DETAILS' => nl2br(htmlspecialchars($detail ?: 'Sin detalles específicos proporcionados.')),
            'TIMESTAMP' => $this->formatDateTime(date('Y-m-d H:i:s'))
        ]);

        return $this->replaceVariables($template, $variables);
    }

    /**
     * Genera versión de texto plano de email de cita
     */
    public function generateAppointmentTextEmail($client, $ticket, $technician, $isReschedule = false) {
        $type = $isReschedule ? 'REPROGRAMADA' : 'PROGRAMADA';
        $intro = $isReschedule 
            ? 'Su cita de mantenimiento ha sido REPROGRAMADA.'
            : 'Se ha programado una visita técnica para el mantenimiento en su propiedad.';

        $appointmentDate = $this->formatDate($ticket['scheduled_date']);
        $appointmentTime = $this->formatTime($ticket['scheduled_time']);

        $securitySection = '';
        if (!empty($ticket['security_code'])) {
            $securitySection = "\n\nCÓDIGO DE SEGURIDAD: " . $ticket['security_code'] . 
                              "\nSolicite este código al técnico antes de permitir el acceso a su propiedad.";
        }

        return "
TECHONWAY - CITA DE MANTENIMIENTO $type

Estimado/a {$client['name']},

$intro

DETALLES DE LA CITA:
- Fecha: $appointmentDate
- Hora: $appointmentTime
- Técnico asignado: {$technician['name']}
- Dirección: {$client['address']}
$securitySection

TRABAJO A REALIZAR:
{$ticket['description']}

INSTRUCCIONES IMPORTANTES:
- El técnico llegará en el horario programado
- Verifique el código de seguridad antes de permitir el acceso (si aplica)
- Si necesita reprogramar, contacte con nosotros con anticipación
- Mantenga despejada el área de trabajo
- Tenga a mano la información de acceso si es necesaria

Para consultas: {$this->defaultVars['CONTACT_EMAIL']} | Tel: {$this->defaultVars['CONTACT_PHONE']}

Este email fue generado automáticamente por el sistema TechonWay.
        ";
    }

    /**
     * Genera versión de texto plano de solicitud de servicio
     */
    public function generateServiceRequestTextEmail($name, $phone, $address, $detail) {
        $timestamp = $this->formatDateTime(date('Y-m-d H:i:s'));

        return "
TECHONWAY - NUEVA SOLICITUD DE SERVICIO

🚨 NUEVA SOLICITUD RECIBIDA

INFORMACIÓN DEL CLIENTE:
- Nombre: $name
- Teléfono: $phone
- Dirección: $address

DETALLE DE LA SOLICITUD:
$detail

RECIBIDO EL: $timestamp

PRÓXIMOS PASOS:
1. Contactar al cliente para confirmar los detalles
2. Crear un ticket en el sistema
3. Asignar un técnico disponible
4. Programar la cita en el calendario
5. Enviar confirmación al cliente

Panel Admin: {$this->defaultVars['ADMIN_URL']}
        ";
    }

    /**
     * Reemplaza variables en el template
     */
    private function replaceVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Formatea la fecha para mostrar
     */
    private function formatDate($date) {
        try {
            $dateObj = new DateTime($date);
            $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $months = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            
            $dayName = $days[$dateObj->format('w')];
            $day = $dateObj->format('j');
            $monthName = $months[$dateObj->format('n') - 1];
            $year = $dateObj->format('Y');
            
            return "$dayName, $day de $monthName de $year";
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Formatea la hora para mostrar
     */
    private function formatTime($time) {
        try {
            $timeObj = DateTime::createFromFormat('H:i:s', $time);
            if (!$timeObj) {
                $timeObj = DateTime::createFromFormat('H:i', $time);
            }
            return $timeObj ? $timeObj->format('H:i') : $time;
        } catch (Exception $e) {
            return $time;
        }
    }

    /**
     * Formatea fecha y hora completa
     */
    private function formatDateTime($datetime) {
        try {
            $dateObj = new DateTime($datetime);
            return $dateObj->format('d/m/Y H:i');
        } catch (Exception $e) {
            return $datetime;
        }
    }

    /**
     * Obtiene el asunto del email según el tipo
     */
    public function getSubject($type, $isReschedule = false) {
        switch ($type) {
            case 'appointment':
                return $isReschedule 
                    ? '🔄 Cita Reprogramada - TechonWay'
                    : '📅 Cita de Mantenimiento Programada - TechonWay';
                    
            case 'service_request':
                return '🚨 Nueva Solicitud de Servicio - TechonWay';
                
            default:
                return 'Notificación - TechonWay';
        }
    }
}
