<?php
// (Database connection - using PDO)
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = basename($_SERVER['PATH_INFO']); // Get the ID from the URL

    if (!ctype_digit($id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid alert ID']);
        exit;
    }

    try {
        // Fetch alert details, including vehicle detection information if available
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.alert_type,
                a.license_plate,
                a.location,
                a.alert_time,
                a.description,
                a.status,
                a.is_read,
                v.make,
                v.model,
                v.color,
                v.vehicle_image_path,
                vd.image_path AS detection_image_path
            FROM alerts a
            LEFT JOIN vehicle_detections vd ON a.vehicle_detection_id = vd.id
            LEFT JOIN vehicles v ON a.license_plate = v.license_plate
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $alert = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$alert) {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Alert not found']);
            exit;
        }
        //Format the alert data for the frontend
        $formattedAlert = [
          'id' => $alert['id'],
          'type' => $alert['alert_type'] === 'critical' ? 'critical' : ($alert['alert_type'] === 'warning' ? 'warning' : 'info'),
          'title' => $alert['alert_type'] === 'Unauthorized Vehicle' ? 'Unauthorized Vehicle Detected' : ($alert['alert_type'] === 'Camera Obstruction' ? 'Camera Obstruction Detected' : ($alert['alert_type'] === 'Watchlist Match' ? 'Watchlist Match' : 'System Update Available' )), // Map alert type to title.
          'licensePlate' => $alert['license_plate'],
          'location' => $alert['location'],
          'time' => $alert['alert_time'],
          'description' => $alert['description'],
          'status' => $alert['status'],
          'isRead' => (bool)$alert['is_read'],
          'vehicle' => [  // Vehicle details (may be null)
              'make' => $alert['make'],
              'model' => $alert['model'],
              'color' => $alert['color'],
              'imagePath' => $alert['vehicle_image_path'],
          ],
          'detectionImage' => $alert['detection_image_path'], // Detection image (may be null)

        ];

        echo json_encode($formattedAlert);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}