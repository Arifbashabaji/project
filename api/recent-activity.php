<?php
// (Database connection code - using PDO)
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query('SELECT event_type, message, created_at FROM activity_log ORDER BY created_at DESC LIMIT 5');
        $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($activity);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

?>