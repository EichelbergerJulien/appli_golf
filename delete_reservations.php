<?php            // delete_reservations.php -> Effacer une réservation

session_start();  // Démarre la session pour accéder aux données de session de l'utilisateur. 
                  // Cela est nécessaire pour vérifier si l'utilisateur est authentifié avant de lui permettre de supprimer une réservation.

header("Access-Control-Allow-Origin: http://localhost/appli_golf"); // Remplacez par l'URL de votre frontend pour permettre les requêtes CORS depuis cette origine
header("Access-Control-Allow-Credentials: true"); // Permet d'envoyer les cookies de session avec la requête, ce qui est nécessaire pour maintenir l'authentification 
                                                  // de l'utilisateur lors des requêtes CORS
header("Access-Control-Allow-Headers: Content-Type"); // Permet d'envoyer des requêtes avec le header Content-Type: application/json, 
                                                      // ce qui est nécessaire pour envoyer des données JSON dans le corps de la requête
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Permet les méthodes POST, GET et OPTIONS pour les requêtes CORS. 
                                                            // POST est nécessaire pour supprimer une réservation, GET peut être utilisé pour récupérer des données, 
                                                            // et OPTIONS est nécessaire pour les requêtes préliminaires CORS.

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {  // Répond aux requêtes préliminaires CORS en vérifiant si la méthode de la requête est OPTIONS
    http_response_code(200);  // Répond avec un code 200 OK pour les requêtes OPTIONS, ce qui indique que le serveur accepte les requêtes CORS de l'origine spécifiée
    exit;  // Termine le script pour les requêtes OPTIONS, car elles ne nécessitent pas de traitement supplémentaire du code. 
           // Les requêtes préliminaires CORS sont utilisées par les navigateurs pour vérifier les permissions avant d'envoyer la requête réelle.
}
header("Content-Type: application/json; charset=UTF-8");  // Définit le type de contenu de la réponse comme JSON avec encodage UTF-8 pour assurer 
                                                          // la compatibilité avec les caractères spéciaux

if (!isset($_SESSION["user_id"])) {  // Vérifie si l'utilisateur est authentifié en vérifiant la présence de user_id dans la session
    http_response_code(401);   // Répond avec un code 401 Unauthorized si l'utilisateur n'est pas authentifié
    echo json_encode([  // Envoie une réponse JSON indiquant que l'accès est non autorisé
        "success" => false,  // Indique que l'opération a échoué en définissant "success" à false
        "message" => "Non autorisé"  // Message d'erreur indiquant que l'utilisateur n'est pas autorisé à effectuer cette action
    ]);
    exit;  // Termine le script pour les utilisateurs non authentifiés afin d'empêcher l'exécution du code qui suit
}


// Connexion à la base de données

$host = "localhost";  // Adresse du serveur de base de données (généralement localhost pour une base de données locale)
$dbname = "golf_connect";  // Nom de la base de données à laquelle se connecter
$user = "root";            // Nom d'utilisateur pour se connecter à la base de données (généralement root pour une base de données locale)
$pass = "";                // Mot de passe pour se connecter à la base de données (généralement vide pour une base de données locale sans mot de passe)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [  // Crée une nouvelle instance de PDO pour se connecter à la base de données MySQL 
                                                                                    // en utilisant les paramètres spécifiés
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION  // Configure PDO pour lancer des exceptions en cas d'erreur de base de données, ce qui facilite la gestion des erreurs
    ]);
} catch (Exception $e) {  // Capture toute exception qui pourrait survenir lors de la tentative de connexion à la base de données
    echo json_encode([  // Envoie une réponse JSON indiquant qu'il y a eu une erreur de connexion à la base de données 
        "success" => false,  // Indique que l'opération a échoué en définissant "success" à false
        "message" => "❌ Connexion DB échouée"  // Message d'erreur indiquant qu'il y a eu un problème lors de la connexion à la base de données
    ]);
    exit;
}

// Récupération du JSON envoyé

$data = json_decode(file_get_contents("php://input"), true);  // Lit les données JSON brutes de la requête et les décode en un tableau associatif PHP. 
                                                              // Cela permet d'accéder aux données envoyées par le client dans le corps de la requête, 
                                                              // qui devraient inclure l'ID de la réservation à supprimer.

$id = (int) $data["id"];  // Récupère l'ID de la réservation à supprimer à partir des données décodées 
                          // et le convertit en entier pour éviter les injections SQL et s'assurer que c'est un nombre valide

// Vérification

if (!isset($data["id"]) || !is_numeric($data["id"])) {  // Vérifie que l'ID de la réservation est présent dans les données reçues et qu'il est un nombre valide.
    echo json_encode([  // Envoie une réponse JSON indiquant que l'ID de la réservation est invalide
        "success" => false,  // Indique que l'opération a échoué en définissant "success" à false
        "message" => "❌ ID invalide"  // Message d'erreur indiquant que l'ID de la réservation fourni est invalide, 
                                       // ce qui peut se produire si l'ID est manquant ou n'est pas un nombre
    ]);
    exit;  // Termine le script si l'ID de la réservation est invalide pour éviter toute exécution supplémentaire du code
}

$reservation_id = (int)$data["id"];  // Convertit l'ID de la réservation en entier pour éviter les injections SQL et s'assurer que c'est un nombre valide

try {
    // Préparation requête sécurisée

    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id=?");  // Prépare une requête SQL de suppression pour supprimer une réservation de la table "reservations" 
                                                            // en fonction de l'ID de la réservation
    $stmt->execute([$reservation_id]);  // Exécute la requête préparée en passant l'ID de la réservation à supprimer

    if ($stmt->rowCount() > 0) {  // Vérifie si la requête de suppression a affecté au moins une ligne, ce qui signifie qu'une réservation a été supprimée avec succès
        echo json_encode([  // Envoie une réponse JSON indiquant que la réservation a été supprimée avec succès
            "success" => true,  // Indique que l'opération a réussi en définissant "success" à true
            "message" => "Réservation supprimée"  // Message d'information indiquant que la réservation a été supprimée avec succès
        ]);
    } else {
        echo json_encode([
            "success" => false, // Indique que l'opération a échoué en définissant "success" à false
            "message" => "❌ Aucune réservation trouvée"  // Message d'erreur indiquant qu'aucune réservation n'a été trouvée avec l'ID fourni pour cet utilisateur, 
                                       // ce qui peut se produire si l'ID est incorrect ou si la réservation n'appartient pas à l'utilisateur connecté
        ]);
    }
} catch (Exception $e) {  // Capture toute exception qui pourrait survenir lors de l'exécution de la requête SQL
    echo json_encode([
        "success" => false,  // Indique que l'opération a échoué en définissant "success" à false
        "message" => "❌ Erreur serveur"  // Message d'erreur indiquant qu'il y a eu un problème lors de l'exécution de la requête SQL, 
                                          // ce qui peut se produire en cas de problème avec la base de données ou la requête elle-même
    ]);  
}

