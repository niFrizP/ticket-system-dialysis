<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'teqmedcl_intranet';
    private $username = 'tu_usuario';
    private $password = 'tu_password';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error al conectar con la base de datos. Por favor, intente más tarde.");
        }

        return $this->conn;
    }

    public function generateTicketNumber() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $ticketNumber = '';
        
        do {
            $ticketNumber = 'TKT-';
            for ($i = 0; $i < 6; $i++) {
                $ticketNumber .= $characters[random_int(0, strlen($characters) - 1)];
            }
            
            // Verificar que no exista
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM tickets WHERE numero_ticket = ?");
            $stmt->execute([$ticketNumber]);
            $exists = $stmt->fetchColumn() > 0;
            
        } while ($exists);
        
        return $ticketNumber;
    }
}
?>