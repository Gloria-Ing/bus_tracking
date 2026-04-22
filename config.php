<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ FIXED timezone (Asia/Kigali is invalid)
date_default_timezone_set('Africa/Kigali');

try {
    // 🚨 IMPORTANT: Use Railway environment variables (NOT hardcoded values)
    $host = getenv("MYSQLHOST") ?: "mysql.railway.internal";
    $port = getenv("MYSQLPORT") ?: 3306;
    $db   = getenv("MYSQLDATABASE") ?: "railway";
    $user = getenv("MYSQLUSER") ?: "root";
    $pass = getenv("MYSQLPASSWORD") ?: "vMogoenptaWFqwxVKxxCrhobvXlBAaUi";

    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // ❌ safe error (do not expose DB details in production)
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

// ---------------- LOG SYSTEM EVENT ----------------

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