<?php     // reservations_traitement.php -> Traiter une nouvelle réservation et envoyer un email de confirmation

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  // Active les rapports d'erreurs MySQLi pour faciliter le débogage 
// en lançant des exceptions en cas d'erreur de base de données

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$response = [
    "success" => false,
    "message" => ""
];

// Connexion à la base de données

$conn = new mysqli("localhost", "root", "", "golf_connect");

if ($conn->connect_error) {
    $response["message"] = "Erreur connexion BDD";
    echo json_encode($response);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);    //convert JSON into array


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sécurisation

    $nom = isset($input['nom']) ? htmlspecialchars(trim($input['nom'])) : '';
    $prenom = isset($input['prenom']) ? htmlspecialchars(trim($input['prenom'])) : '';
    $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : false;
    $date = $input['date_reservation'] ?? '';
    $heure = $input['heure'] ?? '';
    $joueurs = isset($input['joueurs']) ? intval($input['joueurs']) : 0;

    if (!$nom || !$prenom || !$email || !$date || !$heure || $joueurs <= 0) {
        $response["message"] = "Champs invalides ou incomplets";
        echo json_encode($response);
        exit;
    }

    $check = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE date_reservation=? AND heure=?");

    if (!$check) {
        $response["message"] = "Erreur SQL check";
        echo json_encode($response);
        exit;
    }

    $check->bind_param("ss", $date, $heure);

    $check->bind_result($total);
    $check->fetch();

    if ($total > 0) {
        $response["message"] = "Créneau déjà réservé";
        echo json_encode($response);
        exit;
    }

    $check->close();

    // INSERT requete SQL dans la BDD

    $stmt = $conn->prepare("INSERT INTO reservations (nom, prenom, email, date_reservation, heure, joueurs, user_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $response["message"] = "Erreur SQL prepare";
        echo json_encode($response);
        exit;
    }

    $user_id = $_SESSION["user_id"];

    $stmt->bind_param("sssssii", $nom, $prenom, $email, $date, $heure, $joueurs, $user_id);

    if ($stmt->execute()) {

        $response["success"] = true;
        $response["id"] = $conn->insert_id;  // Retourner l'ID de la réservation créée
        $response["message"] = "Réservation enregistrée et email en cours d'envoi";


        try {
            $mail = new PHPMailer(true);

            // PHPMailer

            $smtpUser = "tonemail@gmail.com";
            $smtpPass = "ton_mot_de_passe_app";    //  Mot de passe d'application Gmail

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username =  $smtpUser;
            $mail->Password =  $smtpPass;    // ⚠️ IMPORTANT !!

            $mail->SMTPSecure = 'tls';
            $mail->CharSet = 'UTF-8';
            $mail->Port = 587;

            $mail->setFrom('tonemail@gmail.com', 'Golf Connect');

            // Client

            $mail->addAddress($email, "$prenom $nom");

            // Golf

            $mail->addAddress("contact@golfdecherisey.fr");

            $mail->isHTML(true);
            $mail->Subject = "Confirmation de votre réservation";

            $mail->Body = "
                <h2>Bonjour $prenom $nom,</h2>
                <p>Votre réservation est confirmée :</p>
                <ul>
                    <li><strong>Date :</strong> $date</li>
                    <li><strong>Heure :</strong> $heure</li>
                    <li><strong>Joueurs :</strong> $joueurs</li>
                </ul>
                <p>Cordialement,<br>Golf Connect</p>
            ";

          //  $mail->send();
        } catch (Exception $e) {
            error_log($e->getMessage());

            $response["success"] = true;
            $response["id"] = $conn->insert_id;
            $response["message"] = "Réservation enregistrée";
        }
    } else {
        $response["message"] = "Erreur SQL execute";
    }
} else {
    $response["message"] = "Méthode non autorisée";
}
echo json_encode($response);
