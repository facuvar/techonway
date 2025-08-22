<?php
/**
 * Script de utilidad para habilitar/deshabilitar plantillas HSM de WhatsApp
 * 
 * Uso:
 * php whatsapp_hsm_toggle.php enable  - Habilitar plantillas HSM
 * php whatsapp_hsm_toggle.php disable - Deshabilitar plantillas HSM
 * php whatsapp_hsm_toggle.php status  - Ver estado actual
 */

function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

function getConfig() {
    $configFile = __DIR__ . '/config/whatsapp.php';
    if (!file_exists($configFile)) {
        throw new Exception("Archivo de configuración no encontrado: {$configFile}");
    }
    return file_get_contents($configFile);
}

function updateConfig($content, $enableHsm) {
    $newValue = $enableHsm ? 'true' : 'false';
    $pattern = "/'use_hsm_templates'\s*=>\s*(true|false)/";
    $replacement = "'use_hsm_templates' => {$newValue}";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    if ($newContent === $content) {
        throw new Exception("No se pudo encontrar la configuración 'use_hsm_templates' en el archivo");
    }
    
    return $newContent;
}

function getCurrentStatus() {
    $config = require __DIR__ . '/config/whatsapp.php';
    return $config['use_hsm_templates'] ?? false;
}

// Verificar argumentos
if ($argc < 2) {
    echo "Uso: php whatsapp_hsm_toggle.php [enable|disable|status]\n";
    echo "\n";
    echo "Comandos:\n";
    echo "  enable  - Habilitar plantillas HSM\n";
    echo "  disable - Deshabilitar plantillas HSM\n";
    echo "  status  - Ver estado actual\n";
    exit(1);
}

$command = strtolower($argv[1]);

try {
    switch ($command) {
        case 'status':
            $currentStatus = getCurrentStatus();
            logMessage("Estado actual de plantillas HSM: " . ($currentStatus ? "HABILITADAS" : "DESHABILITADAS"));
            
            if ($currentStatus) {
                logMessage("✅ El sistema está usando plantillas HSM de META");
                logMessage("   Los técnicos reciben notificaciones sin limitación de 24 horas");
            } else {
                logMessage("⚠️  El sistema está usando mensajes de texto simples");
                logMessage("   Los técnicos pueden dejar de recibir notificaciones después de 24 horas");
            }
            break;
            
        case 'enable':
            $currentStatus = getCurrentStatus();
            if ($currentStatus) {
                logMessage("Las plantillas HSM ya están habilitadas");
                break;
            }
            
            logMessage("Habilitando plantillas HSM...");
            
            // Verificar que existan las plantillas
            $templatesDir = __DIR__ . '/whatsapp_templates';
            $requiredTemplates = [
                'nuevo_ticket.json',
                'nuevo_ticket_con_cita.json',
                'reprogramacion_cita.json',
                'bienvenida_tecnico.json'
            ];
            
            foreach ($requiredTemplates as $template) {
                if (!file_exists($templatesDir . '/' . $template)) {
                    throw new Exception("Plantilla requerida no encontrada: {$template}");
                }
            }
            
            // Actualizar configuración
            $configContent = getConfig();
            $newContent = updateConfig($configContent, true);
            
            // Hacer backup del archivo original
            $backupFile = __DIR__ . '/config/whatsapp.php.backup.' . date('Y-m-d_H-i-s');
            file_put_contents($backupFile, $configContent);
            logMessage("Backup creado: " . basename($backupFile));
            
            // Escribir nueva configuración
            file_put_contents(__DIR__ . '/config/whatsapp.php', $newContent);
            
            logMessage("✅ Plantillas HSM habilitadas exitosamente");
            logMessage("   IMPORTANTE: Asegúrate de que las plantillas estén aprobadas por META");
            logMessage("   Ejecuta 'php test_hsm_templates.php' para probar");
            break;
            
        case 'disable':
            $currentStatus = getCurrentStatus();
            if (!$currentStatus) {
                logMessage("Las plantillas HSM ya están deshabilitadas");
                break;
            }
            
            logMessage("Deshabilitando plantillas HSM...");
            
            // Actualizar configuración
            $configContent = getConfig();
            $newContent = updateConfig($configContent, false);
            
            // Hacer backup del archivo original
            $backupFile = __DIR__ . '/config/whatsapp.php.backup.' . date('Y-m-d_H-i-s');
            file_put_contents($backupFile, $configContent);
            logMessage("Backup creado: " . basename($backupFile));
            
            // Escribir nueva configuración
            file_put_contents(__DIR__ . '/config/whatsapp.php', $newContent);
            
            logMessage("✅ Plantillas HSM deshabilitadas exitosamente");
            logMessage("   El sistema volverá a usar mensajes de texto simples");
            logMessage("   ⚠️  Recuerda la limitación de 24 horas de WhatsApp");
            break;
            
        default:
            logMessage("ERROR: Comando desconocido '{$command}'");
            logMessage("Comandos válidos: enable, disable, status");
            exit(1);
    }
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}
?>
