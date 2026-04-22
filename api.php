<?php
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

require_once 'config.php';

// NO logSystemEvent() function here - it's already in config.php

$action = $_GET['action'] ?? '';

switch($action) {
    
    case 'process_rfid':
        $card_uid = strtoupper($conn->real_escape_string($_GET['uid']));
        $bus_number = $conn->real_escape_string($_GET['bus']);
        
        // Check if card is valid and active
        $card_check = $conn->query("SELECT * FROM rfid_cards WHERE card_uid='$card_uid' AND is_active=1");
        if ($card_check->num_rows == 0) {
            echo "INVALID_CARD";
            logSystemEvent($conn, $card_uid, $bus_number, "INVALID_CARD attempt");
            break;
        }
        
        $card_data = $card_check->fetch_assoc();
        $passenger_name = $card_data['passenger_name'];
        
        // Check if passenger is currently ON the bus
        $check_on_bus = $conn->query("
            SELECT COUNT(*) as on_bus 
            FROM passenger_transactions 
            WHERE card_uid='$card_uid' 
            AND bus_number='$bus_number' 
            AND action='entry'
            AND id > IFNULL((
                SELECT MAX(id) 
                FROM passenger_transactions 
                WHERE card_uid='$card_uid' 
                AND action='exit'
            ), 0)
        ");
        
        $is_on_bus = $check_on_bus->fetch_assoc()['on_bus'] > 0;
        
        // Get current seat availability
        $seat_result = $conn->query("SELECT available_seats, total_seats FROM bus_status WHERE bus_number='$bus_number'");
        if ($seat_result && $seat_result->num_rows > 0) {
            $seat_data = $seat_result->fetch_assoc();
            $available_seats = $seat_data['available_seats'];
        } else {
            $available_seats = 40;
        }
        
        if ($is_on_bus) {
            // PASSENGER EXITING
            $conn->query("INSERT INTO passenger_transactions (card_uid, bus_number, action) VALUES ('$card_uid', '$bus_number', 'exit')");
            $conn->query("UPDATE bus_status SET available_seats = available_seats + 1 WHERE bus_number='$bus_number'");
            
            // Update trip count
            $conn->query("UPDATE rfid_cards SET total_trips = total_trips + 1, last_used = NOW() WHERE card_uid='$card_uid'");
            
            // Get fare for this trip
            $current_time = date('H:i:s');
            $fare_result = $conn->query("SELECT fare FROM schedule WHERE bus_number='$bus_number' AND '$current_time' BETWEEN departure_time AND arrival_time LIMIT 1");
            $fare = ($fare_result && $fare_result->num_rows > 0) ? $fare_result->fetch_assoc()['fare'] : 5.00;
            
            // Deduct fare from balance
            $new_balance = $card_data['balance'] - $fare;
            $conn->query("UPDATE rfid_cards SET balance = $new_balance WHERE card_uid='$card_uid'");
            
            // Record in trip history
            $conn->query("INSERT INTO trip_history (card_uid, bus_number, entry_time, exit_time, fare) 
                         VALUES ('$card_uid', '$bus_number', NOW(), NOW(), $fare)");
            
            logSystemEvent($conn, $card_uid, $bus_number, "EXIT - $passenger_name left, Fare: $$fare, Balance: $$new_balance");
            echo "EXIT_SUCCESS";
            
        } else {
            // PASSENGER ENTERING
            if ($available_seats > 0) {
                $conn->query("INSERT INTO passenger_transactions (card_uid, bus_number, action) VALUES ('$card_uid', '$bus_number', 'entry')");
                $conn->query("UPDATE bus_status SET available_seats = available_seats - 1 WHERE bus_number='$bus_number'");
                $conn->query("UPDATE rfid_cards SET last_used = NOW() WHERE card_uid='$card_uid'");
                
                logSystemEvent($conn, $card_uid, $bus_number, "ENTRY - $passenger_name boarded");
                echo "ENTRY_SUCCESS";
            } else {
                echo "NO_SEATS_AVAILABLE";
                logSystemEvent($conn, $card_uid, $bus_number, "REJECTED - No seats available");
            }
        }
        break;
        
    case 'update_location':
        $bus_number = $conn->real_escape_string($_GET['bus']);
        $latitude = floatval($_GET['lat']);
        $longitude = floatval($_GET['lng']);
        $speed = isset($_GET['speed']) ? floatval($_GET['speed']) : 0;
        
        // Validate coordinates
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            echo "INVALID_COORDINATES";
            break;
        }
        
        // Update bus status
        $sql = "UPDATE bus_status 
                SET latitude = '$latitude', 
                    longitude = '$longitude', 
                    speed = '$speed',
                    last_update = NOW() 
                WHERE bus_number = '$bus_number'";
        
        if ($conn->query($sql)) {
            // Store in location history
            $conn->query("INSERT INTO location_history (bus_number, latitude, longitude, speed) 
                         VALUES ('$bus_number', '$latitude', '$longitude', '$speed')");
            echo "OK";
        } else {
            echo "DB_ERROR";
        }
        break;
        
    case 'get_location':
        $bus_number = $conn->real_escape_string($_GET['bus']);
        $result = $conn->query("SELECT latitude, longitude, speed, last_update FROM bus_status WHERE bus_number='$bus_number'");
        
        if ($result && $result->num_rows > 0) {
            $location = $result->fetch_assoc();
            echo json_encode($location);
        } else {
            echo json_encode(['latitude' => 28.6139, 'longitude' => 77.2090, 'speed' => 0, 'last_update' => date('Y-m-d H:i:s')]);
        }
        break;
        
    case 'get_seats':
        $bus_number = $conn->real_escape_string($_GET['bus']);
        $result = $conn->query("SELECT available_seats FROM bus_status WHERE bus_number='$bus_number'");
        if ($result && $result->num_rows > 0) {
            echo $result->fetch_assoc()['available_seats'];
        } else {
            echo "40";
        }
        break;
        
    case 'get_schedule':
        $result = $conn->query("SELECT * FROM schedule ORDER BY departure_time");
        $schedules = [];
        while($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        echo json_encode($schedules);
        break;
        
    case 'heartbeat':
        $bus_number = $conn->real_escape_string($_GET['bus']);
        $status = $conn->real_escape_string($_GET['status'] ?? 'online');
        $conn->query("UPDATE bus_status SET last_update=NOW() WHERE bus_number='$bus_number'");
        echo "HEARTBEAT_RECEIVED";
        break;
        
    default:
        echo "INVALID_ACTION";
}

$conn->close();
?>