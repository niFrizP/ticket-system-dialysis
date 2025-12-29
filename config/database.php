<?php

/**
 * Clase Database - Conexión segura a MySQL con PDO
 * Implementa patrón Singleton para evitar múltiples conexiones
 */
require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $conn = null;

    // Constructor privado (Singleton)
    private function __construct()
    {
        // Cargar configuración desde variables de entorno
        Config::load();

        $host = Config::get('DB_HOST', 'localhost');
        $db_name = Config::get('DB_NAME');
        $username = Config::get('DB_USER');
        $password = Config::get('DB_PASSWORD');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');

        // Validar que las credenciales estén configuradas
        if (!$db_name || !$username || !$password) {
            throw new Exception("Error: Las credenciales de la base de datos no están configuradas correctamente.");
        }

        try {
            $dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
                PDO::ATTR_PERSISTENT         => false // Evitar conexiones persistentes en shared hosting
            ];

            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // NO mostrar detalles del error en producción
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos. Por favor, contacte al administrador.");
        }
    }

    // Obtener instancia única
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Obtener conexión
    public function getConnection()
    {
        return $this->conn;
    }

    // Prevenir clonación
    private function __clone() {}

    // Prevenir deserialización
    public function __wakeup()
    {
        throw new Exception("No se puede deserializar singleton");
    }
}
