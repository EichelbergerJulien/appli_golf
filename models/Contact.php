<?php
// models/Contact.php - Modèle pour les messages de contact

require_once __DIR__ . '/../config/database.php';

class Contact {
    private mysqli $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create(string $nom, string $prenom, string $email, string $telephone, string $message) {
        $stmt = $this->conn->prepare("INSERT INTO contact (nom, prenom, email, telephone, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $prenom, $email, $telephone, $message);
        return $stmt->execute();
    }
}
?>