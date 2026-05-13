<?php
// models/User.php - Modèle pour les utilisateurs

require_once __DIR__ . '/../config/database.php';

class User {
    private mysqli $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function register($nom, $prenom, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ssss", $nom, $prenom, $email, $hashedPassword);
        return $stmt->execute();
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT nom, prenom, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT id, nom, prenom, email FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>