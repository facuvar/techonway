<?php
/**
 * Script de Configuración de SendGrid para TechonWay
 * 
 * Este script ayuda a configurar fácilmente SendGrid para el envío de emails
 * en el sistema TechonWay tanto en desarrollo como en producción.
 */

class SendGridSetup {
    private $configPath;
    private $envExamplePath;
    private $localExamplePath;
    
    public function __construct() {
        $this->configPath = __DIR__ . '/config/email.php';
        $this->envExamplePath = __DIR__ . '/.env.example';
        $this->localExamplePath = __DIR__ . '/config/local.example.php';
    }
    
    /**
     * Ejecuta el setup interactivo de SendGrid
     */
    public function run() {
        $this->showHeader();
        
        echo "Este script te ayudará a configurar SendGrid para TechonWay.\n\n";
        
        // Verificar si ya existe configuración
        if ($this->hasExistingConfig()) {
            echo "⚠️  Se detectó una configuración existente.\n";
            if (!$this->askConfirmation("¿Deseas continuar y sobrescribir la configuración actual?")) {
                echo "Configuración cancelada.\n";
                return;
            }
        }
        
        // Recopilar información de SendGrid
        $sendgridData = $this->collectSendGridData();
        
        // Crear archivos de configuración
        $this->createConfigFiles($sendgridData);
        
        // Ejecutar prueba de email
        if ($this->askConfirmation("¿Deseas probar la configuración de email?")) {
            $this->testEmailConfiguration($sendgridData);
        }
        
        $this->showSuccessMessage();
    }
    
    /**
     * Muestra el header del script
     */
    private function showHeader() {
        echo "\n";
        echo "=====================================\n";
        echo "🔧 CONFIGURACIÓN DE SENDGRID         \n";
        echo "   Sistema TechonWay                 \n";
        echo "=====================================\n\n";
    }
    
    /**
     * Verifica si ya existe configuración
     */
    private function hasExistingConfig() {
        return file_exists($this->configPath) && 
               (file_exists('.env') || file_exists('config/local.php'));
    }
    
    /**
     * Recopila los datos de SendGrid del usuario
     */
    private function collectSendGridData() {
        echo "📧 CONFIGURACIÓN DE SENDGRID\n";
        echo "══════════════════════════════\n\n";
        
        echo "Para obtener tu API Key de SendGrid:\n";
        echo "1. Ve a https://app.sendgrid.com/\n";
        echo "2. Inicia sesión en tu cuenta\n";
        echo "3. Ve a Settings > API Keys\n";
        echo "4. Crea una nueva API Key con permisos 'Full Access'\n\n";
        
        $data = [];
        
        // API Key
        do {
            $data['api_key'] = $this->askInput("Ingresa tu SendGrid API Key", true);
            if (empty($data['api_key'])) {
                echo "❌ La API Key es obligatoria.\n";
            }
        } while (empty($data['api_key']));
        
        // Email del remitente
        do {
            $data['from_email'] = $this->askInput("Email del remitente", false, "no-reply@techonway.com");
            if (!filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
                echo "❌ Email inválido. Inténtalo de nuevo.\n";
                $data['from_email'] = '';
            }
        } while (empty($data['from_email']));
        
        // Nombre del remitente
        $data['from_name'] = $this->askInput("Nombre del remitente", false, "TechonWay - Sistema de Gestión");
        
        // Email de respuesta
        $data['reply_to'] = $this->askInput("Email de respuesta (Reply-To)", false, $data['from_email']);
        
        // Entorno
        echo "\n🌍 ENTORNO DE CONFIGURACIÓN\n";
        echo "═══════════════════════════\n";
        echo "1. Desarrollo local (config/local.php)\n";
        echo "2. Producción (.env)\n";
        echo "3. Ambos\n";
        
        do {
            $env = $this->askInput("Selecciona el entorno (1-3)", false, "3");
            if (!in_array($env, ['1', '2', '3'])) {
                echo "❌ Opción inválida. Selecciona 1, 2 o 3.\n";
                $env = '';
            }
        } while (empty($env));
        
        $data['environment'] = $env;
        
        return $data;
    }
    
    /**
     * Crea los archivos de configuración
     */
    private function createConfigFiles($data) {
        echo "\n📝 CREANDO ARCHIVOS DE CONFIGURACIÓN...\n";
        
        // Crear/actualizar .env si es necesario
        if (in_array($data['environment'], ['2', '3'])) {
            $this->createEnvFile($data);
            echo "✅ Archivo .env creado/actualizado\n";
        }
        
        // Crear/actualizar config/local.php si es necesario
        if (in_array($data['environment'], ['1', '3'])) {
            $this->createLocalConfigFile($data);
            echo "✅ Archivo config/local.php creado/actualizado\n";
        }
        
        // Crear .env.example
        $this->createEnvExampleFile($data);
        echo "✅ Archivo .env.example creado/actualizado\n";
        
        echo "\n";
    }
    
    /**
     * Crea o actualiza el archivo .env
     */
    private function createEnvFile($data) {
        $envContent = [];
        
        // Leer .env existente si existe
        if (file_exists('.env')) {
            $envContent = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        // Variables de SendGrid a actualizar
        $sendgridVars = [
            'SENDGRID_API_KEY' => $data['api_key'],
            'SENDGRID_FROM_EMAIL' => $data['from_email'],
            'FROM_EMAIL' => $data['from_email'],
            'FROM_NAME' => $data['from_name'],
            'REPLY_TO_EMAIL' => $data['reply_to'],
            'SMTP_HOST' => 'smtp.sendgrid.net',
            'SMTP_PORT' => '587',
            'SMTP_USERNAME' => 'apikey',
            'SMTP_PASSWORD' => $data['api_key'],
            'SMTP_SECURE' => 'tls',
            'EMAIL_DEBUG' => 'false'
        ];
        
        // Actualizar variables existentes o agregar nuevas
        foreach ($sendgridVars as $key => $value) {
            $found = false;
            for ($i = 0; $i < count($envContent); $i++) {
                if (strpos($envContent[$i], $key . '=') === 0) {
                    $envContent[$i] = $key . '=' . $value;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $envContent[] = $key . '=' . $value;
            }
        }
        
        file_put_contents('.env', implode("\n", $envContent) . "\n");
    }
    
    /**
     * Crea o actualiza el archivo config/local.php
     */
    private function createLocalConfigFile($data) {
        $localConfig = "<?php
/**
 * Configuración local para desarrollo
 * Este archivo NO debe subirse a Git
 */

return [
    'email' => [
        'smtp_host' => 'smtp.sendgrid.net',
        'smtp_port' => 587,
        'smtp_username' => 'apikey',
        'smtp_password' => '{$data['api_key']}',
        'smtp_secure' => 'tls',
        'from_email' => '{$data['from_email']}',
        'from_name' => '{$data['from_name']}',
        'reply_to' => '{$data['reply_to']}',
        'debug_mode' => false
    ],
    
    // Otras configuraciones locales pueden ir aquí
    'database' => [
        // Configuración de base de datos local si es diferente
    ]
];
";
        
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        file_put_contents('config/local.php', $localConfig);
    }
    
    /**
     * Crea el archivo .env.example
     */
    private function createEnvExampleFile($data) {
        $envExample = "# Configuración de SendGrid para TechonWay
SENDGRID_API_KEY=tu_sendgrid_api_key_aqui
SENDGRID_FROM_EMAIL={$data['from_email']}

# Configuración SMTP (usa SendGrid)
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=\${SENDGRID_API_KEY}
SMTP_SECURE=tls

# Configuración de emails
FROM_EMAIL=\${SENDGRID_FROM_EMAIL}
FROM_NAME=TechonWay - Sistema de Gestión
REPLY_TO_EMAIL=\${FROM_EMAIL}
EMAIL_DEBUG=false

# Base de datos (Railway MySQL)
DB_HOST=tu_host_mysql
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASSWORD=tu_password_mysql

# Configuración general
APP_ENV=production
APP_DEBUG=false
";
        
        file_put_contents('.env.example', $envExample);
    }
    
    /**
     * Prueba la configuración de email
     */
    private function testEmailConfiguration($data) {
        echo "\n🧪 PROBANDO CONFIGURACIÓN DE EMAIL...\n";
        
        $testEmail = $this->askInput("Email para la prueba", false, $data['from_email']);
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo "❌ Email inválido. Saltando prueba.\n";
            return;
        }
        
        try {
            // Cargar la configuración actualizada
            require_once 'includes/EmailNotifier.php';
            
            $emailNotifier = new EmailNotifier(true); // Debug mode
            
            // Crear datos de prueba
            $testClient = [
                'name' => 'Cliente de Prueba',
                'email' => $testEmail,
                'address' => 'Dirección de Prueba 123'
            ];
            
            $testTicket = [
                'id' => 'TEST001',
                'description' => 'Prueba de configuración de SendGrid',
                'scheduled_date' => date('Y-m-d'),
                'scheduled_time' => date('H:i:s'),
                'security_code' => 'TEST123'
            ];
            
            $testTechnician = [
                'name' => 'Técnico de Prueba'
            ];
            
            echo "Enviando email de prueba a $testEmail...\n";
            
            $result = $emailNotifier->sendAppointmentNotification($testClient, $testTicket, $testTechnician);
            
            if ($result) {
                echo "✅ Email de prueba enviado correctamente!\n";
                echo "📧 Revisa tu bandeja de entrada (y spam) en $testEmail\n";
            } else {
                echo "❌ Error enviando email de prueba.\n";
                echo "💡 Verifica tu API Key y configuración de SendGrid.\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error en la prueba: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Muestra el mensaje de éxito
     */
    private function showSuccessMessage() {
        echo "\n";
        echo "🎉 ¡CONFIGURACIÓN COMPLETADA!\n";
        echo "═══════════════════════════════\n\n";
        echo "✅ SendGrid configurado correctamente\n";
        echo "✅ Archivos de configuración creados\n";
        echo "✅ Sistema listo para enviar emails\n\n";
        
        echo "📝 PRÓXIMOS PASOS:\n";
        echo "─────────────────\n";
        echo "1. Si estás en desarrollo, asegúrate de que config/local.php no se suba a Git\n";
        echo "2. En producción, configura las variables de entorno en Railway\n";
        echo "3. Verifica que el dominio del email esté verificado en SendGrid\n";
        echo "4. Prueba el sistema creando una cita y verificando que el email llegue\n\n";
        
        echo "🔗 RECURSOS ÚTILES:\n";
        echo "──────────────────\n";
        echo "• Panel de SendGrid: https://app.sendgrid.com/\n";
        echo "• Verificar dominio: https://app.sendgrid.com/settings/sender_auth\n";
        echo "• Estadísticas de email: https://app.sendgrid.com/email_activity\n\n";
    }
    
    /**
     * Pide input al usuario
     */
    private function askInput($prompt, $hidden = false, $default = null) {
        $defaultText = $default ? " [$default]" : "";
        echo "$prompt$defaultText: ";
        
        if ($hidden) {
            // Para passwords, ocultar input (solo funciona en terminales compatibles)
            if (function_exists('readline')) {
                $input = readline("");
            } else {
                $input = trim(fgets(STDIN));
            }
        } else {
            $input = trim(fgets(STDIN));
        }
        
        return empty($input) ? $default : $input;
    }
    
    /**
     * Pide confirmación al usuario
     */
    private function askConfirmation($prompt) {
        echo "$prompt (s/n) [s]: ";
        $input = trim(fgets(STDIN));
        return empty($input) || strtolower($input) === 's' || strtolower($input) === 'si';
    }
}

// Ejecutar el setup si se llama directamente
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $setup = new SendGridSetup();
    $setup->run();
}
