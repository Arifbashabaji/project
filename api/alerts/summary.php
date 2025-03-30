    <?php
    // (Database connection - using PDO)
    require_once '../../db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $summary = [];

            // --- Critical Alerts ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'critical' AND status = 'pending'"); // Example assuming 'critical' type
            $stmt->execute();
            $summary['critical']['total'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'critical' AND is_read = 0");
            $stmt->execute();
            $summary['critical']['unread'] = (int)$stmt->fetchColumn();

            // --- Warning Alerts ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'warning' AND status = 'pending'");
            $stmt->execute();
            $summary['warning']['total'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'warning' AND is_read = 0");
            $stmt->execute();
            $summary['warning']['unread'] = (int)$stmt->fetchColumn();

            // --- Info Alerts ---
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'info' AND status = 'pending'");
            $stmt->execute();
            $summary['info']['total'] = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE alert_type = 'info' AND is_read = 0");
            $stmt->execute();
            $summary['info']['unread'] = (int)$stmt->fetchColumn();


            echo json_encode($summary);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database query failed']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    ?>