<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kigali');

try {
    // Railway MySQL credentials (your values)
    $host = "mysql.railway.internal";
    $port = 3306;
    $db   = "railway";
    $user = "root";
    $pass = "vMogoenptaWFqwxVKxxCrhobvXlBAaUi";

    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // safer message (don’t expose DB error in production)
    die("Database connection failed");
}

// ---------------- HELPERS ----------------

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function redirectIfNotLoggedIn() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

function logSystemEvent($conn, $card_uid, $bus_number, $message) {
    $stmt = $conn->prepare("
        INSERT INTO system_logs (card_uid, bus_number, message)
        VALUES (:card_uid, :bus_number, :message)
    ");

    $stmt->execute([
        ':card_uid' => $card_uid,
        ':bus_number' => $bus_number,
        ':message' => $message
    ]);
}
?>