<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

$sql = file_get_contents("bus_tracking.sql");

if (!$sql) {
    die("❌ SQL file not found or empty");
}

try {
    // PDO can run multiple SQL statements
    $conn->exec($sql);

    echo "✅ Database imported successfully";

} catch (PDOException $e) {
    echo "❌ Import failed: " . $e->getMessage();
}
?>