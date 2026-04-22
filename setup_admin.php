<?php
require_once 'config.php';

$username = 'admin';
$password = 'admin123';
$email = 'admin@bustracking.com';
$full_name = 'System Administrator';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->query("SELECT * FROM admins WHERE username='$username'");
if($check->num_rows > 0) {
    $conn->query("UPDATE admins SET password='$hashed_password', email='$email', full_name='$full_name' WHERE username='$username'");
    $message = "Admin password updated successfully!";
} else {
    $conn->query("INSERT INTO admins (username, password, email, full_name) VALUES ('$username', '$hashed_password', '$email', '$full_name')");
    $message = "Admin created successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-900 to-purple-900 min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-800 p-6 text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-4xl text-green-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Setup Complete!</h2>
            </div>
            
            <div class="p-8">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-gray-800 mb-3">Login Credentials:</h3>
                    <p><strong>Username:</strong> <code class="bg-gray-200 px-2 py-1 rounded">admin</code></p>
                    <p><strong>Password:</strong> <code class="bg-gray-200 px-2 py-1 rounded">admin123</code></p>
                </div>
                
                <a href="login.php" class="block w-full bg-blue-500 hover:bg-blue-600 text-white text-center font-bold py-3 rounded-lg transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>