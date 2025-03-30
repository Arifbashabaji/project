<?php
// (Database connection code - using PDO)
require_once 'anpr_system\db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT license_plate, detection_time, location, is_authorized FROM vehicle_detections ORDER BY detection_time DESC LIMIT 6");
        $stmt->execute();
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format the data for the frontend
        $formattedVehicles = [];
        foreach ($vehicles as $vehicle) {
            $formattedVehicles[] = [
                'license' => $vehicle['license_plate'],
                'time' => date('h:i A', strtotime($vehicle['detection_time'])), // Format as "11:45 AM"
                'location' => $vehicle['location'],
                'status' => $vehicle['is_authorized'] ? 'authorized' : 'unauthorized',
            ];
        }

        echo json_encode($formattedVehicles);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
}
else{
  http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>