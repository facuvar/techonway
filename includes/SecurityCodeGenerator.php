<?php
/**
 * Generador de códigos de seguridad para tickets
 * 
 * Esta clase se encarga de generar códigos únicos de 4 dígitos
 * para validar la identidad del técnico ante el cliente
 */
class SecurityCodeGenerator {
    
    /**
     * Genera un código de seguridad único de 4 dígitos
     * 
     * @return string Código de 4 dígitos
     */
    public static function generate() {
        // Generar un número aleatorio de 4 dígitos (1000-9999)
        return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Genera un código de seguridad único verificando que no exista en la base de datos
     * 
     * @param Database $db Instancia de la base de datos
     * @param int|null $excludeTicketId ID del ticket a excluir de la verificación (para ediciones)
     * @return string Código único de 4 dígitos
     */
    public static function generateUnique($db, $excludeTicketId = null) {
        $maxAttempts = 100; // Límite de intentos para evitar bucles infinitos
        $attempts = 0;
        
        do {
            $code = self::generate();
            $attempts++;
            
            // Verificar si el código ya existe
            $query = "SELECT id FROM tickets WHERE security_code = ?";
            $params = [$code];
            
            if ($excludeTicketId) {
                $query .= " AND id != ?";
                $params[] = $excludeTicketId;
            }
            
            $existingTicket = $db->selectOne($query, $params);
            
            // Si no existe o llegamos al límite de intentos, usar este código
            if (!$existingTicket || $attempts >= $maxAttempts) {
                break;
            }
            
        } while ($attempts < $maxAttempts);
        
        return $code;
    }
    
    /**
     * Valida que un código de seguridad tenga el formato correcto
     * 
     * @param string $code Código a validar
     * @return bool True si el código es válido
     */
    public static function isValid($code) {
        return preg_match('/^\d{4}$/', $code);
    }
    
    /**
     * Verifica si un código de seguridad coincide con el de un ticket
     * 
     * @param Database $db Instancia de la base de datos
     * @param int $ticketId ID del ticket
     * @param string $code Código a verificar
     * @return bool True si el código coincide
     */
    public static function verify($db, $ticketId, $code) {
        if (!self::isValid($code)) {
            return false;
        }
        
        $ticket = $db->selectOne(
            "SELECT security_code FROM tickets WHERE id = ?",
            [$ticketId]
        );
        
        return $ticket && $ticket['security_code'] === $code;
    }
}
