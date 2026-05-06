<?php             // update_reservations.php -> Modifier une réservation existante

session_start();  // Démarre la session pour accéder à $_SESSION["user_id"] et vérifier l'authentification

header("Access-Control-Allow-Origin: http://localhost/appli_golf");  // Remplacez par l'URL de votre frontend
header("Access-Control-Allow-Credentials: true");  // Permet d'envoyer les cookies de session avec la requête
header("Access-Control-Allow-Headers: Content-Type");  // Permet d'envoyer des requêtes avec le header Content-Type: application/json
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");  // Permet les méthodes POST, GET et OPTIONS pour les requêtes CORS

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {  // Répond aux requêtes préliminaires CORS
    http_response_code(200);  // Répond avec un code 200 OK pour les requêtes OPTIONS
    exit;  // Termine le script pour les requêtes OPTIONS, car elles ne nécessitent pas de traitement supplémentaire
}
header("Content-Type: application/json; charset=UTF-8");  // Définit le type de contenu de la réponse comme JSON

if (!isset($_SESSION["user_id"])) {  // Vérifie si l'utilisateur est authentifié en vérifiant la présence de user_id dans la session
    http_response_code(401);  // Répond avec un code 401 Unauthorized si l'utilisateur n'est pas authentifié
    echo json_encode([  // Envoie une réponse JSON indiquant que l'accès est non autorisé
        "success" => false,  // Indique que l'opération a échoué
        "message" => "Non autorisé"  // Message d'erreur indiquant que l'utilisateur n'est pas autorisé à effectuer cette action
    ]);
    exit;  // Termine le script pour les utilisateurs non authentifiés
}

require 'vendor/autoload.php';  // Charge les dépendances via Composer (si vous utilisez des bibliothèques tierces)

$conn = new mysqli("localhost", "root", "", "golf_connect");    // Connexion à la base de données MySQL avec les paramètres d'hôte, 
                                                                // d'utilisateur, de mot de passe et de nom de base de données

if ($conn->connect_error) {  // Vérifie si la connexion à la base de données a échoué
    http_response_code(500);  // Répond avec un code 500 Internal Server Error si la connexion à la base de données a échoué
    echo json_encode([  // Envoie une réponse JSON indiquant qu'il y a eu une erreur de connexion à la base de données
        "success" => false,  // Indique que l'opération a échoué
        "message" => "Erreur de connexion à la base de données"  // Message d'erreur indiquant qu'il y a eu un problème lors de la connexion à la base de données
    ]);
    exit;  // Termine le script si la connexion à la base de données a échoué
}

$data = json_decode(file_get_contents("php://input"), true);  // Lit les données JSON brutes de la requête et les décode en un tableau associatif PHP.  

//var_dump($data);  // Affiche le contenu de $data pour le débogage. Cela permet de vérifier que 
                    // les données reçues sont correctes et dans le format attendu.


if (         
    !isset(  // Vérifie que toutes les données nécessaires sont présentes dans la requête
        $data["id"],  // ID de la réservation à modifier
        $data["nom"],
        $data["prenom"],
        $data["email"],
        $data["date_reservation"],
        $data["heure"],
        $data["joueurs"]
    )
) {
    echo json_encode(["success" => false, "message" => "Données invalides"]);  // Envoie une réponse JSON indiquant que les données reçues sont invalides
    exit;  // Termine le script si les données sont invalides
}

$id = (int)$data["id"];  // Convertit l'ID de la réservation en entier pour éviter les injections SQL et s'assurer que c'est un nombre valide
$nom = trim($data["nom"]);
$prenom = trim($data["prenom"]);
$email = filter_var($data["email"], FILTER_VALIDATE_EMAIL);
$date = $data["date_reservation"];
$heure = $data["heure"];
$joueurs = (int)$data["joueurs"];

if (!$email) {  // Vérifie si l'email est valide après avoir été filtré avec FILTER_VALIDATE_EMAIL
    echo json_encode(["success" => false, "message" => "Email invalide"]);  // Envoie une réponse JSON indiquant que l'email fourni est invalide
    exit;  // Termine le script si l'email est invalide
}

$stmt = $conn->prepare("UPDATE reservations   
    SET nom=?, prenom=?, email=?, date_reservation=?, heure=?, joueurs=? 
    WHERE id=?");     // Prépare une requête SQL pour mettre à jour une réservation existante 
                                     // en utilisant des paramètres liés pour éviter les injections SQL.

if (!$stmt) {  // Vérifie si la préparation de la requête SQL a échoué
    http_response_code(500);  // Répond avec un code 500 Internal Server Error si la préparation de la requête SQL a échoué
    echo json_encode([  // Envoie une réponse JSON indiquant qu'il y a eu une erreur lors de la préparation de la requête SQL
        "success" => false,  // Indique que l'opération a échoué
        "message" => "Erreur préparation SQL"  // Message d'erreur indiquant qu'il y a eu un problème lors de la préparation de la requête SQL
    ]);
    exit;  // Termine le script si la préparation de la requête SQL a échoué
}

$stmt->bind_param("sssssii", $nom, $prenom, $email, $date, $heure, $joueurs, $id);  // Lie les paramètres à la requête préparée.
                                                                                        // Les types de données sont spécifiés dans la chaîne "sssssii"

if ($stmt->execute()) {  // Exécute la requête SQL préparée et vérifie si elle a réussi
    echo json_encode([  // Envoie une réponse JSON indiquant que la mise à jour a réussi
        "success" => true,  // Indique que l'opération a réussi
        "message" => $stmt->affected_rows > 0   // Vérifie si des lignes ont été affectées par la mise à jour pour déterminer 
                                                // si les données ont été modifiées ou si elles étaient identiques
            ? "Modifié"  // Si des lignes ont été affectées, cela signifie que les données ont été modifiées
            : "Aucune modification (données identiques)"  // Si aucune ligne n'a été affectée, cela signifie que les données fournies étaient identiques à celles déjà présentes dans la base de données, 
                                                          // donc aucune modification n'a été effectuée   
    ]);
} else {  // Si l'exécution de la requête SQL a échoué, envoie une réponse JSON indiquant qu'il y a eu une erreur SQL
    echo json_encode([  // Envoie une réponse JSON indiquant qu'il y a eu une erreur lors de l'exécution de la requête SQL
        "success" => false,  // Indique que l'opération a échoué
        "message" => "Erreur SQL"  // Message d'erreur indiquant qu'il y a eu un problème lors de l'exécution de la requête SQL
    ]);
}

$stmt->close();  // Ferme la requête préparée pour libérer les ressources associées à cette requête 
$conn->close();  // Ferme la connexion à la base de données pour libérer les ressources

