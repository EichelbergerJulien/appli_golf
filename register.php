<?php            // register.php -> Enregistrer un nouvel utilisateur avec email + mot de passe (hashé)

header('Content-Type: application/json; charset=UTF-8'); // Définit le type de contenu de la réponse comme JSON avec encodage UTF-8 pour assurer 
                                                         // la compatibilité avec les caractères spéciaux

function respond(bool $success, string $message, int $code = 200): void  // Fonction utilitaire pour envoyer une réponse JSON avec un code de statut HTTP personnalisé
{
    http_response_code($code);  // Définit le code de statut HTTP de la réponse (par défaut 200 OK, mais peut être personnalisé 
                                // pour indiquer des erreurs comme 400 Bad Request ou 500 Internal Server Error)
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);  // Envoie une réponse JSON avec les champs "success" (booléen indiquant si l'opération a réussi ou échoué) 
         // et "message" (string contenant un message d'information pour le client)
    exit;  // Termine le script après avoir envoyé la réponse pour éviter toute exécution supplémentaire du code
}

// DB

$conn = new mysqli("localhost", "root", "", "golf_connect");  // Connexion à la base de données MySQL avec les paramètres d'hôte, d'utilisateur, 
                                                              // de mot de passe et de nom de base de données
$conn->set_charset("utf8mb4");  // Définit le jeu de caractères de la connexion à la base de données à UTF-8 pour assurer la compatibilité 
                                // avec les caractères spéciaux et les accents dans les données stockées et récupérées de la base de données

if ($conn->connect_error) {  // Vérifie si la connexion à la base de données a échoué
    respond(false, "Erreur serveur", 500);  // Envoie une réponse JSON indiquant qu'il y a eu une erreur de connexion à la base de données 
                                            // avec un code de statut HTTP 500 Internal Server Error
}

// DATA JSON (comme ton reservations)

$data = json_decode(file_get_contents("php://input"), true);  // Lit les données JSON brutes de la requête et les décode en un tableau associatif PHP.

if (!$data) {  // Vérifie si les données JSON ont été décodées correctement. Si $data est false ou null, cela signifie que le JSON était invalide ou mal formé.
    respond(false, "JSON invalide", 400);  // Envoie une réponse JSON indiquant que le JSON reçu est invalide
}

$email = trim($data["email"] ?? "");  // Récupère l'email du tableau de données décodé, en utilisant l'opérateur de coalescence nulle (??) 
                                      // pour fournir une valeur par défaut de chaîne vide si "email" n'est pas défini dans les données reçues.
$password = trim($data["password"] ?? "");  // Récupère le mot de passe du tableau de données décodé, en utilisant l'opérateur de coalescence nulle (??) 
                                            // pour fournir une valeur par défaut de chaîne vide si "password" n'est pas défini dans les données reçues.

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {  // Vérifie si l'email est valide en utilisant filter_var avec FILTER_VALIDATE_EMAIL, 
                                                                            // et si le mot de passe a une longueur d'au moins 6 caractères. 
                                                                            // Si l'une de ces conditions n'est pas remplie, cela signifie que les données reçues sont invalides.
    respond(false, "Email ou mot de passe invalide", 400);  // Envoie une réponse JSON indiquant que l'email ou le mot de passe est invalide, 
                                                            // avec un code de statut HTTP 400 Bad Request pour indiquer que la requête du client est incorrecte
}

// HASH 🔒

$hash = password_hash($password, PASSWORD_DEFAULT);  // Hash le mot de passe en utilisant la fonction password_hash avec l'algorithme par défaut (actuellement bcrypt), 
                                                     // ce qui permet de stocker le mot de passe de manière sécurisée dans la base de données. 
                                                     // Le hash généré inclut un sel unique pour chaque mot de passe, ce qui rend les attaques par force brute plus difficiles.

// INSERT

$stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");    // Prépare une requête SQL d'insertion pour ajouter un nouvel utilisateur 
                                                                                // dans la table "users" avec les champs "email" et "password".

if (!$stmt) {
    respond(false, "Erreur serveur", 500);  // Envoie une réponse JSON indiquant qu'il y a eu une erreur lors de la préparation de la requête SQL, 
                                            // avec un code de statut HTTP 500 Internal Server Error pour indiquer un problème côté serveur
}

$stmt->bind_param("ss", $email, $hash);  // Lie les paramètres de la requête préparée avec les variables $email et $hash. 
                                         // Le premier argument "ss" indique que les deux paramètres sont des chaînes de caractères (string).

if (!$stmt->execute()) {  // Exécute la requête préparée pour insérer le nouvel utilisateur dans la base de données.
    respond(false, "Email déjà utilisé", 400);  // Si l'exécution de la requête échoue, cela peut être dû à une violation de contrainte d'unicité sur le champ "email" (si un utilisateur avec le même email existe déjà). 
                                            // Envoie une réponse JSON indiquant que l'email est déjà utilisé, avec un code de statut HTTP 400 Bad Request pour indiquer que la requête du client est incorrecte
}

$stmt->close();  // Ferme la requête préparée pour libérer les ressources associées à cette requête.
$conn->close();  // Ferme la connexion à la base de données pour libérer les ressources associées à cette connexion.

respond(true, "Compte créé !");  // Si l'exécution de la requête réussit, envoie une réponse JSON indiquant que le compte a été créé avec succès, 
                                 // avec un champ "success" à true pour indiquer que l'opération a réussi et un message d'information pour le client
