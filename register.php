<?php            // register.php -> Enregistrer un nouvel utilisateur avec email + mot de passe (hashé)

header('Content-Type: application/json; charset=UTF-8');

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

// DATA JSON (comme ton reservations)

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    respond(false, "Email ou mot de passe invalide", 400);
}

// HASH 🔒

$hash = password_hash($password, PASSWORD_DEFAULT);

// INSERT

$stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");

if (!$stmt) {
    respond(false, "Erreur serveur", 500);
}

$stmt->bind_param("ss", $email, $hash);

if (!$stmt->execute()) {
    respond(false, "Email déjà utilisé", 400);
}

respond(true, "Compte créé !");
