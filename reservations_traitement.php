<?php     // reservations_traitement.php -> Traiter une nouvelle réservation et envoyer un email de confirmation

session_set_cookie_params([  // Configure les paramètres du cookie de session pour améliorer la sécurité et la gestion des sessions
    'lifetime' => 0,  // Le cookie de session expire lorsque le navigateur est fermé
    'path' => '/',  // Le cookie est disponible sur tout le site
    'secure' => false,  // Le cookie n'est pas limité aux connexions sécurisées (HTTPS). À changer en true en production avec HTTPS
    'httponly' => true,  // Le cookie est inaccessible via JavaScript pour réduire les risques de vol de session
    'samesite' => 'Lax'  // Le cookie est envoyé uniquement pour les requêtes de même site, ce qui aide à prévenir les attaques CSRF
]);

session_start();  // Démarre la session pour accéder à $_SESSION["user_id"] et vérifier l'authentification

header("Access-Control-Allow-Origin: http://localhost/appli_golf");  // Remplacez par l'URL de votre frontend
header("Access-Control-Allow-Credentials: true");  // Permet d'envoyer les cookies de session avec la requête
header("Access-Control-Allow-Headers: Content-Type");  // Permet d'envoyer des requêtes avec le header Content-Type: application/json
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");  // Permet les méthodes POST, GET et OPTIONS pour les requêtes CORS

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200);  // Répond avec un code 200 OK pour les requêtes OPTIONS, qui sont des requêtes préliminaires CORS 
                              // utilisées par le navigateur pour vérifier les permissions avant d'envoyer la requête réelle
    exit;  // Termine le script pour les requêtes OPTIONS, car elles ne nécessitent pas de traitement supplémentaire
}
header("Content-Type: application/json; charset=UTF-8");  // Définit le type de contenu de la réponse comme JSON, 
                                                          //ce qui indique au client que la réponse sera au format JSON

if (!isset($_SESSION["user_id"])) {  // Vérifie si l'utilisateur est authentifié en vérifiant la présence de user_id dans la session
    http_response_code(401);  // Répond avec un code 401 Unauthorized si l'utilisateur n'est pas authentifié
    echo json_encode([  // Envoie une réponse JSON indiquant que l'accès est non autorisé
        "success" => false,  // Indique que l'opération a échoué
        "message" => "Non autorisé"  // Message d'erreur indiquant que l'utilisateur n'est pas autorisé à effectuer cette action
    ]);
    exit;  // Termine le script pour les utilisateurs non authentifiés
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  // Active les rapports d'erreurs MySQLi pour faciliter le débogage 
                                                            // en lançant des exceptions en cas d'erreur de base de données

use PHPMailer\PHPMailer\PHPMailer;  // Importation de la classe PHPMailer pour envoyer des emails
use PHPMailer\PHPMailer\Exception;  // Importation de la classe Exception de PHPMailer pour gérer les erreurs lors de l'envoi d'emails

require 'vendor/autoload.php';  // Charge les dépendances via Composer



$response = [  // Initialise un tableau de réponse qui sera envoyé au client à la fin du script. 
               // Ce tableau contient une clé "success" pour indiquer si l'opération a réussi ou échoué, 
               // et une clé "message" pour fournir des informations supplémentaires.
    "success" => false,
    "message" => ""
];

// Connexion à la base de données

$conn = new mysqli("localhost", "root", "", "golf_connect");  // Connexion à la base de données MySQL avec les paramètres d'hôte, d'utilisateur, 
                                                              // de mot de passe et de nom de base de données

if ($conn->connect_error) {  // Vérifie si la connexion à la base de données a échoué
    $response["message"] = "Erreur connexion BDD"; // Met à jour le message de réponse pour indiquer qu'il y a eu une erreur de connexion 
                                                   // à la base de données
    echo json_encode($response);  // Envoie la réponse JSON au client avec le message d'erreur de connexion à la base de données
    exit;  // Termine le script si la connexion à la base de données a échoué
}

$inputJSON = file_get_contents('php://input');  // Lit les données JSON brutes de la requête POST. 
                                                // php://input est un flux qui permet de lire les données brutes de la requête, 
                                                // ce qui est nécessaire pour traiter les données JSON envoyées par le client   
$input = json_decode($inputJSON, TRUE);    //convert JSON into array


if ($_SERVER['REQUEST_METHOD'] === 'POST') {  // Vérifie que la requête est de type POST, ce qui est attendu pour créer une nouvelle réservation

    // Sécurisation

    $nom = isset($input['nom']) ? htmlspecialchars(trim($input['nom'])) : '';   // Vérifie si le champ "nom" est présent dans les données d'entrée, le nettoie en supprimant les espaces 
                                                                                // et en convertissant les caractères spéciaux en entités HTML pour éviter les attaques XSS. 
                                                                                // Si le champ n'est pas présent, il est défini comme une chaîne vide.
    $prenom = isset($input['prenom']) ? htmlspecialchars(trim($input['prenom'])) : '';
    $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : false;
    $date = $input['date_reservation'] ?? '';
    $heure = $input['heure'] ?? '';
    $joueurs = isset($input['joueurs']) ? intval($input['joueurs']) : 0;

    if (!$nom || !$prenom || !$email || !$date || !$heure || $joueurs <= 0) {  // Vérifie que tous les champs nécessaires sont présents et valides. 
                                                                               // Si l'un des champs est manquant ou invalide, une réponse JSON est envoyée au client 
                                                                               // avec un message d'erreur indiquant que les champs sont invalides ou incomplets, et le script est terminé.
        $response["message"] = "Champs invalides ou incomplets";  // Met à jour le message de réponse pour indiquer que les champs sont invalides ou incomplets
        echo json_encode($response);  // Envoie la réponse JSON au client avec le message d'erreur de champs invalides ou incomplets
        exit;
    }

    $check = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE date_reservation=? AND heure=?");  
                            //  Prépare une requête SQL pour vérifier si un créneau de réservation existe déjà pour la date et l'heure spécifiées. 
                            // La requête utilise des paramètres liés pour éviter les injections SQL.

    if (!$check) {  // Vérifie si la préparation de la requête SQL a échoué. Si c'est le cas, 
                    // une réponse JSON est envoyée au client avec un message d'erreur indiquant 
                    //qu'il y a eu une erreur lors de la préparation de la requête SQL, et le script est terminé.
        $response["message"] = "Erreur SQL check";  // Met à jour le message de réponse pour indiquer qu'il y a eu une erreur 
                                                    // lors de la préparation de la requête SQL
        echo json_encode($response);  // Envoie la réponse JSON au client avec le message d'erreur de préparation de la requête SQL
        exit;  // Termine le script si la préparation de la requête SQL a échoué
    }

    $check->bind_param("ss", $date, $heure);  // Lie les paramètres de la requête SQL préparée avec les variables $date et $heure.
    if (!$check->execute()) {
        $response["message"] = "Erreur SQL check";
        echo json_encode($response);
        exit;
    }

    $check->bind_result($total);  // Lie le résultat de la requête SQL à la variable $total
    $check->fetch();  // Exécécute la requête SQL et récupère le résultat

    if ($total > 0) {  // Vérifie si le nombre de réservations existantes pour le créneau spécifié est supérieur à 0. 
                    // Si c'est le cas, cela signifie que le créneau est déjà réservé, et une réponse JSON est envoyée au client avec 
                    // un message d'erreur indiquant que le créneau est déjà réservé, et le script est terminé.
        $response["message"] = "Créneau déjà réservé";  // Met à jour le message de réponse pour indiquer que le créneau est déjà réservé
        echo json_encode($response);  // Envoie la réponse JSON au client avec le message d'erreur de créneau déjà réservé
        exit;  // Termine le script si le créneau est déjà réservé
    }

    $check->close();  // Ferme la requête préparée pour libérer les ressources associées à cette requête

    // INSERT requete SQL dans la BDD

    $stmt = $conn->prepare("INSERT INTO reservations (nom, prenom, email, date_reservation, heure, joueurs) 
        VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $response["message"] = "Erreur SQL prepare";
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("sssssi", $nom, $prenom, $email, $date, $heure, $joueurs);

    if ($stmt->execute()) {

        $response["success"] = true;
        $response["id"] = $conn->insert_id;
        $response["message"] = "Réservation enregistrée";

        try {
            // Charger les variables d'environnement depuis le fichier .env
            $envFile = __DIR__ . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                        list($key, $value) = explode('=', $line, 2);
                        putenv(trim($key) . '=' . trim($value));
                    }
                }
            }
            
            $mail = new PHPMailer(true);

            // Configuration SMTP - Variables d'environnement sécurisées
            $smtpUser = getenv('SMTP_USER');
            $smtpPass = getenv('SMTP_PASS');
            
            if (!$smtpUser || !$smtpPass) {
                throw new Exception('Configuration SMTP manquante dans .env');
            }
            
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet = 'UTF-8';
            $mail->Port = 587;

            $mail->setFrom($smtpUser, 'Golf Connect');

            $mail->addAddress($email, "$prenom $nom");
            $mail->addAddress("contact@golfdecherisey.fr", 'Golf Connect');

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

            $mail->send();
            $response["message"] = "Réservation enregistrée et email envoyé";
            
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            error_log("Exception: " . $e->getMessage());
            
            $response["success"] = true;
            $response["id"] = $conn->insert_id;
            $response["message"] = "Réservation enregistrée mais erreur envoi email";
        }
    } else {
        $response["message"] = "Erreur SQL execute"; // Met à jour le message de réponse pour indiquer qu'il y a eu une erreur lors de l'exécution de la requête SQL d'insertion
    }
} else {
    $response["message"] = "Méthode non autorisée"; // Met à jour le message de réponse pour indiquer que la méthode HTTP utilisée n'est pas autorisée 
                                                    // (seules les requêtes POST sont autorisées pour créer une réservation)
}
echo json_encode($response);  // Envoie la réponse JSON au client avec le résultat de l'opération, y compris si elle a réussi ou échoué, 
                              // et un message d'information supplémentaire pour aider le client à comprendre le résultat de sa requête

