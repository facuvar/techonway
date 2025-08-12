<?php
/**
 * Database connection class
 */
class Database {
    private static $instance = null;
    private $connection;
    private static $connectionCount = 0;
    
    private function __construct() {
        // Usar require en lugar de require_once para evitar problemas de cache
        $config = @require __DIR__ . '/../config/database.php';
        
        try {
            // Verificar que config es un array válido
            if (!is_array($config)) {
                // Fallback a configuración de servidor con config local si existe
                $fallbackPassword = '';
                
                // Cargar configuración local si existe
                $localConfigFile = BASE_PATH . '/config/local.php';
                if (file_exists($localConfigFile)) {
                    $localConfig = require $localConfigFile;
                    if (isset($localConfig['database_server']['password'])) {
                        $fallbackPassword = $localConfig['database_server']['password'];
                    }
                }
                
                $config = [
                    'host' => 'localhost',
                    'dbname' => 'techonway_demo',
                    'username' => 'techonway_demousr',
                    'password' => $fallbackPassword,
                    'charset' => 'utf8',
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false, // Evitar conexiones persistentes en VPS
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                        PDO::ATTR_TIMEOUT => 30, // Timeout de conexión
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                    ]
                ];
            }
            
            // Manejo robusto del charset
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8';
            
            // Intentar primero sin charset, luego con fallbacks
            $dsn_variants = [
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$charset}",
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
                "mysql:host={$config['host']};dbname={$config['dbname']}"
            ];
            
            $connection_error = null;
            // Prevent too many connections - más restrictivo para VPS
            if (self::$connectionCount > 2) {
                throw new PDOException("Too many connection attempts. Server may be overloaded.");
            }
            
            self::$connectionCount++;
            
            foreach ($dsn_variants as $dsn) {
                try {
                    $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    // Solo log en desarrollo para evitar sobrecarga en VPS
                    if (class_exists('Logger') && (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)) {
                        Logger::database('Conexión exitosa a base de datos', [
                            'host' => $config['host'],
                            'database' => $config['dbname'],
                            'charset' => $charset
                        ]);
                    }
                    break; // Si funciona, salir del loop
                } catch (PDOException $e) {
                    $connection_error = $e->getMessage();
                    // Solo log en desarrollo
                    if (class_exists('Logger') && (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)) {
                        Logger::warning('Fallo en conexión a DB', [
                            'dsn' => $dsn,
                            'error' => $e->getMessage()
                        ]);
                    }
                    // Si es "too many connections", no seguir intentando
                    if (strpos($e->getMessage(), 'Too many connections') !== false) {
                        if (class_exists('Logger') && (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)) {
                            Logger::error('Too many connections en base de datos', [
                                'connection_count' => self::$connectionCount,
                                'error' => $e->getMessage()
                            ]);
                        }
                        throw new PDOException("Database server overloaded: " . $e->getMessage());
                    }
                    continue; // Probar siguiente variante
                }
            }
            
            // Si no se pudo conectar con ninguna variante
            if (!$this->connection) {
                throw new PDOException("Failed to connect with any charset variant. Last error: " . $connection_error);
            }
            
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
    }
}