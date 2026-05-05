<?php             //  logout.php -> Déconnecter l'utilisateur en détruisant la session 

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();  // Démarre la session pour accéder aux données de session de l'utilisateur. 
                  // Cela est nécessaire pour pouvoir détruire la session et ainsi déconnecter l'utilisateur.

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1'
];
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_unset();  // Supprime toutes les variables de session. Cela efface les données de session associées à l'utilisateur, 
                  // mais la session elle-même reste active jusqu'à ce qu'elle soit détruite.

session_destroy();  // Détruit la session. Cela termine la session en cours et supprime toutes les données de session du serveur, 
                    // ce qui déconnecte effectivement l'utilisateur.

echo json_encode([
    "success" => true,
    "message" => "Déconnecté"
]);                           // Envoie une réponse JSON indiquant que la déconnexion a réussi, avec un champ "success" à true 
                              // et un message d'information pour le client 


