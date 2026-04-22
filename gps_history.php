<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$bus_number = $_GET['bus'] ?? 'BUS101';
$history = $conn->query("SELECT * FROM location_history WHERE bus_number='$bus_number' ORDER BY recorded_at DESC LIMIT 200");
$bus_list = $conn->query("SELECT bus_number FROM buses ORDER BY bus_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS History - Bus Tracking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <nav class="bg-gradient-to-r from-blue-800 to-purple-800 shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-map-marked-alt text-white text-2xl"></i>
                    <h1 class="text-white text-xl font-bold">GPS History</h1>
                </div>
                <a href="admin_dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Bus Selector -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <label class="block text-gray-700 font-semibold mb-2">Select Bus:</label>
            <select id="bus_select" class="px-4 py-2 border rounded-lg">
                <?php while($bus = $bus_list->fetch_assoc()): ?>
                    <option value="<?php echo $bus['bus_number']; ?>" <?php echo $bus_number == $bus['bus_number'] ? 'selected' : ''; ?>>
                        <?php echo $bus['bus_number']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Map -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div id="map" style="height: 500px; border-radius: 8px;"></div>
        </div>
        
        <!-- History Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b">
                <h3 class="font-semibold text-gray-800">Location History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Time</th>
                            <th class="px-4 py-3 text-left">Latitude</th>
                            <th class="px-4 py-3 text-left">Longitude</th>
                            <th class="px-4 py-3 text-left">Speed (km/h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $points = [];
                        while($row = $history->fetch_assoc()): 
                            $points[] = [$row['latitude'], $row['longitude']];
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?php echo date('Y-m-d H:i:s', strtotime($row['recorded_at'])); ?></td>
                            <td class="px-4 py-2 font-mono"><?php echo $row['latitude']; ?></td>
                            <td class="px-4 py-2 font-mono"><?php echo $row['longitude']; ?></td>
                            <td class="px-4 py-2"><?php echo $row['speed']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(empty($points)): ?>
                            <tr><td colspan="4" class="text-center py-8 text-gray-500">No GPS data available yet. Wait for bus to send location.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize map
        var map = L.map('map').setView([28.6139, 77.2090], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Route points from PHP
        var points = <?php echo json_encode($points); ?>;
        
        if(points.length > 0) {
            // Draw route line
            var routeLine = L.polyline(points, {
                color: '#2563eb',
                weight: 3,
                opacity: 0.8
            }).addTo(map);
            
            // Fit map to route bounds
            map.fitBounds(routeLine.getBounds());
            
            // Add start and end markers
            if(points.length > 0) {
                L.marker(points[0]).bindPopup('<b>Start Point</b>').addTo(map);
                L.marker(points[points.length-1]).bindPopup('<b>Latest Location</b>').addTo(map);
            }
        }
        
        // Bus selector change
        document.getElementById('bus_select').addEventListener('change', function() {
            window.location.href = 'gps_history.php?bus=' + this.value;
        });
    </script>
</body>
</html>