<?php
/**
 * Debug específico para problemas de login
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Login Debug - TechonWay</h1>";
echo "<pre>";

try {
    require_once 'includes/init.php';
    echo "✅ Init loaded successfully\n";
    
    $db = Database::getInstance();
    echo "✅ Database connected\n";
    
    // Buscar usuarios en la BD
    echo "\n👥 Usuarios en la base de datos:\n";
    $users = $db->select("SELECT id, name, email, role, created_at FROM users ORDER BY id");
    
    if (empty($users)) {
        echo "❌ No hay usuarios en la base de datos\n";
        echo "🔧 Crear usuarios con: /create_admin.php\n";
    } else {
        foreach ($users as $user) {
            echo "   ID: {$user['id']} | Email: {$user['email']} | Role: {$user['role']} | Name: {$user['name']}\n";
        }
    }
    
    // Test de verificación de contraseña específico para admin
    echo "\n🔑 Test de contraseña para admin@techonway.com:\n";
    $admin = $db->selectOne("SELECT * FROM users WHERE email = ? AND role = ?", ['admin@techonway.com', 'admin']);
    
    if ($admin) {
        echo "✅ Usuario admin encontrado\n";
        echo "   Hash en BD: " . substr($admin['password'], 0, 20) . "...\n";
        
        // Test con contraseña admin123
        $testPassword = 'admin123';
        $isValid = password_verify($testPassword, $admin['password']);
        echo "   Test 'admin123': " . ($isValid ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
        
        // Test con diferentes contraseñas comunes
        $testPasswords = ['admin123', 'Admin123', 'ADMIN123', 'admin', '123456'];
        echo "   Probando contraseñas comunes:\n";
        foreach ($testPasswords as $pass) {
            $valid = password_verify($pass, $admin['password']);
            echo "     '{$pass}': " . ($valid ? "✅ VÁLIDA" : "❌ inválida") . "\n";
        }
        
    } else {
        echo "❌ Usuario admin no encontrado\n";
    }
    
    // Test del método Auth->login
    echo "\n🔐 Test del método Auth->login:\n";
    $auth = new Auth();
    
    // Probar login
    $loginResult = $auth->login('admin@techonway.com', 'admin123', 'admin');
    echo "   Login admin@techonway.com / admin123: " . ($loginResult ? "✅ EXITOSO" : "❌ FALLÓ") . "\n";
    
    if ($loginResult) {
        echo "   Usuario logueado: " . ($auth->isLoggedIn() ? "✅ SÍ" : "❌ NO") . "\n";
        if ($auth->isLoggedIn()) {
            $currentUser = $auth->getUser();
            echo "   Usuario actual: " . $currentUser['email'] . " (" . $currentUser['role'] . ")\n";
        }
    }
    
    echo "\n🔗 Enlaces:\n";
    echo "   <a href='/login.php'>🔐 Probar Login</a>\n";
    echo "   <a href='/create_admin.php'>👤 Crear Admin</a>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
