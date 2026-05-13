<?php
// controllers/ReservationController.php - Contrôleur pour les réservations

require_once __DIR__ . '/../models/Reservation.php';

class ReservationController {
    private $reservationModel;

    public function __construct() {
        $this->reservationModel = new Reservation();
        session_start();
    }

    public function create() {                    // Créer réservation
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $nom = trim($data['nom']);
        $prenom = trim($data['prenom']);
        $email = trim($data['email']);
        $date_reservation = $data['date_reservation'];
        $heure = $data['heure'];
        $joueurs = (int)$data['joueurs'];

        if ($id = $this->reservationModel->create($nom, $prenom, $email, $date_reservation, $heure, $joueurs, $_SESSION['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Réservation créée', 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
    }

    public function get() {                       // Récupérer réservations
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $reservations = $this->reservationModel->getByUserId($_SESSION['user_id']);
        echo json_encode(['success' => true, 'data' => $reservations]);
    }

    public function delete() {                    // Supprimer réservation
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];

        if ($this->reservationModel->delete($id, $_SESSION['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Réservation supprimée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
    }

    public function update() {                       // Modifier réservation
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        $nom = trim($data['nom']);
        $prenom = trim($data['prenom']);
        $email = trim($data['email']);
        $date_reservation = $data['date_reservation'];
        $heure = $data['heure'];
        $joueurs = (int)$data['joueurs'];

        if ($this->reservationModel->update($id, $nom, $prenom, $email, $date_reservation, $heure, $joueurs, $_SESSION['user_id'])) {
            echo json_encode(['success' => true, 'message' => 'Réservation mise à jour']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
    }
}
?>