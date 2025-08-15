<?php
/**
 * Script simple para restablecer credenciales del administrador
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'techonway';
$username = 'root';
$password = '';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<h1>🔑 Restablecimiento de Credenciales</h1>";
    
    // Verificar si la tabla users existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    
    if ($tableCheck == 0) {
        echo "<p style='color: red;'>❌ La tabla 'users' no existe. Necesitas ejecutar el setup de la base de datos primero.</p>";
        echo "<p><a href='database/setup_database.php'>Ejecutar Setup de Base de Datos</a></p>";
        exit;
    }
    
    // Hash de la contraseña
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Verificar si existe un admin
    $existingAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = ?");
    $existingAdmin->execute(['admin@techonway.com', 'admin']);
    $admin = $existingAdmin->fetch();
    
    if ($admin) {
        // Actualizar admin existente
        $updateAdmin = $pdo->prepare("UPDATE users SET password = ?, name = ? WHERE id = ?");
        $updateAdmin->execute([$hashedPassword, 'Administrador', $admin['id']]);
        echo "<p style='color: green;'>✅ Usuario administrador actualizado exitosamente.</p>";
    } else {
        // Crear nuevo admin
        $createAdmin = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $createAdmin->execute(['Administrador', 'admin@techonway.com', $hashedPassword, 'admin']);
        echo "<p style='color: green;'>✅ Usuario administrador creado exitosamente.</p>";
    }
    
    // También crear/actualizar técnico de ejemplo
    $hashedTechPassword = password_hash('tecnico123', PASSWORD_DEFAULT);
    
    $existingTech = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = ?");
    $existingTech->execute(['tecnico@techonway.com', 'technician']);
    $tech = $existingTech->fetch();
    
    if ($tech) {
        $updateTech = $pdo->prepare("UPDATE users SET password = ?, name = ?, zone = ? WHERE id = ?");
        $updateTech->execute([$hashedTechPassword, 'Técnico Demo', 'Norte', $tech['id']]);
        echo "<p style='color: green;'>✅ Usuario técnico actualizado exitosamente.</p>";
    } else {
        $createTech = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, zone) VALUES (?, ?, ?, ?, ?, ?)");
        $createTech->execute(['Técnico Demo', 'tecnico@techonway.com', $hashedTechPassword, '123456789', 'technician', 'Norte']);
        echo "<p style='color: green;'>✅ Usuario técnico creado exitosamente.</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 Credenciales de Acceso</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>👨‍💼 ADMINISTRADOR:</h3>";
    echo "<p><strong>Email:</strong> <code>admin@techonway.com</code></p>";
    echo "<p><strong>Contraseña:</strong> <code>admin123</code></p>";
    echo "</div>";
    
    echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>👨‍🔧 TÉCNICO:</h3>";
    echo "<p><strong>Email:</strong> <code>tecnico@techonway.com</code></p>";
    echo "<p><strong>Contraseña:</strong> <code>tecnico123</code></p>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>🌐 Acceso al Sistema</h3>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Sistema</a></p>";
    
    // Mostrar usuarios existentes
    echo "<hr>";
    echo "<h3>📋 Usuarios en la Base de Datos</h3>";
    $users = $pdo->query("SELECT id, name, email, role FROM users ORDER BY role, id")->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th>";
        echo "</tr>";
        
        foreach ($users as $user) {
            $bgColor = $user['role'] === 'admin' ? '#fff3cd' : '#d4edda';
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . ucfirst($user['role']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<h1>❌ Error de Conexión</h1>";
    echo "<p style='color: red;'>No se pudo conectar a la base de datos: " . $e->getMessage() . "</p>";
    
    echo "<h3>🔧 Posibles soluciones:</h3>";
    echo "<ol>";
    echo "<li><strong>Verificar XAMPP:</strong> Asegúrate de que Apache y MySQL estén ejecutándose</li>";
    echo "<li><strong>Verificar base de datos:</strong> La base de datos 'techonway' debe existir</li>";
    echo "<li><strong>Credenciales MySQL:</strong> Usuario 'root' sin contraseña (configuración por defecto de XAMPP)</li>";
    echo "</ol>";
    
    echo "<p><strong>Para crear la base de datos:</strong></p>";
    echo "<p>1. Abre phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></p>";
    echo "<p>2. Crea una nueva base de datos llamada 'techonway'</p>";
    echo "<p>3. Ejecuta: <a href='database/setup_database.php'>Setup de Base de Datos</a></p>";
}
?>
