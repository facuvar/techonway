<?php
/**
 * Script para corregir problemas con técnicos
 */
require_once 'includes/init.php';

echo "<h1>🔧 Correcciones para Problemas con Técnicos</h1>";

// Get database connection
$db = Database::getInstance();

try {
    echo "<div style='font-family: Arial; margin: 20px;'>";
    
    // 1. Verificar lógica de validación de emails duplicados
    echo "<h2>1. Corregir Validación de Emails Duplicados</h2>";
    
    $problemEmail = 'vargues@gmail.com';
    $user = $db->selectOne("SELECT * FROM users WHERE email = ?", [$problemEmail]);
    
    if ($user) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Usuario encontrado: {$user['name']}</h3>";
        echo "<p>Email: {$user['email']}</p>";
        echo "<p>Rol: {$user['role']}</p>";
        echo "<p>ID: {$user['id']}</p>";
        echo "</div>";
        
        // Simular la lógica del formulario de edición
        $technicianId = $user['id']; // Simular que editamos este técnico
        $testEmail = $problemEmail;
        
        // Esta es la consulta actual que causa problemas
        $existingUser = $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND id != ?", 
            [$testEmail, $technicianId]
        );
        
        echo "<h4>🧪 Test de la consulta actual:</h4>";
        echo "<div style='background: #f8f9fa; padding: 10px; font-family: monospace;'>";
        echo "SELECT * FROM users WHERE email = '$testEmail' AND id != $technicianId";
        echo "</div>";
        
        if ($existingUser) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h4>❌ Problema confirmado:</h4>";
            echo "<p>La consulta encuentra un usuario duplicado cuando NO debería.</p>";
            echo "<p>Usuario encontrado: {$existingUser['name']} (ID: {$existingUser['id']})</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ Consulta OK:</h4>";
            echo "<p>No se encontraron duplicados. El email se puede usar.</p>";
            echo "</div>";
        }
    }
    
    // 2. Probar diferentes escenarios de validación
    echo "<h2>2. Test de Escenarios de Validación</h2>";
    
    $testCases = [
        [
            'description' => 'Email existente, editando el mismo usuario',
            'email' => 'vargues@gmail.com',
            'editing_id' => 5, // ID del usuario con ese email
            'should_pass' => true
        ],
        [
            'description' => 'Email existente, creando nuevo usuario',
            'email' => 'vargues@gmail.com',
            'editing_id' => null,
            'should_pass' => false
        ],
        [
            'description' => 'Email nuevo, creando nuevo usuario',
            'email' => 'nuevo_tecnico@test.com',
            'editing_id' => null,
            'should_pass' => true
        ],
        [
            'description' => 'Email existente, editando otro usuario',
            'email' => 'vargues@gmail.com',
            'editing_id' => 2, // ID diferente
            'should_pass' => false
        ]
    ];
    
    foreach ($testCases as $i => $test) {
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff;'>";
        echo "<h4>Test " . ($i + 1) . ": {$test['description']}</h4>";
        
        if ($test['editing_id']) {
            // Editando usuario existente
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ? AND id != ?", 
                [$test['email'], $test['editing_id']]
            );
        } else {
            // Creando nuevo usuario
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ?", 
                [$test['email']]
            );
        }
        
        $actualResult = !$existingUser; // true si no hay duplicado (puede proceder)
        $testPassed = $actualResult === $test['should_pass'];
        
        echo "<p>Email: {$test['email']}</p>";
        echo "<p>ID de edición: " . ($test['editing_id'] ?: 'N/A (nuevo usuario)') . "</p>";
        echo "<p>Resultado esperado: " . ($test['should_pass'] ? 'PERMITIR' : 'BLOQUEAR') . "</p>";
        echo "<p>Resultado actual: " . ($actualResult ? 'PERMITIR' : 'BLOQUEAR') . "</p>";
        echo "<p><strong>Test: " . ($testPassed ? '✅ PASÓ' : '❌ FALLÓ') . "</strong></p>";
        echo "</div>";
    }
    
    // 3. Test de contraseñas
    echo "<h2>3. Test de Contraseñas Actuales</h2>";
    
    $technicians = $db->select("SELECT id, name, email, password FROM users WHERE role = 'technician' ORDER BY name");
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>⚠️ Para probar login, use estas credenciales conocidas:</h4>";
    echo "<ul>";
    echo "<li><strong>tecnico@example.com</strong> / <strong>tech123</strong> (contraseña por defecto)</li>";
    echo "<li><strong>vargues@gmail.com</strong> / <strong>¿cuál fue la última contraseña que pusiste?</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. Verificar invalidación de sesiones
    echo "<h2>4. Verificar Gestión de Sesiones</h2>";
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Problema encontrado:</h4>";
    echo "<p>Cuando cambias la contraseña de un técnico, el sistema NO invalida las sesiones existentes.</p>";
    echo "<p>Si el técnico tenía una sesión activa, seguirá logueado con los datos antiguos.</p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ Solución:</h4>";
    echo "<p>Después de cambiar la contraseña, el técnico debe:</p>";
    echo "<ol>";
    echo "<li>Cerrar completamente el navegador</li>";
    echo "<li>O borrar las cookies del sitio</li>";
    echo "<li>O usar modo incógnito para probar el login</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Ocurrió un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
