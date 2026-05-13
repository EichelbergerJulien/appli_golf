<?php
// controllers/ContactController.php - Contrôleur pour Traiter les messages de contact

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/Contact.php';

class ContactController {
    private $contactModel;

    public function __construct() {
        $this->contactModel = new Contact();
    }

    private function getEnvOrFail($key) {
        $value = getenv($key);
        if (!$value) {
            error_log("Missing env: $key");
            $this->respond(false, "Erreur serveur", 500);
        }
        return $value;
    }

    private function respond($success, $message, $code = 200) {
        http_response_code($code);
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    public function send() {
        header('Content-Type: application/json; charset=UTF-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(false, "Méthode non autorisée", 405);
        }

        if (!isset($_POST['nom'], $_POST['prenom'], $_POST['email'], $_POST['tel'], $_POST['message'])) {
            $this->respond(false, "Données manquantes", 400);
        }

        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $emailRaw = trim($_POST['email']);

        if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
            $this->respond(false, "Email invalide", 400);
        }

        $email = $emailRaw;
        $tel = trim($_POST['tel']);
        $message = trim($_POST['message']);

        if (!preg_match('/^\+?[0-9\s().-]{6,20}$/', $tel)) {
            $this->respond(false, "Téléphone invalide", 400);
        }

        if (empty($nom) || empty($prenom) || empty($message) || empty($email) || empty($tel)) {
            $this->respond(false, "Veuillez remplir tous les champs correctement", 400);
        }

        // Sauvegarde en base
        if (!$this->contactModel->create($nom, $prenom, $email, $tel, $message)) {
            $this->respond(false, "Erreur base de données", 500);
        }

        // Envoi email
        $mailSent = $this->sendEmail($nom, $prenom, $email, $tel, $message);

        if ($mailSent) {
            $this->respond(true, "Message envoyé et enregistré en base de données.", 200);
        } else {
            $this->respond(true, "Message enregistré en base de données. Mail: échec SMTP (vérifiez les identifiants et les logs).", 200);
        }
    }

    private function sendEmail($nom, $prenom, $email, $tel, $message) {
        $messageAlt = str_replace(["\r\n", "\r"], "\n", $message);
        $messageAlt = htmlspecialchars($messageAlt, ENT_QUOTES, 'UTF-8');

        try {
            $mail = new PHPMailer(true);

            $smtpUser = $this->getEnvOrFail('SMTP_USER');
            $smtpPass = $this->getEnvOrFail('SMTP_PASS');
            $recipient = $this->getEnvOrFail('SMTP_RECIPIENT');

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
            $mail->addReplyTo($email, "$prenom $nom");
            $mail->Subject = "Nouveau message depuis le formulaire de contact";
            $mail->isHTML(true);

            $nomSafe = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
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
            $mail->AltBody = "Nom: $nomSafe\nPrénom: $prenomSafe\nEmail: $emailSafe\nTéléphone: $telSafe\nMessage:\n$messageAlt";

            $mail->send();
            return true;

        } catch (Exception $e) {
            if (isset($mail)) {
                error_log("Mail error: " . $mail->ErrorInfo);
            }
            error_log("Exception: " . $e->getMessage());
            return false;
        }
    }
}
?>