<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read database credentials from environment variables
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'bus_tracking';
$port = 4000; // TiDB Cloud port

// Create connection with SSL for TiDB Cloud
$conn = mysqli_init();
if (!$conn) {
    die("mysqli_init failed");
}

// Set SSL options
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Connect to TiDB Cloud using environment variables
if (!$conn->real_connect($host, $user, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL)) {
    error_log("Connection failed: " . mysqli_connect_error());
    die("Database connection error. Please try again later.");
}

date_default_timezone_set('Africa/Kigali');

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
    $card_uid = $conn->real_escape_string($card_uid);
    $bus_number = $conn->real_escape_string($bus_number);
    $message = $conn->real_escape_string($message);
    $conn->query("INSERT INTO system_logs (card_uid, bus_number, message) VALUES ('$card_uid', '$bus_number', '$message')");
}
?>