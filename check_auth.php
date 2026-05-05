<?php  // check_auth.php -> Vérifier si l'utilisateur est connecté et retourner son ID

session_set_cookie_params([  // Configure les paramètres du cookie de session pour améliorer la sécurité et le contrôle de la session
    'lifetime' => 0,  // Le cookie de session expire lorsque le navigateur est fermé, ce qui est une bonne pratique pour les sessions d'authentification
    'path' => '/',    // Le cookie est disponible pour tout le site, ce qui est nécessaire pour que la session fonctionne sur toutes les pages de l'application
    'secure' => false,   // Le cookie n'est pas limité aux connexions sécurisées (HTTPS) pour le développement local, mais devrait être true en production pour sécuriser la session
    'httponly' => true,    // Le cookie est accessible uniquement via HTTP(S) et pas via JavaScript, ce qui aide à prévenir les attaques de type Cross-Site Scripting (XSS)
    'samesite' => 'Lax'  // Lax est adapté pour les requêtes du même site en local
]);

session_start();  // Démarre la session pour accéder aux données de session de l'utilisateur, 
                  // ce qui est nécessaire pour vérifier si l'utilisateur est connecté et pour récupérer son ID

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1'
];
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true"); // Permet d'envoyer les cookies de session avec la requête, ce qui est nécessaire pour maintenir l'authentification de l'utilisateur lors des requêtes CORS
header("Access-Control-Allow-Headers: Content-Type"); // Permet d'envoyer des requêtes avec le header Content-Type: application/json, ce qui est nécessaire pour envoyer des données JSON dans le corps de la requête
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Permet les méthodes POST, GET et OPTIONS pour les requêtes préliminaires CORS.
header('Content-Type: application/json'); // Définit le type de contenu de la réponse comme JSON pour indiquer au client que les données retournées sont au format JSON.

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { // Répond aux requêtes préliminaires CORS en vérifiant si la méthode de la requête est OPTIONS
    http_response_code(200); // Répond avec un code 200 OK pour les requêtes OPTIONS, ce qui indique que le serveur accepte les requêtes CORS de l'origine spécifiée
    exit;  // Termine le script pour les requêtes OPTIONS, car elles ne nécessitent pas de traitement supplémentaire du code. 
           // Les requêtes préliminaires CORS sont utilisées par les navigateurs pour vérifier les permissions avant d'envoyer la requête réelle.
}

echo json_encode([ // Envoie une réponse JSON indiquant si l'utilisateur est connecté et son ID s'il est connecté
    "logged_in" => isset($_SESSION["user_id"]),  // Indique si l'utilisateur est connecté en vérifiant la présence de user_id dans la session. 
                                                 // Si user_id est défini, cela signifie que l'utilisateur est connecté, sinon il ne l'est pas.
    "user_id" => $_SESSION["user_id"] ?? null    // Si l'utilisateur est connecté, retourne son ID à partir de la session, sinon retourne null. 
                                                 // L'opérateur de coalescence nulle (??) est utilisé pour fournir une valeur par défaut de null si user_id n'est pas défini dans la session.
]);

