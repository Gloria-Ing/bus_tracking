<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bus_tracking';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Kolkata');

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