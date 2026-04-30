<?php                        // login.php -> Authentifier un utilisateur et démarrer une session

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,      // true si HTTPS
    'httponly' => true,
    'samesite' => 'Lax'    // 🔥 ou 'None' si cross-origin strict
]);

session_start();

error_reporting(0);
ini_set('display_errors', 0);

$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;

header("Access-Control-Allow-Origin: http://localhost/appli_golf");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header("Content-Type: application/json; charset=UTF-8");

function respond(bool $success, string $message, int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit;
}

// DB

$conn = new mysqli("localhost", "root", "", "golf_connect");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    respond(false, "Erreur serveur", 500);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    respond(false, "JSON invalide", 400);
}

$email = trim($data["login_email"] ?? "");
$password = trim($data["login_password"] ?? "");

if (!$email || !$password) {
    respond(false, "Champs requis manquants", 400);
}

$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");

if (!$stmt) {
    respond(false, "Erreur SQL prepare", 500);
}

$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    respond(false, "Erreur SQL execute", 500);
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user["password"])) {

    password_hash("123456", PASSWORD_DEFAULT);  // Juste pour forcer le chargement de la fonction password_hash et éviter un potentiel délai lors de la première 
                                                // connexion d'un utilisateur (car PHP doit charger les librairies de hashage en arrière-plan)
    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
 
    respond(true, "Connexion réussie");
} else {
    respond(false, "Identifiants invalides", 401);
}

$stmt->close();
$conn->close();
