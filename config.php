<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'bank_db');
define('DB_USER', 'postgres');
define('DB_PASS', '#01YannicK#');

function getConnection() {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("<p style='color:red;font-family:sans-serif'>Connexion échouée : " . htmlspecialchars($e->getMessage()) . "</p>");
    }
}
