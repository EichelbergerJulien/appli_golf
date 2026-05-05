<?php    // get_reservations.php -> Récupérer toutes les réservations au format JSON

session_start();  // Démarre la session pour accéder aux données de session de l'utilisateur. 
                  // Cela est nécessaire pour vérifier si l'utilisateur est authentifié avant de lui permettre d'accéder aux réservations.

if (!isset($_SESSION["user_id"])) {  // Vérifie si l'utilisateur est authentifié en vérifiant la présence de user_id dans la session  
    echo json_encode([               // Envoie une réponse JSON indiquant que l'accès est non autorisé
        "success" => false,      // Indique que l'opération a échoué en définissant "success" à false
        "message" => "Non autorisé"  // Message d'erreur indiquant que l'utilisateur n'est pas autorisé à accéder à cette ressource
    ]);  // Envoie une réponse JSON indiquant que l'accès est non autorisé, avec un champ "success" à false et un message d'erreur pour le client
    exit;  // Termine le script pour les utilisateurs non authentifiés afin d'empêcher l'exécution du code qui suit
}

header('Content-Type: application/json');  // Définit le type de contenu de la réponse comme JSON pour indiquer au client que les données retournées sont au format JSON.

// Connexion à la base de données

$conn = new mysqli("localhost", "root", "", "golf_connect");  // Connexion à la base de données MySQL avec les paramètres d'hôte, d'utilisateur, 
                                                              // de mot de passe et de nom de base de données

if ($conn->connect_error) {  // Vérifie si la connexion à la base de données a échoué
    echo json_encode([  // Envoie une réponse JSON indiquant qu'il y a eu une erreur de connexion à la base de données
        "success" => false,  // Indique que l'opération a échoué en définissant "success" à false
        "message" => "Connexion échouée"  // Message d'erreur indiquant qu'il y a eu un problème lors de la connexion à la base de données
    ]);
    exit;  // Termine le script si la connexion à la base de données a échoué pour éviter toute exécution supplémentaire du code
}

// Requête SQL pour récupérer les réservations

$sql = "SELECT id, nom, prenom, email, date_reservation, heure, joueurs FROM reservations";  // Requête SQL pour sélectionner les champs id, nom, prenom, email, date_reservation, 
                                                                                             // heure et joueurs de la table "reservations"
$result = $conn->query($sql);  // Exécute la requête SQL et stocke le résultat dans la variable $result. 
                               // $result est un objet mysqli_result qui contient les données retournées par la requête SQL.

$reservations = [];  // Initialise un tableau vide pour stocker les réservations récupérées de la base de données.

if ($result) {  // Vérifie si la requête SQL a été exécutée avec succès et que $result contient des données
    while ($row = $result->fetch_assoc()) {  // Parcourt chaque ligne du résultat de la requête SQL en utilisant fetch_assoc() 
                                             // pour récupérer les données sous forme de tableau associatif.

        $reservations[] = $row;  // Ajoute chaque ligne de données (chaque réservation) au tableau $reservations. 
                                 // Chaque élément de $reservations est un tableau associatif contenant les champs de la réservation.
    }
}

// Retour JSON propre

echo json_encode([  // Envoie une réponse JSON contenant les réservations récupérées de la base de données
    "success" => true,  // Indique que l'opération a réussi en définissant "success" à true
    "data" => $reservations  // Inclut les réservations dans le champ "data" de la réponse JSON, ce qui permet au client de recevoir les données des réservations
]);

$conn->close();  // Ferme la connexion à la base de données pour libérer les ressources associées à cette connexion.
exit;  // Termine le script après avoir envoyé la réponse JSON pour éviter toute exécution supplémentaire du code.
