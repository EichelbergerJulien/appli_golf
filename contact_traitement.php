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

// PHPMailer - Envoyer directement l'email sans passer par la base

$mailSent = false;         // Initialise une variable pour suivre si l'email a été envoyé avec succès
try {  // Essaye d'envoyer un email de notification avec les détails du message de contact, et gère les erreurs potentielles
    $mail = new PHPMailer(true);   // Crée une nouvelle instance de PHPMailer avec la gestion des exceptions activée
    $mailSent = false;         // Initialise une variable pour suivre si l'email a été envoyé avec succès

    //  Vérification des variables d’environnement AVANT config SMTP

    $smtpUser = "julien.e@me.com";  //  Utilisateur SMTP
    $smtpPass = "2145";    //  Mot de passe d'application Gmail

    $mail->isSMTP();   // Configure PHPMailer pour utiliser SMTP pour l'envoi d'emails, ce qui est nécessaire pour envoyer des emails via un serveur SMTP comme Gmail
    $mail->Host = 'smtp.gmail.com';  // Spécifie le serveur SMTP de Gmail pour l'envoi des emails
    $mail->CharSet = 'UTF-8';  // Définit l'encodage des caractères de l'email à UTF-8 pour assurer la compatibilité avec les caractères spéciaux et les accents dans le contenu de l'email
    $mail->SMTPAuth = true;  // Active l'authentification SMTP, ce qui est nécessaire pour se connecter au serveur SMTP de Gmail avec un nom d'utilisateur et un mot de passe
    $mail->Username =  $smtpUser;  // Récupère le nom d'utilisateur SMTP à partir de la variable d'environnement    
    $mail->Password = $smtpPass;

    $mail->SMTPSecure = 'tls';  // Utilise TLS pour sécuriser la connexion au serveur SMTP de Gmail, ce qui est recommandé pour la sécurité de l'envoi d'emails
    $mail->Port = 587;    // Spécifie le port SMTP de Gmail pour TLS (587) pour l'envoi des emails

    $mail->setFrom($smtpUser, 'Contact Golf');
    $mail->addAddress($email);  // Envoie l'email DIRECTEMENT à l'adresse fournie dans le formulaire
    $mail->addReplyTo($smtpUser, 'Support Golf');

    $mail->Subject = "Merci pour votre message de contact";  // Sujet de l'email envoyé au contact
    $mail->isHTML(true);  // Indique que le corps de l'email est au format HTML, ce qui permet d'inclure des balises HTML pour formater le contenu de l'email de manière plus attrayante et lisible
    $mail->Timeout = 10;    // Définit un délai d'attente de 10 secondes pour la connexion au serveur SMTP et l'envoi de l'email, ce qui permet d'éviter que le script ne reste bloqué indéfiniment en cas de problème de connexion ou de serveur lent


    $nomSafe = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');  // Sécurise les données pour l'affichage dans l'email en convertissant les caractères spéciaux en entités HTML, 
                                                             // et en préservant les sauts de ligne dans le message
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
                        ";  // Corps de l'email de notification formaté en HTML pour inclure les détails du message de contact de manière claire et lisible, 
                            // avec des balises HTML pour la mise en forme

    $mail->AltBody = "Nom: $nomSafe\nPrénom: $prenomSafe\nEmail: $emailSafe\nTéléphone: $telSafe\nMessage:\n$messageSafe";  // Corps alternatif de l'email pour les clients de messagerie 
                                                                                // qui ne supportent pas le HTML, formaté en texte brut avec des sauts de ligne pour la lisibilité

    $mail->send();  // Tente d'envoyer l'email et si cela réussit, marque $mailSent comme true
    $mailSent = true;  // Si l'envoi de l'email réussit, la variable $mailSent est définie sur true pour indiquer que l'email a été envoyé avec succès
} catch (Exception $e) {
    $errorMsg = $mail->ErrorInfo ?? $e->getMessage();
    error_log("Mail error: " . $errorMsg);
    respond(false, "Erreur SMTP: " . $errorMsg, 500);
}

if ($mailSent) {  // Si l'email a été envoyé avec succès, répond avec un message de succès
    respond(true, "Message envoyé avec succès !", 200);    // 200 =  OK, Succès HTTP
}

