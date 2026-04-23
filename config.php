<?php
session_start();

// Use environment variables on Render, fallback to local defaults
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'bus_tracking';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    // Log error but don't expose details in production
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

// Set timezone to Rwanda (East Africa Time)
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