<?php            // delete_reservations.php -> Effacer une réservation

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


// Connexion à la base de données

$host = "localhost";
$dbname = "golf_connect";          // ⚠️ à adapter
$user = "root";            // ⚠️ à adapter
$pass = "";                // ⚠️ à adapter

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "❌ Connexion DB échouée"
    ]);
    exit;
}

// Récupération du JSON envoyé

$data = json_decode(file_get_contents("php://input"), true);

$id = (int) $data["id"];

// Vérification

if (!isset($data["id"]) || !is_numeric($data["id"])) {
    echo json_encode([
        "success" => false,
        "message" => "❌ ID invalide"
    ]);
    exit;
}

$reservation_id = (int)$data["id"];

try {
    // Préparation requête sécurisée

    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id=? AND user_id =?");
    $stmt->execute([$reservation_id, $_SESSION["user_id"]]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Réservation supprimée"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "❌ Aucune réservation trouvée"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "❌ Erreur serveur"
    ]);
}

