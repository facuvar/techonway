<?php
/**
 * Setup inicial para Railway - Importación de base de datos
 * Este archivo se ejecuta UNA VEZ para configurar la base de datos
 */

// Solo permitir en Railway
$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
           strpos($_SERVER['HTTP_HOST'] ?? '', '.local') !== false);

if ($isLocal) {
    die("❌ Este setup solo funciona en Railway");
}

// Incluir configuración
require_once 'includes/init.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Setup TechonWay - Railway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .setup-container { max-width: 800px; margin: 50px auto; }
        .card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
        .btn-primary { background: #28a745; border-color: #28a745; }
        .log-output { background: #000; color: #00ff00; padding: 15px; border-radius: 5px; font-family: monospace; height: 400px; overflow-y: auto; }
        .step { margin: 20px 0; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px; }
        .step.success { background: rgba(40, 167, 69, 0.3); }
        .step.error { background: rgba(220, 53, 69, 0.3); }
        .step.loading { background: rgba(255, 193, 7, 0.3); }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="card">
            <div class="card-header text-center">
                <h1>🚀 TechonWay Setup - Railway</h1>
                <p>Configuración inicial de la base de datos</p>
            </div>
            <div class="card-body">
                <div id="setup-steps">
                    
                    <div class="step" id="step-env">
                        <h5>📋 Paso 1: Verificar Variables de Entorno</h5>
                        <div id="env-status">Verificando...</div>
                    </div>
                    
                    <div class="step" id="step-db">
                        <h5>🔌 Paso 2: Conexión a Base de Datos</h5>
                        <div id="db-status">Esperando...</div>
                    </div>
                    
                    <div class="step" id="step-import">
                        <h5>📥 Paso 3: Importar Datos</h5>
                        <div id="import-status">Esperando...</div>
                        <button class="btn btn-primary mt-2" id="start-import" style="display:none;">Iniciar Importación</button>
                    </div>
                    
                    <div class="step" id="step-complete">
                        <h5>✅ Paso 4: Finalización</h5>
                        <div id="complete-status">Esperando...</div>
                    </div>
                    
                </div>
                
                <div class="mt-4">
                    <h6>📝 Log de Proceso:</h6>
                    <div class="log-output" id="log-output">Iniciando setup...\n</div>
                </div>
            </div>
        </div>
    </div>

<script>
function log(message) {
    const logOutput = document.getElementById('log-output');
    const timestamp = new Date().toLocaleTimeString();
    logOutput.innerHTML += `[${timestamp}] ${message}\n`;
    logOutput.scrollTop = logOutput.scrollHeight;
}

function updateStep(stepId, status, message) {
    const step = document.getElementById(stepId);
    const statusDiv = document.getElementById(stepId.replace('step-', '') + '-status');
    
    step.className = 'step ' + status;
    statusDiv.innerHTML = message;
    
    log(`${stepId}: ${message}`);
}

// Verificar variables de entorno
async function checkEnvironment() {
    try {
        const response = await fetch('setup_api.php?action=check_env');
        const data = await response.json();
        
        if (data.success) {
            updateStep('step-env', 'success', '✅ Todas las variables configuradas correctamente');
            checkDatabase();
        } else {
            updateStep('step-env', 'error', '❌ Variables faltantes: ' + data.missing.join(', '));
        }
    } catch (error) {
        updateStep('step-env', 'error', '❌ Error verificando variables: ' + error.message);
    }
}

// Verificar conexión a base de datos
async function checkDatabase() {
    updateStep('step-db', 'loading', '🔄 Conectando a base de datos...');
    
    try {
        const response = await fetch('setup_api.php?action=check_db');
        const data = await response.json();
        
        if (data.success) {
            updateStep('step-db', 'success', '✅ Conexión exitosa - ' + data.info);
            document.getElementById('start-import').style.display = 'block';
        } else {
            updateStep('step-db', 'error', '❌ Error de conexión: ' + data.error);
        }
    } catch (error) {
        updateStep('step-db', 'error', '❌ Error verificando DB: ' + error.message);
    }
}

// Importar base de datos
async function importDatabase() {
    updateStep('step-import', 'loading', '🔄 Importando base de datos...');
    document.getElementById('start-import').style.display = 'none';
    
    try {
        const response = await fetch('setup_api.php?action=import_db');
        const data = await response.json();
        
        if (data.success) {
            updateStep('step-import', 'success', `✅ Importación exitosa - ${data.stats}`);
            completeSetup();
        } else {
            updateStep('step-import', 'error', '❌ Error en importación: ' + data.error);
        }
    } catch (error) {
        updateStep('step-import', 'error', '❌ Error en importación: ' + error.message);
    }
}

// Finalizar setup
function completeSetup() {
    updateStep('step-complete', 'success', '🎉 Setup completado! El sistema está listo.');
    
    setTimeout(() => {
        window.location.href = 'login.php';
    }, 3000);
}

// Iniciar proceso
document.addEventListener('DOMContentLoaded', function() {
    log('🚀 Iniciando setup de TechonWay en Railway...');
    checkEnvironment();
    
    document.getElementById('start-import').addEventListener('click', importDatabase);
});
</script>

</body>
</html>
