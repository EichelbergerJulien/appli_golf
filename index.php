<?php

// index.php - Routeur central pour l'application

session_start();

// Autoloader simple pour les classes
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/controllers/' . $className . '.php',
        __DIR__ . '/models/' . $className . '.php',
        __DIR__ . '/config/' . $className . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Charger les variables d'environnement
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

function serveView(string $viewName): void
{
    $path = __DIR__ . '/views/' . $viewName;
    if (!file_exists($path)) {
        http_response_code(404);
        echo 'Page introuvable';
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    readfile($path);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$requestPath = $requestUri;

if ($scriptDir !== '/' && strpos($requestPath, $scriptDir) === 0) {
    $requestPath = substr($requestPath, strlen($scriptDir));
}

$requestPath = '/' . trim($requestPath, '/');

switch ($requestPath) {
    case '/':
    case '/home':
        serveView('index.html');
        break;

    case '/login':
        serveView('login.html');
        break;

    case '/register':
        serveView('register.html');
        break;

    case '/contact':
        serveView('contact.html');
        break;

    case '/reservations':
        serveView('reservations.html');
        break;

    case '/galerie':
        serveView('galerie.html');
        break;

    case '/mini-jeu':
        serveView('mini_jeu.html');
        break;

    case '/index.php':
        serveView('index.html');
        break;

    case '/login.html':
        serveView('login.html');
        break;

    case '/register.html':
        serveView('register.html');
        break;

    case '/contact.html':
        serveView('contact.html');
        break;

    case '/reservations.html':
        serveView('reservations.html');
        break;

    case '/mini_jeu.html':
        serveView('mini_jeu.html');
        break;

    case '/logout':
    case '/logout.php':
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/check_auth':
    case '/check_auth.php':
        $controller = new AuthController();
        $controller->checkAuth();
        break;

    case '/auth/login':
    case '/login.php':
        $controller = new AuthController();
        $controller->login();
        break;

    case '/auth/register':
    case '/register.php':
        $controller = new AuthController();
        $controller->register();
        break;

    case '/contact/send':
    case '/contact_traitement.php':
        $controller = new ContactController();
        $controller->send();
        break;

    case '/reservations/create':
    case '/reservations_traitement.php':
        $controller = new ReservationController();
        $controller->create();
        break;

    case '/reservations/list':
    case '/get_reservations.php':
        $controller = new ReservationController();
        $controller->get();
        break;

    case '/reservations/delete':
    case '/delete_reservations.php':
        $controller = new ReservationController();
        $controller->delete();
        break;

    case '/reservations/update':
    case '/update_reservations.php':
        $controller = new ReservationController();
        $controller->update();
        break;

    default:
        http_response_code(404);
        echo 'Route introuvable';
        break;
}
?>