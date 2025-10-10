<?php
/**
 * Clase Database - Conexión segura a MySQL con PDO
 * Implementa patrón Singleton para evitar múltiples conexiones
 */
class Database {
    private static $instance = null;
    private $conn = null;
    
    // Configuración (mejor aún: usar variables de entorno)
    private $host = 'localhost';
    private $db_name = 'teqmedcl_intranet';
    private $username = 'teqmedcl_intranet';
    private $password = 'KSzZhsYHE#xK';
    private $charset = 'utf8mb4';
    
    // Constructor privado (Singleton)
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT         => false // Evitar conexiones persistentes en shared hosting
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // NO mostrar detalles del error en producción
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos. Por favor, contacte al administrador.");
        }
    }
    
    // Obtener instancia única
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Obtener conexión
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("No se puede deserializar singleton");
    }
}
?>