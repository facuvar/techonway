<?php
/**
 * Script para añadir sistema de invalidación de sesiones
 */
require_once 'includes/init.php';

echo "<h1>🔧 Añadir Sistema de Invalidación de Sesiones</h1>";

// Get database connection
$db = Database::getInstance();

try {
    echo "<div style='font-family: Arial; margin: 20px;'>";
    
    // 1. Verificar si la columna session_invalidated_at existe
    echo "<h2>1. Verificar Estructura de Tabla</h2>";
    
    $columns = $db->query("DESCRIBE users");
    $hasSessionColumn = false;
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    
    while ($row = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'session_invalidated_at') {
            $hasSessionColumn = true;
        }
    }
    echo "</table>";
    
    // 2. Añadir columna si no existe
    if (!$hasSessionColumn) {
        echo "<h2>2. Añadir Columna de Invalidación de Sesiones</h2>";
        
        $sql = "ALTER TABLE users ADD COLUMN session_invalidated_at TIMESTAMP NULL DEFAULT NULL";
        $db->query($sql);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Columna añadida exitosamente</h3>";
        echo "<p>Se añadió la columna 'session_invalidated_at' a la tabla 'users'</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h3>⚠️ Columna ya existe</h3>";
        echo "<p>La columna 'session_invalidated_at' ya está en la tabla 'users'</p>";
        echo "</div>";
    }
    
    // 3. Verificar la nueva estructura
    echo "<h2>3. Nueva Estructura de Tabla</h2>";
    
    $newColumns = $db->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    
    while ($row = $newColumns->fetch(PDO::FETCH_ASSOC)) {
        $highlight = ($row['Field'] === 'session_invalidated_at') ? 'background: #d4edda;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Crear función para invalidar sesiones
    echo "<h2>4. Funciones de Invalidación</h2>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Función a añadir en admin/technicians.php:</h4>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 3px;'>";
    echo "// Invalidar sesiones del usuario cuando se cambia la contraseña\n";
    echo "if (\$passwordChanged) {\n";
    echo "    \$db->update('users', \n";
    echo "        ['session_invalidated_at' => date('Y-m-d H:i:s')], \n";
    echo "        'id = ?', \n";
    echo "        [\$_POST['technician_id']]\n";
    echo "    );\n";
    echo "}";
    echo "</pre>";
    echo "</div>";
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;'>";
    echo "<h4>Función a añadir en includes/Auth.php:</h4>";
    echo "<pre style='background: #e9ecef; padding: 10px; border-radius: 3px;'>";
    echo "// Verificar si la sesión fue invalidada\n";
    echo "public function isSessionValid(\$userId, \$loginTime) {\n";
    echo "    \$user = Database::getInstance()->selectOne(\n";
    echo "        \"SELECT session_invalidated_at FROM users WHERE id = ?\", \n";
    echo "        [\$userId]\n";
    echo "    );\n";
    echo "    \n";
    echo "    if (\$user && \$user['session_invalidated_at']) {\n";
    echo "        \$invalidatedAt = strtotime(\$user['session_invalidated_at']);\n";
    echo "        \$loginTimestamp = strtotime(\$loginTime);\n";
    echo "        \n";
    echo "        // Si la sesión fue invalidada después del login, la sesión es inválida\n";
    echo "        return \$invalidatedAt < \$loginTimestamp;\n";
    echo "    }\n";
    echo "    \n";
    echo "    return true; // Sesión válida\n";
    echo "}";
    echo "</pre>";
    echo "</div>";
    
    // 5. Test de invalidación
    echo "<h2>5. Test de Invalidación (Ejemplo)</h2>";
    
    $testUserId = 5; // Usuario vargues@gmail.com
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🧪 Simular invalidación de sesión para usuario ID $testUserId:</h4>";
    
    if ($_POST['test_invalidate'] ?? false) {
        $db->update('users', 
            ['session_invalidated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$testUserId]
        );
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h5>✅ Sesión invalidada</h5>";
        echo "<p>El usuario ID $testUserId ahora tiene su sesión marcada como invalidada.</p>";
        echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
        echo "</div>";
        
        // Mostrar resultado
        $user = $db->selectOne("SELECT name, email, session_invalidated_at FROM users WHERE id = ?", [$testUserId]);
        if ($user) {
            echo "<p><strong>Usuario:</strong> {$user['name']} ({$user['email']})</p>";
            echo "<p><strong>Sesión invalidada en:</strong> {$user['session_invalidated_at']}</p>";
        }
    } else {
        echo "<form method='POST'>";
        echo "<p>Esto marcará que todas las sesiones del usuario deben ser invalidadas:</p>";
        echo "<button type='submit' name='test_invalidate' value='1' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Invalidar Sesión del Usuario $testUserId</button>";
        echo "</form>";
    }
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Ocurrió un error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
