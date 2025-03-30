<?php
// (Database connection - PDO)
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Get the ID from the URL
    $id = basename($_SERVER['PATH_INFO']); // Extract ID from the URL.
    if (!ctype_digit($id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid vehicle ID']);
        exit;
    }
    try {
        // --- First, get the image paths (if any) ---
          $stmt = $pdo->prepare("SELECT vehicle_image_path, plate_image_path FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        // --- Delete the record from the database ---
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);

      // --- Delete files from the file system, Important ---
      if ($vehicle) { // Check if vehicle data exists
        if (!empty($vehicle['vehicle_image_path']) && file_exists($vehicle['vehicle_image_path'])) {
            unlink($vehicle['vehicle_image_path']); // Delete vehicle image
        }
        if (!empty($vehicle['plate_image_path']) && file_exists($vehicle['plate_image_path'])) {
            unlink($vehicle['plate_image_path']); // Delete plate image
        }
    }

        http_response_code(200); // OK
        echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully']);

    } catch (\PDOException $e) {
        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
              http_response_code(405); // Method Not Allowed
              echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        ?>