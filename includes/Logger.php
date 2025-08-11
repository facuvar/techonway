<?php
/**
 * Sistema de Logs para debugging
 */
class Logger {
    private static $logFile = 'logs/system.log';
    private static $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    /**
     * Inicializar sistema de logs
     */
    public static function init() {
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Rotar log si es muy grande
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::$maxFileSize) {
            self::rotateLog();
        }
    }
    
    /**
     * Log de información general
     */
    public static function info($message, $context = []) {
        self::writeLog('INFO', $message, $context);
    }
    
    /**
     * Log de errores
     */
    public static function error($message, $context = []) {
        self::writeLog('ERROR', $message, $context);
    }
    
    /**
     * Log de warnings
     */
    public static function warning($message, $context = []) {
        self::writeLog('WARNING', $message, $context);
    }
    
    /**
     * Log de debugging
     */
    public static function debug($message, $context = []) {
        self::writeLog('DEBUG', $message, $context);
    }
    
    /**
     * Log específico para database
     */
    public static function database($message, $context = []) {
        self::writeLog('DATABASE', $message, $context);
    }
    
    /**
     * Log específico para autenticación
     */
    public static function auth($message, $context = []) {
        self::writeLog('AUTH', $message, $context);
    }
    
    /**
     * Escribir log
     */
    private static function writeLog($level, $message, $context = []) {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'ip' => $ip,
            'request_uri' => $requestUri,
            'user_agent' => substr($userAgent, 0, 100),
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        
        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotar logs cuando son muy grandes
     */
    private static function rotateLog() {
        if (file_exists(self::$logFile)) {
            $backupFile = self::$logFile . '.' . date('Y-m-d_H-i-s') . '.bak';
            rename(self::$logFile, $backupFile);
        }
    }
    
    /**
     * Obtener logs recientes
     */
    public static function getRecentLogs($lines = 100) {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $logs = [];
        $file = file(self::$logFile);
        $totalLines = count($file);
        $startLine = max(0, $totalLines - $lines);
        
        for ($i = $startLine; $i < $totalLines; $i++) {
            $logData = json_decode(trim($file[$i]), true);
            if ($logData) {
                $logs[] = $logData;
            }
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Limpiar logs antiguos
     */
    public static function clearLogs() {
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
    
    /**
     * Registrar error de PHP automáticamente
     */
    public static function logPhpError($errno, $errstr, $errfile = '', $errline = 0) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'ERROR',
            E_NOTICE => 'INFO',
            E_CORE_ERROR => 'ERROR',
            E_CORE_WARNING => 'WARNING',
            E_COMPILE_ERROR => 'ERROR',
            E_COMPILE_WARNING => 'WARNING',
            E_USER_ERROR => 'ERROR',
            E_USER_WARNING => 'WARNING',
            E_USER_NOTICE => 'INFO',
            E_STRICT => 'INFO',
            E_RECOVERABLE_ERROR => 'ERROR',
            E_DEPRECATED => 'INFO',
            E_USER_DEPRECATED => 'INFO'
        ];
        
        $level = $errorTypes[$errno] ?? 'ERROR';
        $message = "PHP {$level}: {$errstr}";
        $context = [
            'error_number' => $errno,
            'file' => $errfile,
            'line' => $errline
        ];
        
        self::writeLog('PHP_' . $level, $message, $context);
        
        // Retornar false para que PHP continue con su manejo normal
        return false;
    }
    
    /**
     * Registrar excepción no capturada
     */
    public static function logException($exception) {
        self::error('Excepción no capturada: ' . $exception->getMessage(), [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    /**
     * Obtener estadísticas de logs
     */
    public static function getStats() {
        if (!file_exists(self::$logFile)) {
            return ['total' => 0, 'size' => 0, 'by_level' => []];
        }
        
        $logs = file(self::$logFile);
        $stats = [
            'total' => count($logs),
            'size' => filesize(self::$logFile),
            'by_level' => []
        ];
        
        foreach ($logs as $line) {
            $logData = json_decode(trim($line), true);
            if ($logData && isset($logData['level'])) {
                $level = $logData['level'];
                $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
}
