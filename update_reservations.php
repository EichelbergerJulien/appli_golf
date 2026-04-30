<?php             // update_reservations.php -> Modifier une réservation existante

session_start();

header("Access-Control-Allow-Origin: http://localhost/appli_golf");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Non autorisé"
    ]);
    exit;
}

require 'vendor/autoload.php';

$conn = new mysqli("localhost", "root", "", "golf_connect");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

//var_dump($data); 


if (
    !isset(
        $data["id"],
        $data["nom"],
        $data["prenom"],
        $data["email"],
        $data["date_reservation"],
        $data["heure"],
        $data["joueurs"]
    )
) {
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit;
}

$id = (int)$data["id"];
$nom = trim($data["nom"]);
$prenom = trim($data["prenom"]);
$email = filter_var($data["email"], FILTER_VALIDATE_EMAIL);
$date = $data["date_reservation"];
$heure = $data["heure"];
$joueurs = (int)$data["joueurs"];

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email invalide"]);
    exit;
}

$stmt = $conn->prepare("UPDATE reservations 
    SET nom=?, prenom=?, email=?, date_reservation=?, heure=?, joueurs=? 
    WHERE id=? AND user_id =?");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Erreur préparation SQL"
    ]);
    exit;
}

$stmt->bind_param("sssssii", $nom, $prenom, $email, $date, $heure, $joueurs, $id, $_SESSION["user_id"]);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => $stmt->affected_rows > 0
            ? "Modifié"
            : "Aucune modification (données identiques)"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Erreur SQL"
    ]);
}

$stmt->close();
$conn->close();
