<?php
/**
 * Script para diagnosticar problemas con técnicos
 */
require_once 'includes/init.php';

echo "<h1>🔧 Diagnóstico de Problemas con Técnicos</h1>";

// Get database connection
$db = Database::getInstance();

try {
    echo "<div style='font-family: Arial; margin: 20px;'>";
    
    // 1. Buscar el email problemático
    echo "<h2>📧 Problema 1: Email 'vargues@gmail.com'</h2>";
    $emailToCheck = 'vargues@gmail.com';
    
    $userWithEmail = $db->selectOne("SELECT * FROM users WHERE email = ?", [$emailToCheck]);
    
    if ($userWithEmail) {
        echo "<div style='background: #ffcccc; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Email SÍ existe en la base de datos:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Valor</th></tr>";
        foreach ($userWithEmail as $key => $value) {
            if ($key === 'password') {
                $value = '[HASH OCULTO]';
            }
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Verificar si aparece en la página de técnicos
        $allTechnicians = $db->select("SELECT * FROM users WHERE role = 'technician' ORDER BY name");
        $foundInTechList = false;
        foreach ($allTechnicians as $tech) {
            if ($tech['email'] === $emailToCheck) {
                $foundInTechList = true;
                break;
            }
        }
        
        echo "<p><strong>¿Aparece en el listado de técnicos?</strong> " . ($foundInTechList ? "✅ SÍ" : "❌ NO") . "</p>";
        
        if (!$foundInTechList && $userWithEmail['role'] !== 'technician') {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🔍 Posible causa encontrada:</h4>";
            echo "<p>El email existe pero el usuario tiene rol: <strong>{$userWithEmail['role']}</strong></p>";
            echo "<p>Por eso no aparece en el listado de técnicos (que solo muestra usuarios con role='technician')</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Email NO existe en la base de datos</h3>";
        echo "<p>El email '$emailToCheck' no está registrado. Debería poder crearse sin problemas.</p>";
        echo "</div>";
    }
    
    // 2. Problema de contraseñas
    echo "<h2>🔑 Problema 2: Cambio de Contraseñas</h2>";
    
    echo "<h3>Técnicos actuales y sus datos de login:</h3>";
    $technicians = $db->select("SELECT id, name, email, password, phone, zone, updated_at FROM users WHERE role = 'technician' ORDER BY name");
    
    if (count($technicians) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nombre</th><th>Email</th><th>Hash Password</th><th>Teléfono</th><th>Zona</th><th>Última Act.</th>";
        echo "</tr>";
        
        foreach ($technicians as $tech) {
            echo "<tr>";
            echo "<td>{$tech['id']}</td>";
            echo "<td>{$tech['name']}</td>";
            echo "<td>{$tech['email']}</td>";
            echo "<td style='font-family: monospace; font-size: 10px; max-width: 200px; word-break: break-all;'>" . substr($tech['password'], 0, 30) . "...</td>";
            echo "<td>{$tech['phone']}</td>";
            echo "<td>{$tech['zone']}</td>";
            echo "<td>{$tech['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No hay técnicos registrados.</p>";
    }
    
    // 3. Verificar estructura de contraseñas
    echo "<h3>🔍 Análisis de Hashes de Contraseña:</h3>";
    foreach ($technicians as $tech) {
        $hashInfo = password_get_info($tech['password']);
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff;'>";
        echo "<strong>{$tech['name']} ({$tech['email']}):</strong><br>";
        echo "Algoritmo: {$hashInfo['algo']} | Algoritmo Nombre: {$hashInfo['algoName']}<br>";
        echo "Hash válido: " . (password_get_info($tech['password'])['algo'] !== 0 ? "✅ SÍ" : "❌ NO") . "<br>";
        echo "</div>";
    }
    
    // 4. Verificar proceso de actualización de contraseñas
    echo "<h2>🔧 Verificación del Código de Actualización</h2>";
    echo "<h3>Lógica actual en admin/technicians.php:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
    echo "// Líneas 36-40 del código actual:<br>";
    echo "if ((!isset(\$_POST['technician_id']) || empty(\$_POST['technician_id'])) || <br>";
    echo "    (isset(\$_POST['password']) && !empty(\$_POST['password']))) {<br>";
    echo "    \$technicianData['password'] = password_hash(\$_POST['password'], PASSWORD_DEFAULT);<br>";
    echo "}";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>⚠️ Posibles problemas identificados:</h4>";
    echo "<ol>";
    echo "<li><strong>Problema de email duplicado:</strong> El sistema puede estar verificando emails en toda la tabla 'users' (admins + técnicos), no solo técnicos.</li>";
    echo "<li><strong>Problema de contraseña:</strong> El hash se genera pero puede no estar guardándose correctamente en la BD.</li>";
    echo "<li><strong>Cache de sesión:</strong> El técnico puede estar usando datos cacheados de la sesión anterior.</li>";
    echo "</ol>";
    echo "</div>";
    
    // 5. Soluciones recomendadas
    echo "<h2>💡 Soluciones Recomendadas</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Para resolver estos problemas:</h4>";
    echo "<ol>";
    echo "<li><strong>Limpiar datos inconsistentes:</strong> Eliminar usuarios con emails problemáticos pero roles incorrectos</li>";
    echo "<li><strong>Mejorar validación:</strong> Verificar emails solo dentro del contexto apropiado (admin vs técnico)</li>";
    echo "<li><strong>Debug de contraseñas:</strong> Añadir logs para ver si las contraseñas se actualizan correctamente</li>";
    echo "<li><strong>Forzar logout:</strong> Invalidar sesiones existentes después de cambio de contraseña</li>";
    echo "</ol>";
    echo "</div>";
    
    // 6. Test rápido de verificación de contraseña
    echo "<h2>🧪 Test de Verificación de Contraseñas</h2>";
    echo "<p>Para verificar si una contraseña específica funciona, ingrese los datos aquí:</p>";
    
    if ($_POST['test_login'] ?? false) {
        $testEmail = $_POST['test_email'] ?? '';
        $testPassword = $_POST['test_password'] ?? '';
        
        if ($testEmail && $testPassword) {
            $user = $db->selectOne("SELECT * FROM users WHERE email = ?", [$testEmail]);
            if ($user) {
                $passwordValid = password_verify($testPassword, $user['password']);
                echo "<div style='background: " . ($passwordValid ? "#d4edda" : "#f8d7da") . "; padding: 15px; border-radius: 5px;'>";
                echo "<h4>" . ($passwordValid ? "✅ Contraseña CORRECTA" : "❌ Contraseña INCORRECTA") . "</h4>";
                echo "<p>Usuario: {$user['name']} ({$user['email']})</p>";
                echo "<p>Rol: {$user['role']}</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "<h4>❌ Usuario no encontrado</h4>";
                echo "<p>Email '$testEmail' no existe en la base de datos.</p>";
                echo "</div>";
            }
        }
    }
    
    echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Probar Login:</h4>";
    echo "<label>Email: <input type='email' name='test_email' required style='width: 300px; padding: 5px;'></label><br><br>";
    echo "<label>Contraseña: <input type='password' name='test_password' required style='width: 300px; padding: 5px;'></label><br><br>";
    echo "<button type='submit' name='test_login' value='1' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Probar Login</button>";
    echo "</form>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Ocurrió un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
