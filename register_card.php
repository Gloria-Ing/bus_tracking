<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card_uid = strtoupper($conn->real_escape_string($_POST['card_uid']));
    $passenger_name = $conn->real_escape_string($_POST['passenger_name']);
    $passenger_phone = $conn->real_escape_string($_POST['passenger_phone']);
    $passenger_email = $conn->real_escape_string($_POST['passenger_email']);
    $balance = floatval($_POST['balance']);
    
    $check = $conn->query("SELECT * FROM rfid_cards WHERE card_uid='$card_uid'");
    if ($check && $check->num_rows > 0) {
        $error = "Card UID already registered!";
    } else {
        $sql = "INSERT INTO rfid_cards (card_uid, passenger_name, passenger_phone, passenger_email, balance) 
                VALUES ('$card_uid', '$passenger_name', '$passenger_phone', '$passenger_email', $balance)";
        
        if ($conn->query($sql)) {
            $message = "Card registered successfully!";
            logSystemEvent($conn, $card_uid, '', "Card registered for $passenger_name");
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register RFID Card - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-gradient-to-r from-blue-800 to-purple-800 shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-id-card text-white text-2xl"></i>
                    <h1 class="text-white text-xl font-bold">Register RFID Card</h1>
                </div>
                <a href="admin_dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Register New RFID Card</h2>
                
                <?php if($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-microchip mr-2"></i>Card UID
                        </label>
                        <input type="text" name="card_uid" required 
                               placeholder="Enter card UID (e.g., A3B2C1D4)"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i> Tap card on reader and copy UID from Serial Monitor
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-user mr-2"></i>Passenger Name
                        </label>
                        <input type="text" name="passenger_name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-phone mr-2"></i>Phone Number
                        </label>
                        <input type="text" name="passenger_phone" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email (Optional)
                        </label>
                        <input type="email" name="passenger_email" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-dollar-sign mr-2"></i>Initial Balance
                        </label>
                        <input type="number" step="0.01" name="balance" value="0.00" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-save mr-2"></i>Register Card
                    </button>
                </form>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-bold text-blue-800 mb-2">How to get Card UID:</h3>
                    <ol class="text-sm text-blue-700 space-y-1">
                        <li>1. Upload the RFID test sketch to ESP32</li>
                        <li>2. Open Serial Monitor at 115200 baud</li>
                        <li>3. Tap the card on the RFID reader</li>
                        <li>4. Copy the UID shown (e.g., A3B2C1D4)</li>
                        <li>5. Paste it in the Card UID field above</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>