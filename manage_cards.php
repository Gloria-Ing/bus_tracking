<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Handle card actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'deactivate') {
        $conn->query("UPDATE rfid_cards SET is_active=0 WHERE id=$id");
        header("Location: manage_cards.php?msg=deactivated");
        exit();
    } elseif ($_GET['action'] == 'activate') {
        $conn->query("UPDATE rfid_cards SET is_active=1 WHERE id=$id");
        header("Location: manage_cards.php?msg=activated");
        exit();
    } elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM rfid_cards WHERE id=$id");
        header("Location: manage_cards.php?msg=deleted");
        exit();
    }
}

// Handle balance update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_balance'])) {
    $id = intval($_POST['card_id']);
    $balance = floatval($_POST['balance']);
    $conn->query("UPDATE rfid_cards SET balance=$balance WHERE id=$id");
    header("Location: manage_cards.php?msg=balance_updated");
    exit();
}

$cards = $conn->query("SELECT * FROM rfid_cards ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage RFID Cards - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-gradient-to-r from-blue-800 to-purple-800 shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-credit-card text-white text-2xl"></i>
                    <h1 class="text-white text-xl font-bold">Manage RFID Cards</h1>
                </div>
                <div class="flex space-x-2">
                    <a href="register_card.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Add New Card
                    </a>
                    <a href="admin_dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <?php 
                    if($_GET['msg'] == 'deactivated') echo "✓ Card deactivated successfully!";
                    if($_GET['msg'] == 'activated') echo "✓ Card activated successfully!";
                    if($_GET['msg'] == 'deleted') echo "✓ Card deleted successfully!";
                    if($_GET['msg'] == 'balance_updated') echo "✓ Balance updated successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Card UID</th>
                            <th class="px-4 py-3 text-left">Passenger Name</th>
                            <th class="px-4 py-3 text-left">Phone</th>
                            <th class="px-4 py-3 text-left">Balance</th>
                            <th class="px-4 py-3 text-left">Trips</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Registered</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($cards && $cards->num_rows > 0) {
                            while($card = $cards->fetch_assoc()): 
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-sm"><?php echo htmlspecialchars($card['card_uid']); ?></td>
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($card['passenger_name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($card['passenger_phone']); ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                    <input type="number" step="0.01" name="balance" value="<?php echo $card['balance']; ?>" 
                                           class="w-24 px-2 py-1 border rounded">
                                    <button type="submit" name="update_balance" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3"><?php echo $card['total_trips']; ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs <?php echo $card['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo $card['is_active'] ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo date('d M Y', strtotime($card['created_at'])); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    <?php if($card['is_active']): ?>
                                        <a href="?action=deactivate&id=<?php echo $card['id']; ?>" 
                                           class="text-yellow-500 hover:text-yellow-700"
                                           onclick="return confirm('Deactivate this card?')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $card['id']; ?>" 
                                           class="text-green-500 hover:text-green-700"
                                           onclick="return confirm('Activate this card?')">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $card['id']; ?>" 
                                       class="text-red-500 hover:text-red-700"
                                       onclick="return confirm('Delete this card permanently?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="8" class="text-center py-8 text-gray-500">No RFID cards registered yet. <a href="register_card.php" class="text-blue-500">Add your first card</a></td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-600">Total Cards</p>
                        <p class="text-2xl font-bold text-blue-800">
                            <?php 
                            $total = $conn->query("SELECT COUNT(*) as count FROM rfid_cards")->fetch_assoc()['count'];
                            echo $total;
                            ?>
                        </p>
                    </div>
                    <i class="fas fa-id-card text-3xl text-blue-400"></i>
                </div>
            </div>
            
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-600">Active Cards</p>
                        <p class="text-2xl font-bold text-green-800">
                            <?php 
                            $active = $conn->query("SELECT COUNT(*) as count FROM rfid_cards WHERE is_active=1")->fetch_assoc()['count'];
                            echo $active;
                            ?>
                        </p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>
            
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-purple-600">Total Trips</p>
                        <p class="text-2xl font-bold text-purple-800">
                            <?php 
                            $trips = $conn->query("SELECT SUM(total_trips) as total FROM rfid_cards")->fetch_assoc()['total'];
                            echo $trips ? $trips : 0;
                            ?>
                        </p>
                    </div>
                    <i class="fas fa-chart-line text-3xl text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>
</body>
</html>