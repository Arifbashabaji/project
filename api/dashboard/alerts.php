<?php
// (Database connection code - using PDO)
require_once '../db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT alert_type, license_plate, location, alert_time FROM alerts ORDER BY alert_time DESC LIMIT 5");
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format 'timeAgo' (you can use a library for more robust time formatting)
        $formattedAlerts = [];
        foreach ($alerts as $alert) {
            $timeAgo = time() - strtotime($alert['alert_time']);
            if ($timeAgo < 60) {
                $timeAgo = 'Less than a minute ago';
            } elseif ($timeAgo < 3600) {
                $timeAgo = round($timeAgo / 60) . ' minutes ago';
            } elseif ($timeAgo < 86400) {
                $timeAgo = round($timeAgo / 3600) . ' hours ago';
            } else {
                $timeAgo = round($timeAgo / 86400) . ' days ago';
            }
            $formattedAlerts[] = [
                'type' => strtolower(str_replace(' ', '', $alert['alert_type'])), // "unauthorizedvehicle" or "watchlistmatch"
                'license' => $alert['license_plate'],
                'location' => $alert['location'],
                'timeAgo' => $timeAgo,
            ];
        }

        echo json_encode($formattedAlerts);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
} else {
      http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}