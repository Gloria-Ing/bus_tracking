<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

$sql = file_get_contents("bus_tracking.sql");

if (!$sql) {
    die("SQL file not found or empty");
}

if ($conn->multi_query($sql)) {
    echo "✅ Database imported successfully";
} else {
    echo "❌ Error importing database: " . $conn->error;
}
?>