<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Kigali');

try {

    // 🚀 USE getenv() ONLY (MOST RELIABLE ON RAILWAY)
    $host = getenv("MYSQLHOST");
    $port = getenv("MYSQLPORT");
    $db   = getenv("MYSQLDATABASE");
    $user = getenv("MYSQLUSER");
    $pass = getenv("MYSQLPASSWORD");

    // 🔍 DEBUG CHECK (VERY IMPORTANT)
    if (!$host || !$user || !$db) {
        die("Missing database environment variables in Railway");
    }

    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed");
}
?>