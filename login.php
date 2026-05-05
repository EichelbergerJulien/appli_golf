<?php                        // login.php -> Authentifier un utilisateur et démarrer une session

session_set_cookie_params([  // Configure les paramètres du cookie de session pour améliorer la sécurité et la gestion de la session
    'lifetime' => 0,         // Le cookie de session expire lorsque le navigateur est fermé (session cookie)
    'path' => '/',           // Le cookie est disponible pour tout le site
    'secure' => false,       // false en local car pas de HTTPS
    'httponly' => true,      // Empêche l'accès au cookie de session via JavaScript
    'samesite' => 'Lax'      // Lax convient pour les requêtes du même site et évite les rejets de cookie en local
]);

session_start();  // Démarre la session pour permettre l'authentification de l'utilisateur et la gestion de l'état de connexion à travers les différentes pages du site

error_reporting(0);  // Désactive l'affichage des erreurs pour éviter de révéler des informations sensibles sur le serveur ou la base de données en cas d'erreur.
ini_set('display_errors', 0);  // Désactive l'affichage des erreurs pour les mêmes raisons que error_reporting(0). 
                               // Cela garantit que les erreurs ne sont pas affichées dans la réponse HTTP,

$user_id = $_SESSION["user_id"] ?? null;  // Récupère l'ID de l'utilisateur à partir de la session, ou null si l'utilisateur n'est pas connecté. 
                                          // Cela peut être utilisé pour vérifier si l'utilisateur est déjà authentifié ou pour associer des actions à un utilisateur spécifique.

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';  
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1'
];
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true"); // Permet d'envoyer les cookies de session avec la requête, ce qui est nécessaire pour maintenir la session de l'utilisateur entre le frontend et le backend
header("Access-Control-Allow-Headers: Content-Type"); // Permet d'envoyer des requêtes avec le header Content-Type: application/json, ce qui est nécessaire pour que le frontend puisse envoyer des données JSON dans le corps de la requête
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Permet les méthodes POST, GET et OPTIONS pour les requêtes CORS.


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {  // Répond aux requêtes préliminaires CORS (OPTIONS) en renvoyant un code 200 OK, 
                                                 // ce qui indique que les méthodes et les headers spécifiés sont autorisés pour les requêtes CORS
    http_response_code(200);  // Répond avec un code 200 OK pour les requêtes OPTIONS, ce qui indique que les méthodes et les headers spécifiés sont autorisés pour les requêtes CORS
    exit;
}
header("Content-Type: application/json; charset=UTF-8"); // Définit le type de contenu de la réponse comme JSON avec encodage UTF-8 
                                                         // pour assurer la compatibilité avec les caractères spéciaux et les accents dans les données envoyées au client

function respond(bool $success, string $message, int $code = 200, array $extra = []): void  // Fonction utilitaire pour envoyer une réponse JSON avec un code de statut HTTP personnalisé
{
    http_response_code($code);  // Définit le code de statut HTTP de la réponse (par défaut 200 OK, mais peut être personnalisé pour indiquer des erreurs comme 400 Bad Request ou 500 Internal Server Error)

    $response = array_merge([
        "success" => $success,
        "message" => $message
    ], $extra);

    echo json_encode($response);  // Envoie une réponse JSON avec les champs "success" et "message", plus les champs supplémentaires fournis.
    exit;
}

// DB

$conn = new mysqli("localhost", "root", "", "golf_connect");  // Connexion à la base de données MySQL avec les paramètres d'hôte, d'utilisateur, 
                                                              // de mot de passe et de nom de base de données
$conn->set_charset("utf8mb4");  // Définit le jeu de caractères de la connexion à la base de données à UTF-8 pour assurer la compatibilité 
                                // avec les caractères spéciaux et les accents dans les données stockées et récupérées de la base de données

if ($conn->connect_error) {  // Vérifie si la connexion à la base de données a échoué
    respond(false, "Erreur serveur", 500);  // Envoie une réponse JSON indiquant qu'il y a eu une erreur de connexion à la base de données 
                                            // avec un code de statut HTTP 500 Internal Server Error pour indiquer un problème côté serveur
}

$data = json_decode(file_get_contents("php://input"), true);  // Lit les données JSON brutes de la requête et les décode en un tableau associatif PHP.

if (!$data) {  // Vérifie si les données JSON ont été décodées correctement. Si $data est false, cela signifie que le JSON était invalide ou mal formé.
    respond(false, "JSON invalide", 400);  // Envoie une réponse JSON indiquant que le JSON reçu est invalide, 
                                           // avec un code de statut HTTP 400 Bad Request pour indiquer que la requête du client est incorrecte
}

$email = trim($data["login_email"] ?? "");  // Récupère l'email du tableau de données décodé, en utilisant l'opérateur de coalescence nulle (??) 
                                            // pour fournir une valeur par défaut de chaîne vide si "login_email" n'est pas défini dans les données reçues.
$password = trim($data["login_password"] ?? "");  // Récupère le mot de passe du tableau de données décodé, en utilisant l'opérateur de coalescence nulle (??) 
                                                  // pour fournir une valeur par défaut de chaîne vide si "login_password" n'est pas défini dans les données reçues.

if (!$email || !$password) {  // Vérifie si l'email ou le mot de passe est vide. Si l'un des deux est vide, cela signifie que les champs requis ne sont pas remplis.
    respond(false, "Champs requis manquants", 400);  // Envoie une réponse JSON indiquant que les champs requis sont manquants, 
                                                     // avec un code de statut HTTP 400 Bad Request pour indiquer que la requête du client est incorrecte
}

$stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");  // Prépare une requête SQL pour sélectionner l'ID et le mot de passe hashé de l'utilisateur 
                                                                           // correspondant à l'email fourni. 
                                                                           // L'utilisation d'une requête préparée avec un paramètre lié (?) aide à prévenir les injections SQL.

if (!$stmt) {  // Vérifie si la préparation de la requête SQL a échoué
    respond(false, "Erreur SQL prepare", 500); // Envoie une réponse JSON indiquant qu'il y a eu une erreur lors de la préparation de la requête SQL, 
                                               // avec un code de statut HTTP 500 Internal Server Error pour indiquer un problème côté serveur
}

$stmt->bind_param("s", $email);  // Lie le paramètre de la requête préparée avec la variable $email. 
                                 // Le "s" indique que le paramètre est une chaîne de caractères (string).

if (!$stmt->execute()) {  // Exécute la requête préparée pour récupérer les données de l'utilisateur correspondant à l'email fourni.
    respond(false, "Erreur SQL execute", 500);  // Envoie une réponse JSON indiquant qu'il y a eu une erreur lors de l'exécution de la requête SQL,
}

$result = $stmt->get_result(); // Récupère le résultat de la requête exécutée. Cela retourne un objet mysqli_result qui peut être utilisé 
                               // pour accéder aux données retournées par la requête SQL.
$user = $result->fetch_assoc(); // Récupère la première ligne du résultat de la requête sous forme de tableau associatif. 
                                // Si un utilisateur avec l'email fourni existe, $user contiendra les champs

// Obtenir le hash du mot de passe de manière sécurisée
$hash = ($user !== null && isset($user["password"])) ? $user["password"] : password_hash("dummy", PASSWORD_DEFAULT);

if ($user && password_verify($password, $hash)) {  // Vérifie si un utilisateur a été trouvé avec l'email fourni 
                                                               // et si le mot de passe fourni correspond au hash stocké dans la base de données en utilisant password_verify.

    session_regenerate_id(true);  // Génère un nouvel ID de session pour éviter les attaques de fixation de session. 
                                // Le paramètre true indique que l'ancien ID de session doit être supprimé, ce qui améliore la sécurité en empêchant l'utilisation de l'ancien ID.

    $_SESSION["user_id"] = $user["id"];  // Stocke l'ID de l'utilisateur dans la session pour maintenir l'état de connexion de l'utilisateur à travers les différentes pages du site. 
                                // Cela permet au backend de savoir quel utilisateur est connecté lors des requêtes ultérieures.
 
    respond(true, "Connexion réussie", 200, ["redirect" => "reservations.html"]);  // Envoie une réponse JSON indiquant que la connexion a réussi et fournit l'URL de redirection.
} else {  
    respond(false, "Identifiants invalides", 401);  // Envoie une réponse JSON indiquant que les identifiants sont invalides, 
                                                    // avec un champ "success" à false pour indiquer que l'opération a échoué 
                                                    // et un message d'information pour le client, avec un code de statut HTTP 401 Unauthorized pour indiquer 
                                                    // que l'authentification a échoué
}

$stmt->close();  // Ferme la requête préparée pour libérer les ressources associées à cette requête.
$conn->close();  // Ferme la connexion à la base de données pour libérer les ressources associées à cette connexion.

