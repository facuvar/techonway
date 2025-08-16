<?php
/**
 * Script para limpiar problema específico con email vargues@gmail.com
 */
require_once 'includes/init.php';

echo "<h1>🧹 Limpiar Problema Email Duplicado</h1>";

// Get database connection
$db = Database::getInstance();

try {
    echo "<div style='font-family: Arial; margin: 20px;'>";
    
    $problemEmail = 'vargues@gmail.com';
    
    // 1. Buscar todas las variaciones del email
    echo "<h2>1. Buscar Variaciones del Email</h2>";
    
    $variations = [
        'vargues@gmail.com',
        ' vargues@gmail.com', // Con espacio al inicio
        'vargues@gmail.com ', // Con espacio al final
        ' vargues@gmail.com ', // Con espacios en ambos lados
        'Vargues@gmail.com', // Con mayúscula
        'VARGUES@GMAIL.COM', // Todo mayúsculas
        'vargues@Gmail.com' // Gmail con mayúscula
    ];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Variación</th><th>Encontrado</th><th>ID</th><th>Nombre</th><th>Rol</th></tr>";
    
    $foundUsers = [];
    foreach ($variations as $email) {
        $user = $db->selectOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        echo "<tr>";
        echo "<td style='font-family: monospace;'>'" . htmlspecialchars($email) . "'</td>";
        
        if ($user) {
            echo "<td style='background: #ffcccc;'>✅ SÍ</td>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['role']}</td>";
            $foundUsers[] = $user;
        } else {
            echo "<td style='background: #ccffcc;'>❌ NO</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Buscar por LIKE para encontrar similares
    echo "<h2>2. Buscar Emails Similares (LIKE)</h2>";
    
    $similarUsers = $db->select("SELECT * FROM users WHERE email LIKE '%vargues%' OR email LIKE '%gmail%'");
    
    if (count($similarUsers) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Longitud</th></tr>";
        
        foreach ($similarUsers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td style='font-family: monospace;'>'" . htmlspecialchars($user['email']) . "'</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>" . strlen($user['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No se encontraron emails similares.</p>";
    }
    
    // 3. Verificar problema específico del formulario
    echo "<h2>3. Test del Formulario Específico</h2>";
    
    if ($_POST['test_form'] ?? false) {
        $testEmail = trim($_POST['test_email']);
        $testId = $_POST['test_id'] ?? null;
        
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>🧪 Simulando formulario:</h4>";
        echo "<p><strong>Email a probar:</strong> '" . htmlspecialchars($testEmail) . "'</p>";
        echo "<p><strong>ID del técnico (si edita):</strong> " . ($testId ?: 'Nuevo usuario') . "</p>";
        
        // Simular la misma lógica del formulario
        if ($testId) {
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ? AND id != ?", 
                [$testEmail, $testId]
            );
            echo "<p><strong>Query ejecutada:</strong> <code>SELECT * FROM users WHERE email = '$testEmail' AND id != $testId</code></p>";
        } else {
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ?", 
                [$testEmail]
            );
            echo "<p><strong>Query ejecutada:</strong> <code>SELECT * FROM users WHERE email = '$testEmail'</code></p>";
        }
        
        if ($existingUser) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "<h5>❌ RESULTADO: Email EN USO</h5>";
            echo "<p>Usuario encontrado: {$existingUser['name']} (ID: {$existingUser['id']}, Rol: {$existingUser['role']})</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "<h5>✅ RESULTADO: Email DISPONIBLE</h5>";
            echo "<p>No se encontraron conflictos. El email se puede usar.</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    echo "<form method='POST' style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Test del Formulario:</h4>";
    echo "<label>Email a probar: <input type='text' name='test_email' value='vargues@gmail.com' style='width: 300px; padding: 5px;'></label><br><br>";
    echo "<label>ID del técnico (dejar vacío para nuevo): <input type='number' name='test_id' value='5' style='width: 100px; padding: 5px;'></label><br><br>";
    echo "<button type='submit' name='test_form' value='1' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Probar Validación</button>";
    echo "</form>";
    
    // 4. Solución si hay espacios
    if (count($foundUsers) > 1) {
        echo "<h2>4. ⚠️ Múltiples Usuarios Encontrados</h2>";
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Problema detectado:</h4>";
        echo "<p>Se encontraron " . count($foundUsers) . " usuarios con variaciones del email.</p>";
        echo "<p>Esto puede causar conflictos en el formulario.</p>";
        echo "</div>";
        
        if ($_POST['clean_emails'] ?? false) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🧹 Limpiando emails...</h4>";
            
            // Limpiar todos los emails (trim)
            $cleaned = 0;
            $allUsers = $db->select("SELECT id, email FROM users");
            foreach ($allUsers as $user) {
                $cleanEmail = trim($user['email']);
                if ($cleanEmail !== $user['email']) {
                    $db->update('users', ['email' => $cleanEmail], 'id = ?', [$user['id']]);
                    $cleaned++;
                    echo "<p>✅ Limpiado usuario ID {$user['id']}: '{$user['email']}' → '$cleanEmail'</p>";
                }
            }
            
            if ($cleaned > 0) {
                echo "<p><strong>Se limpiaron $cleaned emails.</strong></p>";
            } else {
                echo "<p>No se encontraron emails que necesiten limpieza.</p>";
            }
            echo "</div>";
        } else {
            echo "<form method='POST' style='margin: 10px 0;'>";
            echo "<button type='submit' name='clean_emails' value='1' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>🧹 Limpiar Todos los Emails (trim)</button>";
            echo "</form>";
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Ocurrió un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
