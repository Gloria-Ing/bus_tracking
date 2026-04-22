<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'login';

// Force correct directory path
$file = __DIR__ . "/" . $page . ".php";

if (file_exists($file)) {
    include $file;
} else {
    echo "Page not found: " . htmlspecialchars($file);
}
?>