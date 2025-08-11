<?php
/**
 * Authentication class
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $password, $role) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? AND role = ?",
            [$email, $role]
        );
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            $this->setSession($user);
            Logger::auth('Login exitoso', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return true;
        }
        
        Logger::auth('Login fallido', [
            'email' => $email,
            'role' => $role,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_found' => $user ? 'yes' : 'no'
        ]);
        return false;
    }
    
    public function register($userData) {
        // Hash password
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            $this->setSession($user);
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        Logger::auth('Logout de usuario', [
            'user_id' => $_SESSION['user_id'] ?? 'unknown',
            'email' => $_SESSION['user_email'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        session_start();
        session_unset();
        session_destroy();
        
        // Redirect to login page
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    
    private function setSession($user) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($user['role'] === 'technician') {
            $_SESSION['user_zone'] = $user['zone'];
        }
    }
    
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === 'admin';
    }
    
    public function isTechnician() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === 'technician';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            if (!headers_sent()) {
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            } else {
                echo '<script>window.location.href = "' . BASE_URL . 'login.php";</script>';
                echo '<meta http-equiv="refresh" content="0;url=' . BASE_URL . 'login.php">';
                exit;
            }
        }
    }
    
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            if (!headers_sent()) {
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            } else {
                echo '<script>window.location.href = "' . BASE_URL . 'login.php";</script>';
                echo '<meta http-equiv="refresh" content="0;url=' . BASE_URL . 'login.php">';
                exit;
            }
        }
    }
    
    public function requireTechnician() {
        if (!$this->isTechnician()) {
            if (!headers_sent()) {
                header('Location: ' . BASE_URL . 'login.php');
                exit;
            } else {
                echo '<script>window.location.href = "' . BASE_URL . 'login.php";</script>';
                echo '<meta http-equiv="refresh" content="0;url=' . BASE_URL . 'login.php">';
                exit;
            }
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
}
