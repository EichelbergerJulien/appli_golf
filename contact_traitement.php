<?php     // contact_traitement.php -> Traiter un message de contact et envoyer un email de notification

ini_set('display_errors', 1);    // Active et affiche les erreurs PHP
error_reporting(E_ALL);          // Affiche tous les types d'erreurs

use PHPMailer\PHPMailer\PHPMailer;  // Importation des classes PHPMailer
use PHPMailer\PHPMailer\Exception;  // Importation des classes PHPMailer

require 'vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');  // Définit le type de contenu et l'encodage pour éviter les problèmes d'affichage des caractères spéciaux

//  function respond()

function respond(bool $success, string $message, int $code = 200): void  // 200 =  OK, Succès HTTP
{
    http_response_code($code);  // Définit le code de réponse HTTP
    echo json_encode([          // Retourne une réponse JSON structurée avec succès, message et code
        "success" => $success,
        "message" => $message
    ]);
    exit;
}

// function getEnvOrFail() pour récupérer les variables d’environnement et gérer les erreurs de manière centralisée

function getEnvOrFail(string $key): string   // Récupère une variable d'environnement ou répond avec une erreur si elle est manquante
{
    $value = getenv($key);     // Récupère la valeur de la variable d'environnement spécifiée par $key
    if (!$value) {             // Si la variable d'environnement n'est pas définie ou est vide, log une erreur et répond avec un message d'erreur
        error_log("Missing env: $key");     // Log l'erreur pour le développement et le débogage
        respond(false, "Erreur serveur", 500);   // 500 = Internal Server Error, Erreur interne du serveur
    }
    return $value;    // Retourne la valeur de la variable d'environnement
}

// Connexion à la base de données

$conn = new mysqli("localhost", "root", "", "golf_connect");    //  à adapter
$conn->set_charset("utf8mb4");     // Définit l'encodage pour éviter les problèmes d'affichage des caractères spéciaux

if ($conn->connect_error) {   // Vérifie la connexion à la base de données et répond avec une erreur si la connexion échoue
    $conn->close();   // Ferme la connexion à la base de données avant de répondre

    respond(false, "Erreur serveur", 500);  // 500 = Internal Server Error, Erreur interne du serveur
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {     // Vérifie que la requête est une requête POST, sinon répond avec une erreur 
    $conn->close();     // Ferme la connexion à la base de données avant de répondre

    respond(false, "Méthode non autorisée", 405);   // 405 = Method Not Allowed, Méthode non autorisée
}
if (!isset($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['tel'], $_POST['message'])) {    // Vérifie que tous les champs requis sont présents dans la requête POST, sinon répond avec une erreur
    $conn->close();   // Ferme la connexion à la base de données avant de répondre

    respond(false, "Données manquantes", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

// Sécurisation

$nom = trim($_POST['nom']);  // Supprime les espaces inutiles au début et à la fin du nom
$prenom = trim($_POST['prenom']);
$emailRaw = trim($_POST['email']);

if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {  // Vérifie que l'email est valide, sinon répond avec une erreur     
    $conn->close();  // Ferme la connexion à la base de données avant de répondre
    respond(false, "Email invalide", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

$email = $emailRaw;
$tel = trim($_POST['tel']);
$message = trim($_POST['message']);

if (!preg_match('/^\+?[0-9\s().-]{6,20}$/', $tel)) {  // Vérifie que le numéro de téléphone est valide (6 à 20 caractères, chiffres, espaces, parenthèses, points ou tirets, avec un éventuel + au début)
    $conn->close();  // Ferme sla connexion à la base de données avant de répondre
    respond(false, "Téléphone invalide", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

if (empty($nom) || empty($prenom) || empty($message) || empty($email) || empty($tel)) {  // Vérifie que les champs nom, prénom, message, email et téléphone ne sont pas vides, sinon répond avec une erreur
    $conn->close();
    respond(false, "Veuillez remplir tous les champs correctement", 400);       // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

// INSERT requete SQL dans la BDD

$stmt = $conn->prepare("INSERT INTO contact (nom, prenom, email, telephone, message) VALUES (?, ?, ?, ?, ?)");  // Prépare une requête SQL pour insérer les données de contact dans la table "contact" de la base de données 

if (!$stmt) {  // Vérifie que la préparation de la requête SQL a réussi, sinon ferme la connexion et répond avec une erreur
    $conn->close();

    respond(false, "Erreur serveur", 500);     // 500 = Internal Server Error, Erreur interne du serveur
}

$stmt->bind_param("sssss", $nom, $prenom, $email, $tel, $message);  // Lie les paramètres de la requête SQL aux variables correspondantes (s = string, i = integer, etc.)

if (!$stmt->execute()) {  // Exécute la requête SQL et vérifie si elle a réussi, sinon ferme la connexion et répond avec une erreur
    $stmt->close();   // Ferme la requête préparée
    $conn->close();   // Ferme la connexion à la base de données

    respond(false, "Erreur lors de l'enregistrement", 500);    // 500 = Internal Server Error, Erreur interne du serveur
}

// PHPMailer

$mailSent = false;         // Initialise une variable pour suivre si l'email a été envoyé avec succès
try {  // Essaye d'envoyer un email de notification avec les détails du message de contact, et gère les erreurs potentielles
    $mail = new PHPMailer(true);   // Crée une nouvelle instance de PHPMailer avec la gestion des exceptions activée
    $mailSent = false;         // Initialise une variable pour suivre si l'email a été envoyé avec succès

    //  Vérification des variables d’environnement AVANT config SMTP

    $smtpUser = "tonemail@gmail.com";  //  Utilisateur SMTP
    $smtpPass = "ton_mot_de_passe_app";    //  Mot de passe d'application Gmail

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->CharSet = 'UTF-8';
    $mail->SMTPAuth = true;
    $mail->Username =  $smtpUser;  // Récupère le nom d'utilisateur SMTP à partir de la variable d'environnement    
    $mail->Password = $smtpPass;

    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom($smtpUser, 'Contact Golf');
    $mail->addAddress('contact@golfdecherisey.fr');  // Adresse de destination pour les notifications de contact

    $mail->Subject = "Nouveau message de contact";
    $mail->isHTML(true);
    $mail->Timeout = 10;


    $nomSafe = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');  // Sécurise les données pour l'affichage dans l'email en convertissant les caractères spéciaux en entités HTML, et en préservant les sauts de ligne dans le message
    $prenomSafe = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
    $emailSafe = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $telSafe = htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
    $messageSafe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $mail->Body = "
                        <h2>Nouveau message de contact</h2>
                        <p><strong>Nom :</strong> $nomSafe</p>
                        <p><strong>Prénom :</strong> $prenomSafe</p>
                        <p><strong>Email :</strong> $emailSafe</p>
                        <p><strong>Téléphone :</strong> $telSafe</p>
                        <p><strong>Message :</strong><br>$messageSafe</p>
                        ";

    $mail->AltBody = "Nom: $nomSafe\nPrénom: $prenomSafe\nEmail: $emailSafe\nTéléphone: $telSafe\nMessage:\n$messageSafe";

    $mail->send();  // Tente d'envoyer l'email et si cela réussit, marque $mailSent comme true
    $mailSent = true;
} catch (Exception $e) {
    error_log("Mail error: " . $e->getMessage()); // Log l'erreur d'envoi d'email pour le développement et le débogage, mais ne pas échouer la requête de contact car l'enregistrement en base de données a réussi
}

$stmt->close();  // Ferme la requête préparée
$conn->close();  // Ferme la connexion à la base de données

if ($mailSent) {
    respond(true, "Message envoyé avec succès !", 200);    // 200 =  OK, Succès HTTP
} else {

    respond(true, "Message reçu (email en cours de traitement) !", 202);   // 202 = Accepted, Requête acceptée mais pas encore traitée
}

