<?php  // check_auth.php -> Vérifier si l'utilisateur est connecté et retourner son ID  

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
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode([
    "logged_in" => isset($_SESSION["user_id"]),
    "user_id" => $_SESSION["user_id"] ?? null
]);