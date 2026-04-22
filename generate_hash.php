<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $verify = password_verify($password, $hash);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Hash Generated</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; background: #f0f0f0; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .hash { background: #2d3748; color: #68d391; padding: 15px; border-radius: 5px; font-family: monospace; word-break: break-all; }
            .success { color: #48bb78; }
            .info { background: #ebf8ff; padding: 15px; border-radius: 5px; margin-top: 20px; }
            code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Password Hash Generated</h1>
            <p><strong>Original Password:</strong> <code><?php echo htmlspecialchars($password); ?></code></p>
            <p><strong>Generated Hash:</strong></p>
            <div class="hash"><?php echo $hash; ?></div>
            <p class="success">✓ Verification: <?php echo $verify ? 'Successful' : 'Failed'; ?></p>
            
            <div class="info">
                <h3>📝 SQL Query to Update Password:</h3>
                <code>UPDATE admins SET password = '<?php echo $hash; ?>' WHERE username = 'admin';</code>
            </div>
            
            <div class="info">
                <h3>📝 SQL Query to Insert New Admin:</h3>
                <code>INSERT INTO admins (username, password, email, full_name) VALUES ('newadmin', '<?php echo $hash; ?>', 'admin@example.com', 'New Admin');</code>
            </div>
            
            <p style="margin-top: 20px;"><a href="generate_hash.php">← Generate Another Hash</a> | <a href="login.php">Go to Login →</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Hash Generator - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-6">
                <i class="fas fa-key text-4xl text-blue-500"></i>
                <h1 class="text-2xl font-bold mt-2">Password Hash Generator</h1>
                <p class="text-gray-600">Generate bcrypt hash for database</p>
            </div>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Enter Password:</label>
                    <input type="text" name="password" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 rounded-lg transition">
                    Generate Hash
                </button>
            </form>
            
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle"></i> Use this tool to generate password hashes for manual database updates.
                </p>
            </div>
        </div>
    </div>
</body>
</html>