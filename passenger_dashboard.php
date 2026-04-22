<?php
require_once 'config.php';

$buses = $conn->query("SELECT * FROM buses WHERE status='active'");
$schedules = $conn->query("SELECT s.*, b.route_name FROM schedule s JOIN buses b ON s.bus_number = b.bus_number ORDER BY s.departure_time");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard - Live Bus Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        #map { height: 500px; width: 100%; border-radius: 8px; }
        .info-card { transition: transform 0.3s; }
        .info-card:hover { transform: translateY(-5px); }
        .bus-marker {
            background: #2563eb;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            white-space: nowrap;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .bus-marker i { margin-right: 5px; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-bus text-white text-2xl"></i>
                <h1 class="text-white text-2xl font-bold">Live Bus Tracking System</h1>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Bus Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <i class="fas fa-bus mr-2 text-blue-500"></i>Select Bus
            </h2>
            <select id="bus_selector" class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <?php while($bus = $buses->fetch_assoc()): ?>
                    <option value="<?php echo $bus['bus_number']; ?>" data-total="<?php echo $bus['total_seats']; ?>">
                        <?php echo $bus['bus_number']; ?> - <?php echo $bus['route_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Live Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="info-card bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Available Seats</p>
                        <p class="text-3xl font-bold" id="available_seats">--</p>
                        <p class="text-xs mt-2">Total: <span id="total_seats">--</span></p>
                    </div>
                    <i class="fas fa-chair text-4xl opacity-50"></i>
                </div>
            </div>
            
            <div class="info-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Bus Speed</p>
                        <p class="text-3xl font-bold" id="bus_speed">--</p>
                        <p class="text-xs mt-2">km/h</p>
                    </div>
                    <i class="fas fa-tachometer-alt text-4xl opacity-50"></i>
                </div>
            </div>
            
            <div class="info-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Last Update</p>
                        <p class="text-xl font-bold" id="last_update">--</p>
                    </div>
                    <i class="fas fa-clock text-4xl opacity-50"></i>
                </div>
            </div>
            
            <div class="info-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-md p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Status</p>
                        <p class="text-xl font-bold" id="bus_status">--</p>
                    </div>
                    <i class="fas fa-info-circle text-4xl opacity-50"></i>
                </div>
            </div>
        </div>
        
        <!-- Map and Schedule -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-4">
                <h3 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Live Bus Location
                </h3>
                <div id="map"></div>
                <p class="text-xs text-gray-500 mt-2 text-center">
                    <i class="fas fa-info-circle"></i> Map updates every 3 seconds automatically
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-calendar-alt text-green-500 mr-2"></i>Bus Schedule
                </h3>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php while($schedule = $schedules->fetch_assoc()): ?>
                        <div class="border-l-4 border-blue-500 bg-gray-50 p-3 rounded hover:shadow-md transition">
                            <div class="font-semibold text-gray-800"><?php echo $schedule['bus_number']; ?></div>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?> → 
                                <?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-route mr-1"></i>
                                <?php echo $schedule['source']; ?> → <?php echo $schedule['destination']; ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-dollar-sign mr-1"></i>$<?php echo number_format($schedule['fare'], 2); ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-calendar-week mr-1"></i><?php echo $schedule['days_of_week']; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize map with default center (Kigali, Rwanda)
        var map = L.map('map').setView([-1.97859, 30.10477], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        var busMarker = null;
        var busRoute = [];
        var routeLine = null;
        var currentBus = $('#bus_selector').val();
        
        // Custom bus icon
        var busIcon = L.divIcon({
            className: 'custom-div-icon',
            html: '<div class="bus-marker"><i class="fas fa-bus"></i> BUS</div>',
            iconSize: [60, 30],
            popupAnchor: [0, -15]
        });
        
        function fetchBusData() {
            var busNumber = $('#bus_selector').val();
            var totalSeats = $('#bus_selector option:selected').data('total');
            
            console.log('Fetching data for bus:', busNumber);
            
            // Get location from API
            $.ajax({
                url: 'api.php?action=get_location&bus=' + busNumber,
                method: 'GET',
                dataType: 'json',
                success: function(loc) {
                    console.log('Location received:', loc);
                    
                    // Parse coordinates (they come as strings)
                    var lat = parseFloat(loc.latitude);
                    var lng = parseFloat(loc.longitude);
                    var speed = parseFloat(loc.speed) || 0;
                    
                    console.log('Parsed coordinates:', lat, lng);
                    
                    // Validate coordinates
                    if (isNaN(lat) || isNaN(lng)) {
                        console.error('Invalid coordinates');
                        return;
                    }
                    
                    // Update or create bus marker
                    if (busMarker) {
                        busMarker.setLatLng([lat, lng]);
                    } else {
                        busMarker = L.marker([lat, lng], {icon: busIcon}).addTo(map);
                    }
                    
                    // Update popup content
                    busMarker.bindPopup(`
                        <b>🚌 ${busNumber}</b><br>
                        Speed: ${speed.toFixed(1)} km/h<br>
                        Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>
                        <i>Last updated: ${new Date(loc.last_update).toLocaleTimeString()}</i>
                    `).openPopup();
                    
                    // Center map on bus (optional - comment out if you want manual navigation)
                    // map.panTo([lat, lng]);
                    
                    // Update UI
                    $('#last_update').text(new Date(loc.last_update).toLocaleTimeString());
                    $('#bus_speed').text(speed.toFixed(1));
                    
                    // Track route
                    busRoute.push([lat, lng]);
                    if(busRoute.length > 50) busRoute.shift();
                    
                    // Draw route line
                    if(routeLine) map.removeLayer(routeLine);
                    routeLine = L.polyline(busRoute, {
                        color: '#2563eb',
                        weight: 3,
                        opacity: 0.7
                    }).addTo(map);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching location:', error);
                    console.log('Response:', xhr.responseText);
                }
            });
            
            // Get seat availability
            $.ajax({
                url: 'api.php?action=get_seats&bus=' + busNumber,
                method: 'GET',
                success: function(seats) {
                    console.log('Seats available:', seats);
                    $('#available_seats').text(seats);
                    $('#total_seats').text(totalSeats);
                    
                    var percentage = (seats / totalSeats) * 100;
                    if(percentage < 20) {
                        $('#bus_status').text('Almost Full').css('color', '#ef4444');
                    } else if(percentage < 50) {
                        $('#bus_status').text('Limited Seats').css('color', '#f59e0b');
                    } else {
                        $('#bus_status').text('Seats Available').css('color', '#10b981');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching seats:', error);
                    $('#available_seats').text('Error');
                }
            });
        }
        
        // Change bus selection
        $('#bus_selector').change(function() {
            console.log('Bus changed to:', $(this).val());
            busRoute = [];
            if(routeLine) map.removeLayer(routeLine);
            fetchBusData();
        });
        
        // Initial fetch
        fetchBusData();
        
        // Update every 3 seconds
        setInterval(fetchBusData, 3000);
        
        // Add zoom controls
        map.zoomControl.setPosition('bottomright');
    </script>
</body>
</html>