<?php     // contact_traitement.php -> Traiter un message de contact et envoyer un email de notification

ini_set('display_errors', 1);    // Active et affiche les erreurs PHP
error_reporting(E_ALL);          // Affiche tous les types d'erreurs

use PHPMailer\PHPMailer\PHPMailer;  // Importation des classes PHPMailer
use PHPMailer\PHPMailer\Exception;  // Importation des classes PHPMailer

require 'vendor/autoload.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {     // Vérifie que la requête est une requête POST, sinon répond avec une erreur 
    respond(false, "Méthode non autorisée", 405);   // 405 = Method Not Allowed, Méthode non autorisée
}
if (!isset($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['tel'], $_POST['message'])) {    // Vérifie que tous les champs requis sont présents dans la requête POST, sinon répond avec une erreur
    respond(false, "Données manquantes", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

// Sécurisation

$nom = trim($_POST['nom']);  // Supprime les espaces inutiles au début et à la fin du nom
$prenom = trim($_POST['prenom']);
$emailRaw = trim($_POST['email']);

if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {  // Vérifie que l'email est valide, sinon répond avec une erreur     
    respond(false, "Email invalide", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

$email = $emailRaw;  // Si l'email est valide, on peut l'utiliser tel quel, sinon on aurait répondu avec une erreur.
$tel = trim($_POST['tel']);  // Supprime les espaces inutiles au début et à la fin du numéro de téléphone
$message = trim($_POST['message']);  // Supprime les espaces inutiles au début et à la fin du message, et préserve les sauts de ligne pour l'affichage dans l'email

if (!preg_match('/^\+?[0-9\s().-]{6,20}$/', $tel)) {  // Vérifie que le numéro de téléphone est valide (6 à 20 caractères, chiffres, espaces, parenthèses, points ou tirets, avec un éventuel + au début)
    respond(false, "Téléphone invalide", 400);      // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

if (empty($nom) || empty($prenom) || empty($message) || empty($email) || empty($tel)) {  // Vérifie que les champs nom, prénom, message, email et téléphone ne sont pas vides, sinon répond avec une erreur
    respond(false, "Veuillez remplir tous les champs correctement", 400);       // 400 = Bad Request, Requête incorrecte (erreur côté utilisateur)
}

// Récupérer le destinataire depuis .env
$recipient = getEnvOrFail('SMTP_RECIPIENT');

// Sécurisation des données pour l'email
$nomSafe = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
$prenomSafe = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
$emailSafe = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$telSafe = htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
$messageSafe = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

// PHPMailer - Sauvegarde en base et envoi de l'email

$conn = new mysqli("localhost", "root", "", "golf_connect");
if ($conn->connect_error) {
    respond(false, "Erreur connexion BDD", 500);
}

$stmt = $conn->prepare("INSERT INTO contact (nom, prenom, email, telephone, message) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log("Contact insert prepare error: " . $conn->error);
    respond(false, "Erreur base de données", 500);
}
$stmt->bind_param("sssss", $nom, $prenom, $email, $tel, $message);
if (!$stmt->execute()) {
    error_log("Contact insert execute error: " . $stmt->error);
    respond(false, "Erreur base de données", 500);
}
$stmt->close();

$mailSent = false;

try {
    $mail = new PHPMailer(true);
    
    // Configuration SMTP - Variables d'environnement sécurisées
    $smtpUser = getEnvOrFail('SMTP_USER');
    $smtpPass = getEnvOrFail('SMTP_PASS');
    $recipient = getEnvOrFail('SMTP_RECIPIENT');
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->CharSet = 'UTF-8';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom($smtpUser, 'Club de Golf de Chérisey');
    $mail->addAddress($recipient, 'Club de Golf de Chérisey');
    $mail->addAddress($email, "$prenomSafe $nomSafe");
    $mail->addReplyTo($smtpUser, 'Club de Golf de Chérisey');
    $mail->Subject = "Nouveau message depuis le formulaire de contact";
    $mail->isHTML(true);
    
    $mail->Body = "
        <h2>Nouveau message de contact</h2>
        <p><strong>Nom :</strong> $nomSafe</p>
        <p><strong>Prénom :</strong> $prenomSafe</p>
        <p><strong>Email :</strong> $emailSafe</p>
        <p><strong>Téléphone :</strong> $telSafe</p>
        <p><strong>Message :</strong><br>$messageSafe</p>
    ";
    $mail->AltBody = "Nom: $nomSafe\nPrénom: $prenomSafe\nEmail: $emailSafe\nTéléphone: $telSafe\nMessage:\n$messageSafe";
    
    $mail->send();
    $mailSent = true;
    
} catch (Exception $e) {
    error_log("Mail error: " . $mail->ErrorInfo);
    error_log("Exception: " . $e->getMessage());
}

$conn->close();

if ($mailSent) {
    respond(true, "Message envoyé et enregistré en base de données.", 200);
}

// Le message est en BDD même si le mail a échoué - c'est un succès partiel

respond(true, "Message enregistré en base de données. Mail: échec SMTP (vérifiez les identifiants et les logs).", 200);


header("Location: contact.html");
exit();
