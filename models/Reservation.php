<?php
// models/Reservation.php - Modèle pour les réservations

require_once __DIR__ . '/../config/database.php';

class Reservation {
    private mysqli $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }

    public function create(string $nom, string $prenom, string $email, int $date_reservation, int $heure, int $joueurs, int $user_id) {
        $stmt = $this->conn->prepare("INSERT INTO reservations (nom, prenom, email, date_reservation, heure, joueurs, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $nom, $prenom, $email, $date_reservation, $heure, $joueurs, $user_id);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function getByUserId(int $user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY date_reservation, heure");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function delete(int $id, int $user_id) {
        $stmt = $this->conn->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        return $stmt->execute();
    }

    public function update(int $id, string $nom, string $prenom, string $email, int $date_reservation, int $heure, int $joueurs, int $user_id) {
        $stmt = $this->conn->prepare("UPDATE reservations SET nom = ?, prenom = ?, email = ?, date_reservation = ?, heure = ?, joueurs = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssssiii", $nom, $prenom, $email, $date_reservation, $heure, $joueurs, $id, $user_id);
        return $stmt->execute();
    }
}
?>