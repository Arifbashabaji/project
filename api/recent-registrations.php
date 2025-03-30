<?php
// (Database connection - PDO)
require_once '../db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  try {
    $stmt = $pdo->query('SELECT id, license_plate, owner_name, make, model, access_level
                        FROM vehicles
                        ORDER BY created_at DESC
                        LIMIT 3');

    $recentVehicles = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recentVehicles[] = [
          'id' => $row['id'], // Add the ID
          'license_plate' => $row['license_plate'],
          'owner' => $row['owner_name'],
          'vehicle' => $row['make'] . ' ' . $row['model'],
          'access_level' => $row['access_level'],
          'status' => 'active', // You might derive this from a column in your table
      ];

    }
     echo json_encode($recentVehicles);

  } catch (\PDOException $e){
      http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
  }
} else {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed.']);
}