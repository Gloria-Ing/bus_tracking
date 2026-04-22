<?php
$page = $_GET['page'] ?? 'login';

$file = $page . ".php";

if (file_exists($file)) {
    include $file;
} else {
    echo "Page not found";
}
?>