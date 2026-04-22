<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Kigali');

try {

    // 🚀 USE ONLY getenv (Railway safe method)
    $host = getenv("MYSQLHOST");
    $port = getenv("MYSQLPORT");
    $db   = getenv("MYSQLDATABASE");
    $user = getenv("MYSQLUSER");
    $pass = getenv("MYSQLPASSWORD");

    if (!$host || !$user || !$db) {
        die("Missing database environment variables");
    }

    // 🔥 IMPORTANT CHANGE: force TCP connection
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>