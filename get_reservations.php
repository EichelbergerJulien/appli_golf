<?php    // get_reservations.php -> Récupérer toutes les réservations au format JSON

session_start();

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Non autorisé"
    ]);
    exit;
}

header('Content-Type: application/json');

// Connexion à la base de données

$conn = new mysqli("localhost", "root", "", "golf_connect");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Connexion échouée"
    ]);
    exit;
}

// Requête SQL pour récupérer les réservations

$sql = "SELECT id, nom, prenom, email, date_reservation, heure, joueurs FROM reservations";
$result = $conn->query($sql);

$reservations = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

// Retour JSON propre

echo json_encode([
    "success" => true,
    "data" => $reservations
]);

$conn->close();
exit;
