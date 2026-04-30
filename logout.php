<?php             //  logout.php -> Déconnecter l'utilisateur en détruisant la session 

session_start();

session_unset();
session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Déconnecté"
]);


