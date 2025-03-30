<?php
// (Database connection code - using PDO)
require_once '../db_connect.php'; //Assuming you have a separate file.

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // --- Total Vehicles Today ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE()");
        $stmt->execute();
        $totalVehicles = $stmt->fetchColumn();

        // --- Total Vehicles Yesterday ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE() - INTERVAL 1 DAY");
        $stmt->execute();
        $totalVehiclesYesterday = $stmt->fetchColumn();

        // --- Calculate Change (as a percentage) ---
          $totalVehiclesChange = 0;
        if ($totalVehiclesYesterday > 0) {
            $totalVehiclesChange = round((($totalVehicles - $totalVehiclesYesterday) / $totalVehiclesYesterday) * 100);
        }


        // --- Authorized Vehicles Today ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE() AND is_authorized = 1");
        $stmt->execute();
        $authorizedVehicles = $stmt->fetchColumn();

        // --- Authorized Vehicles Yesterday ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE() - INTERVAL 1 DAY AND is_authorized = 1");
        $stmt->execute();
        $authorizedVehiclesYesterday = $stmt->fetchColumn();

        // --- Calculate Change ---
          $authorizedVehiclesChange = 0;
        if ($authorizedVehiclesYesterday > 0) {
            $authorizedVehiclesChange = round((($authorizedVehicles - $authorizedVehiclesYesterday) / $authorizedVehiclesYesterday) * 100);
        }

        // --- Unauthorized Vehicles Today ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE() AND is_authorized = 0");
        $stmt->execute();
        $unauthorizedVehicles = $stmt->fetchColumn();

        // --- Unauthorized Vehicles Yesterday ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_detections WHERE DATE(detection_time) = CURDATE() - INTERVAL 1 DAY AND is_authorized = 0");
        $stmt->execute();
        $unauthorizedVehiclesYesterday = $stmt->fetchColumn();

        // --- Calculate Change ---
        $unauthorizedVehiclesChange = ($unauthorizedVehiclesYesterday > 0) ? $unauthorizedVehicles - $unauthorizedVehiclesYesterday : 0;


          // --- Total alerts today ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE DATE(alert_time) = CURDATE()");
        $stmt->execute();
        $alerts = $stmt->fetchColumn();

        // --- Total alerts Yesterday ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE DATE(alert_time) = CURDATE() - INTERVAL 1 DAY");
        $stmt->execute();
        $alertsYesterday = $stmt->fetchColumn();

        // --- Calculate change ---
        $alertsChange = ($alertsYesterday > 0) ? $alerts - $alertsYesterday : 0;




        // --- Return as JSON ---
        echo json_encode([
            'totalVehicles' => (int)$totalVehicles,
            'totalVehiclesChange' => (int)$totalVehiclesChange,
            'alerts' => (int)$alerts,
            'alertsChange' =>(int) $alertsChange,
            'authorizedVehicles' => (int)$authorizedVehicles,
            'authorizedVehiclesChange' => (int)$authorizedVehiclesChange,
            'unauthorizedVehicles' => (int)$unauthorizedVehicles,
            'unauthorizedVehiclesChange' => (int)$unauthorizedVehiclesChange
        ]);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]); // Log, don't show to user
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>