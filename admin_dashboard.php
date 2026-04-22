<?php
require_once 'config.php';
redirectIfNotLoggedIn();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bus'])) {
        $bus_number = $conn->real_escape_string($_POST['bus_number']);
        $total_seats = intval($_POST['total_seats']);
        $route_name = $conn->real_escape_string($_POST['route_name']);
        $driver_name = $conn->real_escape_string($_POST['driver_name']);
        $driver_phone = $conn->real_escape_string($_POST['driver_phone']);
        
        $conn->query("INSERT INTO buses (bus_number, total_seats, route_name, driver_name, driver_phone) 
                     VALUES ('$bus_number', $total_seats, '$route_name', '$driver_name', '$driver_phone')");
        $conn->query("INSERT INTO bus_status (bus_number, total_seats, available_seats, latitude, longitude) 
                     VALUES ('$bus_number', $total_seats, $total_seats, 28.6139, 77.2090)");
        header("Location: admin_dashboard.php?success=bus_added");
        exit();
    }
    
    if (isset($_POST['add_schedule'])) {
        $bus_number = $conn->real_escape_string($_POST['bus_number']);
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $source = $conn->real_escape_string($_POST['source']);
        $destination = $conn->real_escape_string($_POST['destination']);
        $days = $conn->real_escape_string($_POST['days']);
        $fare = floatval($_POST['fare']);
        
        $conn->query("INSERT INTO schedule (bus_number, departure_time, arrival_time, source, destination, days_of_week, fare) 
                     VALUES ('$bus_number', '$departure_time', '$arrival_time', '$source', '$destination', '$days', $fare)");
        header("Location: admin_dashboard.php?success=schedule_added");
        exit();
    }
    
    if (isset($_POST['delete_schedule'])) {
        $id = intval($_POST['schedule_id']);
        $conn->query("DELETE FROM schedule WHERE id=$id");
        header("Location: admin_dashboard.php?success=deleted");
        exit();
    }
    
    if (isset($_POST['delete_bus'])) {
        $bus_number = $conn->real_escape_string($_POST['bus_number']);
        $conn->query("DELETE FROM buses WHERE bus_number='$bus_number'");
        header("Location: admin_dashboard.php?success=bus_deleted");
        exit();
    }
}

// Fetch statistics
$total_buses = $conn->query("SELECT COUNT(*) as count FROM buses")->fetch_assoc()['count'];
$total_cards = $conn->query("SELECT COUNT(*) as count FROM rfid_cards")->fetch_assoc()['count'];
$total_schedules = $conn->query("SELECT COUNT(*) as count FROM schedule")->fetch_assoc()['count'];
$total_trips_today = $conn->query("SELECT COUNT(*) as count FROM passenger_transactions WHERE DATE(transaction_time) = CURDATE()")->fetch_assoc()['count'];
$active_cards = $conn->query("SELECT COUNT(*) as count FROM rfid_cards WHERE is_active=1")->fetch_assoc()['count'];

$buses = $conn->query("SELECT * FROM buses ORDER BY bus_number");
$schedules = $conn->query("SELECT s.*, b.route_name FROM schedule s JOIN buses b ON s.bus_number = b.bus_number ORDER BY s.departure_time");
$recent_transactions = $conn->query("
    SELECT pt.*, rc.passenger_name 
    FROM passenger_transactions pt 
    JOIN rfid_cards rc ON pt.card_uid = rc.card_uid 
    ORDER BY pt.transaction_time DESC 
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-gradient-to-r from-blue-800 to-purple-800 shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center flex-wrap gap-2">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-bus text-white text-2xl"></i>
                    <h1 class="text-white text-xl font-bold">Bus Tracking Admin Panel</h1>
                </div>
                <div class="flex items-center space-x-2 flex-wrap gap-2">
                    <span class="text-white">
                        <i class="fas fa-user mr-2"></i><?php echo $_SESSION['admin_name']; ?>
                    </span>
                    <a href="gps_history.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-map-marker-alt mr-2"></i>GPS History
                    </a>
                    <a href="register_card.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-id-card mr-2"></i>Register Card
                    </a>
                    <a href="manage_cards.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-credit-card mr-2"></i>Manage Cards
                    </a>
                    <a href="change_password.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <?php if(isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php 
                    if($_GET['success'] == 'bus_added') echo "Bus added successfully!";
                    if($_GET['success'] == 'schedule_added') echo "Schedule added successfully!";
                    if($_GET['success'] == 'deleted') echo "Schedule deleted successfully!";
                    if($_GET['success'] == 'bus_deleted') echo "Bus deleted successfully!";
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Buses</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_buses; ?></p>
                    </div>
                    <i class="fas fa-bus text-4xl text-blue-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Registered Cards</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_cards; ?></p>
                    </div>
                    <i class="fas fa-id-card text-4xl text-green-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active Cards</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $active_cards; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-4xl text-yellow-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Schedules</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_schedules; ?></p>
                    </div>
                    <i class="fas fa-calendar-alt text-4xl text-purple-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Today's Trips</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_trips_today; ?></p>
                    </div>
                    <i class="fas fa-chart-line text-4xl text-red-500"></i>
                </div>
            </div>
        </div>
        
        <!-- Add Bus Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">
                <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Add New Bus
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="text" name="bus_number" placeholder="Bus Number (e.g., BUS102)" required 
                       class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                <input type="number" name="total_seats" placeholder="Total Seats" required 
                       class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                <input type="text" name="route_name" placeholder="Route Name" required 
                       class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                <input type="text" name="driver_name" placeholder="Driver Name" required 
                       class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                <input type="text" name="driver_phone" placeholder="Driver Phone" required 
                       class="px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                <button type="submit" name="add_bus" 
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 rounded transition">
                    <i class="fas fa-save mr-2"></i>Add Bus
                </button>
            </form>
        </div>
        
        <!-- Buses List -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">
                <i class="fas fa-bus text-green-500 mr-2"></i>Registered Buses
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr><th class="px-4 py-2 text-left">Bus Number</th><th class="px-4 py-2 text-left">Route</th><th class="px-4 py-2 text-left">Driver</th><th class="px-4 py-2 text-left">Phone</th><th class="px-4 py-2 text-left">Seats</th><th class="px-4 py-2 text-left">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while($bus = $buses->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-semibold"><?php echo $bus['bus_number']; ?></td>
                            <td class="px-4 py-2"><?php echo $bus['route_name']; ?></td>
                            <td class="px-4 py-2"><?php echo $bus['driver_name']; ?></td>
                            <td class="px-4 py-2"><?php echo $bus['driver_phone']; ?></td>
                            <td class="px-4 py-2"><?php echo $bus['total_seats']; ?></td>
                            <td class="px-4 py-2">
                                <form method="POST" onsubmit="return confirm('Delete this bus?')">
                                    <input type="hidden" name="bus_number" value="<?php echo $bus['bus_number']; ?>">
                                    <button type="submit" name="delete_bus" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add Schedule Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">
                <i class="fas fa-clock text-green-500 mr-2"></i>Add Bus Schedule
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <select name="bus_number" required class="px-3 py-2 border border-gray-300 rounded">
                    <option value="">Select Bus</option>
                    <?php 
                    $buses->data_seek(0);
                    while($bus = $buses->fetch_assoc()): ?>
                        <option value="<?php echo $bus['bus_number']; ?>"><?php echo $bus['bus_number']; ?> - <?php echo $bus['route_name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="time" name="departure_time" required class="px-3 py-2 border border-gray-300 rounded">
                <input type="time" name="arrival_time" required class="px-3 py-2 border border-gray-300 rounded">
                <input type="text" name="source" placeholder="Source" required class="px-3 py-2 border border-gray-300 rounded">
                <input type="text" name="destination" placeholder="Destination" required class="px-3 py-2 border border-gray-300 rounded">
                <input type="text" name="days" placeholder="Days (MON,TUE,WED)" required class="px-3 py-2 border border-gray-300 rounded">
                <input type="number" step="0.01" name="fare" placeholder="Fare ($)" required class="px-3 py-2 border border-gray-300 rounded">
                <button type="submit" name="add_schedule" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded transition">
                    <i class="fas fa-plus mr-2"></i>Add Schedule
                </button>
            </form>
        </div>
        
        <!-- Schedules Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">
                <i class="fas fa-table text-purple-500 mr-2"></i>Bus Schedules
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr><th class="px-4 py-2 text-left">Bus</th><th class="px-4 py-2 text-left">Departure</th><th class="px-4 py-2 text-left">Arrival</th><th class="px-4 py-2 text-left">Route</th><th class="px-4 py-2 text-left">Days</th><th class="px-4 py-2 text-left">Fare</th><th class="px-4 py-2 text-left">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while($schedule = $schedules->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 font-semibold"><?php echo $schedule['bus_number']; ?></td>
                            <td class="px-4 py-2"><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></td>
                            <td class="px-4 py-2"><?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?></td>
                            <td class="px-4 py-2"><?php echo $schedule['source'] . " → " . $schedule['destination']; ?></td>
                            <td class="px-4 py-2"><?php echo $schedule['days_of_week']; ?></td>
                            <td class="px-4 py-2">$<?php echo number_format($schedule['fare'], 2); ?></td>
                            <td class="px-4 py-2">
                                <form method="POST" onsubmit="return confirm('Delete this schedule?')">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" name="delete_schedule" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">
                <i class="fas fa-history text-blue-500 mr-2"></i>Recent Transactions
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr><th class="px-4 py-2 text-left">Time</th><th class="px-4 py-2 text-left">Passenger</th><th class="px-4 py-2 text-left">Card UID</th><th class="px-4 py-2 text-left">Bus</th><th class="px-4 py-2 text-left">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php while($trans = $recent_transactions->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo date('H:i:s', strtotime($trans['transaction_time'])); ?></td>
                            <td class="px-4 py-2"><?php echo $trans['passenger_name']; ?></td>
                            <td class="px-4 py-2 font-mono text-sm"><?php echo $trans['card_uid']; ?></td>
                            <td class="px-4 py-2"><?php echo $trans['bus_number']; ?></td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs <?php echo $trans['action'] == 'entry' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo strtoupper($trans['action']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>