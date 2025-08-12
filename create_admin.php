<?php
/**
 * Script para crear usuario admin inicial en Railway
 * Ejecutar UNA VEZ después de importar la BD
 */

// Solo permitir ejecución en Railway
$isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
if ($isLocal) {
    die("❌ Este script solo debe ejecutarse en Railway");
}

echo "<h1>👤 Crear Usuario Admin - TechonWay</h1>";
echo "<pre>";

try {
    // Cargar configuración
    require_once 'includes/init.php';
    $db = Database::getInstance();
    
    echo "✅ Conexión a base de datos exitosa\n";
    
    // Verificar si ya existe un admin
    $existingAdmin = $db->selectOne("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    
    if ($existingAdmin) {
        echo "⚠️  Ya existe un usuario admin:\n";
        echo "   Email: " . $existingAdmin['email'] . "\n";
        echo "   Nombre: " . $existingAdmin['name'] . "\n";
        echo "\n🔗 <a href='/login.php'>Ir al Login</a>\n";
        exit;
    }
    
    // Datos del admin por defecto
    $adminData = [
        'name' => 'Administrador TechonWay',
        'email' => 'admin@techonway.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'phone' => '+54911234567',
        'avatar' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Insertar admin
    $adminId = $db->insert('users', $adminData);
    
    echo "🎉 Usuario administrador creado exitosamente!\n\n";
    echo "📋 CREDENCIALES DE ACCESO:\n";
    echo "   Email: admin@techonway.com\n";
    echo "   Password: admin123\n\n";
    echo "⚠️  IMPORTANTE: Cambia la contraseña después del primer login\n\n";
    
    // Crear algunos datos de ejemplo si no existen
    $technicianCount = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'technician'")['count'];
    
    if ($technicianCount == 0) {
        echo "🔧 Creando técnico de ejemplo...\n";
        
        $techData = [
            'name' => 'Técnico Demo',
            'email' => 'tecnico@techonway.com',
            'password' => password_hash('tecnico123', PASSWORD_DEFAULT),
            'role' => 'technician',
            'phone' => '+54911234568',
            'avatar' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('users', $techData);
        echo "✅ Técnico demo creado: tecnico@techonway.com / tecnico123\n\n";
    }
    
    echo "🔗 Enlaces:\n";
    echo "   <a href='/login.php'>🔐 Ir al Login</a>\n";
    echo "   <a href='/admin/dashboard.php'>📊 Dashboard Admin</a>\n\n";
    
    echo "⚠️  ELIMINA ESTE ARCHIVO después de usarlo por seguridad\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
