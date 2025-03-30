<?php

require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? 'today'; // Get filter, default to 'today'

    try {
        switch ($filter) {
            case 'today':
                $stmt = $pdo->prepare("
                    SELECT HOUR(detection_time) AS hour, COUNT(*) AS count
                    FROM vehicle_detections
                    WHERE DATE(detection_time) = CURDATE()
                    GROUP BY hour
                    ORDER BY hour
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as hour => count

                // Fill in missing hours with 0
                $trafficData = [];
                for ($i = 0; $i < 24; $i++) {
                    $trafficData[] = $results[$i] ?? 0;
                }

                break;

            case 'week':
               $stmt = $pdo->prepare("
                    SELECT DATE(detection_time) AS detection_date, COUNT(*) AS count
                    FROM vehicle_detections
                    WHERE detection_time >= CURDATE() - INTERVAL 6 DAY
                    GROUP BY detection_date
                    ORDER BY detection_date
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Create an array with last 7 days
                $last7Days = [];
                for ($i = 6; $i >= 0; $i--) {
                    $last7Days[date('Y-m-d', strtotime("-$i days"))] = 0;
                }


                // Fill in the counts, keeping the date
                foreach ($results as $row) {
                    $last7Days[$row['detection_date']] = (int)$row['count'];
                }

                $trafficData = array_values($last7Days); //We don't need associative array here.
              break;

            case 'month':
                $stmt = $pdo->prepare("
                  SELECT DATE(detection_time) AS detection_date, COUNT(*) AS count
                  FROM vehicle_detections
                  WHERE detection_time >= CURDATE() - INTERVAL 29 DAY
                  GROUP BY detection_date
                  ORDER BY detection_date
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Create an array with last 30 days
                $last30Days = [];
                for ($i = 29; $i >= 0; $i--) {
                    $last30Days[date('Y-m-d', strtotime("-$i days"))] = 0; // Initialize counts
                }

                // Fill in the counts
               foreach ($results as $row) {
                    $last30Days[$row['detection_date']] = (int)$row['count'];
                }
               $trafficData = array_values($last30Days);

                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid filter']);
                exit;
        }

        echo json_encode($trafficData);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>