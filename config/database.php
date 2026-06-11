<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'u880128862_adminyummy');
define('DB_PASS', '*B^&$RaS4');
define('DB_NAME', 'u880128862_yummyspotdb');
define('DB_PORT', '3306');

function getDB()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;background:#fee;color:#c00;border:1px solid #fcc;border-radius:8px;margin:2rem"><strong>Database Error:</strong> ' . $e->getMessage() . '<br><br>Pastikan MySQL berjalan dan sudah import <code>install.sql</code></div>');
        }
    }
    return $pdo;
}
