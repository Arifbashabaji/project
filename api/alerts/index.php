    <?php
    // (Database connection - using PDO)
    require_once '../../db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            // --- Get filter parameters from the query string ---
            $type = $_GET['type'] ?? 'all';
            $location = $_GET['location'] ?? 'all';
            $date = $_GET['date'] ?? null;  // Single date for now
            $licensePlate = $_GET['license_plate'] ?? null;
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;  // Default limit

            // --- Build the WHERE clause based on filters ---
            $where = [];
            $params = [];

            if ($type !== 'all') {
                $where[] = "alert_type = ?";
                $params[] = $type;
            }
            if ($location !== 'all') {
                $where[] = "location = ?";
                $params[] = $location;
            }
            if ($date) {
                $where[] = "DATE(alert_time) >= ?"; // >= for alerts on or after the given date
                $params[] = $date;
            }
            if ($licensePlate) {
                $where[] = "license_plate LIKE ?";
                $params[] = "%$licensePlate%"; // Use % for partial matching
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';


            // --- Count total alerts (for pagination) ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts $whereClause");
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();


            // --- Fetch alerts with filtering, sorting, and pagination ---
            $offset = ($page - 1) * $limit;
            $sql = "SELECT id, alert_type, license_plate, location, alert_time, description, status, is_read
                    FROM alerts
                    $whereClause
                    ORDER BY alert_time DESC
                    LIMIT $limit OFFSET $offset";  // Add LIMIT and OFFSET for pagination

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- Format the data for the frontend ---
              $formattedAlerts = [];
            foreach ($alerts as &$alert) {
              $formattedAlerts[] = [
                'id' => $alert['id'],
                'type' => $alert['alert_type'] === 'critical' ? 'critical' : ($alert['alert_type'] === 'warning' ? 'warning' : 'info'),
                'title' => $alert['alert_type'] === 'critical' ? 'Unauthorized Vehicle Detected' : ($alert['alert_type'] === 'warning' ? 'Camera Obstruction Detected' : 'System Update Available'), // Map alert type to title.
                'time' => $alert['alert_time'],
                'description' => $alert['description'], // Include the description.
                'status' => $alert['status'],
                'isRead' => (bool)$alert['is_read'],
              ];
            }
            // --- Return the data as JSON ---
            echo json_encode([
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'alerts' => $formattedAlerts,
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed', 'details' => $e->getMessage()]); // More details for debugging
        }
    } else {
          http_response_code(405); // Method Not Allowed
          echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    ?>
    