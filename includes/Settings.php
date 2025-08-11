<?php

class Settings {
    private $db;
    private $pdo;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->ensureTable();
    }

    public function get($key, $default = null) {
        try {
            $row = $this->db->selectOne('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
            if ($row && isset($row['value'])) {
                return $row['value'];
            }
            return $default;
        } catch (Throwable $e) {
            // Si la tabla no existe u otro error, devolvemos el valor por defecto
            return $default;
        }
    }

    public function set($key, $value) {
        try {
            $exists = $this->db->selectOne('SELECT `key` FROM settings WHERE `key` = ?', [$key]);
            if ($exists) {
                $this->db->update('settings', ['value' => $value], '`key` = ?', [$key]);
            } else {
                // Evitar conflicto con palabra reservada `key`
                $this->db->query('INSERT INTO settings (`key`, `value`) VALUES (?, ?)', [$key, $value]);
            }
        } catch (Throwable $e) {
            // Loguear y continuar silenciosamente
            error_log('Settings set error: ' . $e->getMessage());
        }
    }

    private function ensureTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(100) PRIMARY KEY,
                `value` TEXT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        } catch (Throwable $e) {
            // Ignorar; se intentará usar migración manual
        }
    }
}


