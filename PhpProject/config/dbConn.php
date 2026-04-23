<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u202304056');
define('DB_PASSWORD', 'asdASD123!');
define('DB_NAME', 'db202304056') ;   

try {
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8",                           
        DB_USERNAME,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}



?>