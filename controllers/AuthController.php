<?php
// controllers/AuthController.php - Contrôleur pour l'authentification

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    private function outputJson(bool $success, string $message, string $redirect = null): void
    {
        header('Content-Type: application/json');
        $payload = ['success' => $success, 'message' => $message];
        if ($redirect !== null) {
            $payload['redirect'] = $redirect;
        }
        echo json_encode($payload);
        exit;
    }

    public function register()     // Registre d'un nouvel user
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../register');
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $nom = trim($input['nom'] ?? '');
        $prenom = trim($input['prenom'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $confirm_password = $input['confirm_password'] ?? '';

        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            if (!$this->isJsonRequest()) {
                header('Location: ../register');
                exit;
            }
            $this->outputJson(false, 'Tous les champs sont requis.');
        }

        if ($password !== $confirm_password) {
            if (!$this->isJsonRequest()) {
                header('Location: ../register');
                exit;
            }
            $this->outputJson(false, 'Les mots de passe ne correspondent pas.');
        }

        if ($this->userModel->register($nom, $prenom, $email, $password)) {
            if (!$this->isJsonRequest()) {
                header('Location: ../login');
                exit;
            }
            $this->outputJson(true, 'Inscription réussie.', '../login');
        }

        if (!$this->isJsonRequest()) {
            header('Location: ../register');
            exit;
        }
        $this->outputJson(false, 'Erreur lors de l\'inscription.');
    }

    public function login()          // Connexion de l'utilisateur
    {                       
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../login');
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $email = trim($input['login_email'] ?? $input['email'] ?? '');
        $password = $input['login_password'] ?? $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            if (!$this->isJsonRequest()) {
                header('Location: ../login');
                exit;
            }
            $this->outputJson(false, 'Email et mot de passe requis.');
        }

        $user = $this->userModel->login($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            if (!$this->isJsonRequest()) {
                header('Location: ../reservations');
                exit;
            }
            $this->outputJson(true, 'Connexion réussie.', '../reservations');
        }

        if (!$this->isJsonRequest()) {
            header('Location: ../login');
            exit;
        }
        $this->outputJson(false, 'Email ou mot de passe incorrect.');
    }

    public function logout()           // Déconnecter l'utilisateur en détruisant la session 
    {              
        session_destroy();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->outputJson(true, 'Déconnecté.');
        }

        header('Location: .');
        exit;
    }

    public function checkAuth()
    {
        header('Content-Type: application/json');
        echo json_encode(['logged_in' => isset($_SESSION['user_id'])]);
        exit;
    }
}
