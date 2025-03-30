    <?php
    // (Database connection - PDO)
    require_once '../../db_connect.php';

    if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $id = basename($_SERVER['PATH_INFO']);
        if (!ctype_digit($id)) { // Check if the ID is a number
          http_response_code(400);
          echo json_encode(['success' => false, 'message' => 'Invalid Alert ID']);
          exit;
        }

        try {
          // Update status in alerts table.
          $stmt = $pdo->prepare("UPDATE alerts SET status = 'resolved' WHERE id = ?"); // Assuming you have 'resolved'
          $stmt->execute([$id]);

          if ($stmt->rowCount() > 0) {
            http_response_code(200); // OK
            echo json_encode(['success' => true, 'message' => 'Alert resolved']);
          } else {
            http_response_code(404); // Not found - if alert with given ID doesn't exists.
            echo json_encode(['success' => false, 'message' => 'Alert not found or already resolved']);
          }


        } catch (\PDOException $e) {
          http_response_code(500); // Internal Server Error
          echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
        }

    } else {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

    // Close the connection
    $pdo = null;