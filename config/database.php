<?php

// config/database.php - Configuration de la base de données

class Database {
    private static ?Database $instance = null;
    private mysqli $conn;

    private function __construct() {
        
        $this->conn = new mysqli("localhost", "root", "", "golf_connect");
        if ($this->conn->connect_error) {
            die("Erreur de connexion à la base de données: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8");
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>