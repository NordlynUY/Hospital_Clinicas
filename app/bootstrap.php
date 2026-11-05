<?php
// Inicializa la aplicación: configuración, sesión segura y conexión con MariaDB.
declare(strict_types=1);
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set('America/Montevideo');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('SIGSMSESSID');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'secure' => $secure,
    'httponly' => true, 'samesite' => 'Lax'
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con MariaDB. Revise config/config.php y ejecute los scripts SQL.');
}
require_once __DIR__ . '/functions.php';
