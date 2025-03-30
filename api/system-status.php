<?php
// (Database connection code - same as previous example, using PDO)
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query('SELECT item_name, status FROM system_status');
        $results = $stmt->fetchAll(); // Fetch all rows as associative arrays
        echo json_encode($results);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>