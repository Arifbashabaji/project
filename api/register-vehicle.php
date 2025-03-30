<?php
// (Database connection code - using PDO)
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Helper function for secure file uploads ---
    function handleFileUpload($file, $fieldName, &$errors) {
        $uploadDir = __DIR__ . '/../uploads/'; // UPLOADS FOLDER OUTSIDE WEB ROOT!
        if (!is_dir($uploadDir)) {
           if(!mkdir($uploadDir, 0755, true)){ // Try creating directory
            $errors[$fieldName] = "Could not create upload directory.";
            return false;
           }
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        if ($file['error'] === UPLOAD_ERR_OK) {
            $tempName = $file['tmp_name'];
            $originalName = basename($file['name']); // Get the filename.
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $uniqueName = uniqid('', true) . '.' . $extension; // Generate a unique name
            $filePath = $uploadDir . $uniqueName;

            // --- Validation ---
             if (!in_array($extension, $allowedExtensions)) {
                $errors[$fieldName] = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
                return false;
            }
            if ($file['size'] > $maxFileSize) {
                $errors[$fieldName] = "File is too large. Maximum size is " . ($maxFileSize / (1024 * 1024)) . "MB";
                return false;
            }

            // --- Move the uploaded file ---
            if (move_uploaded_file($tempName, $filePath)) {
                return $filePath; // Return the full file path
            } else {
                $errors[$fieldName] = "Failed to move uploaded file.";
                return false;
            }
        } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file was uploaded, but that's okay (optional)
        }
         else {
            // Handle other upload errors (see PHP documentation)
            $errors[$fieldName] = "Upload error: " . $file['error'];
            return false;
        }
    }


    // --- Get JSON data for non-file fields ---
      $data = json_decode(file_get_contents('php://input'), true); //Gets data for non files
     $errors = [];

      // If $data is null, it means non-file data was NOT sent as JSON.
      //  This is expected when using FormData for file uploads.
    if ($data === null) {
        $data = $_POST; // Use $_POST for non-file data with FormData.
    }



    // --- VALIDATION (Crucial - Expand this!) ---
    if (empty($data['licensePlate'])) {
        $errors['licensePlate'] = 'License plate is required';
    }
     // Validate other required fields...


    // --- Handle file uploads ---
      $vehicleImagePath = null;
    if (isset($_FILES['vehicleImage'])) { //Use $_FILES for files.
        $vehicleImagePath = handleFileUpload($_FILES['vehicleImage'], 'vehicleImage', $errors);
    }

    $plateImagePath = null;
    if (isset($_FILES['plateImage'])) {  //Use $_FILES for files.
        $plateImagePath = handleFileUpload($_FILES['plateImage'], 'plateImage', $errors);
    }



    if (!empty($errors)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Validation errors', 'errors' => $errors]);
        exit;
    }

   // --- Sanitize (Basic example - use more robust methods) ---
    $licensePlate = htmlspecialchars($data['licensePlate']);
    // Sanitize other fields, including making booleans from the checkboxes:

      // --- Prepare checkbox values (default to false if not present) ---
    $accessMainGate = isset($data['mainGate']) && ($data['mainGate'] === 'on' || $data['mainGate'] === 'true' || $data['mainGate'] === 1 || $data['mainGate'] === true) ? 1 : 0;
    $accessEmployeeParking = isset($data['employeeParking']) && ($data['employeeParking'] === 'on' || $data['employeeParking'] === 'true' || $data['employeeParking'] === 1 ||  $data['employeeParking'] === true) ? 1 : 0;
     $accessVisitorParking = isset($data['visitorParking']) && ($data['visitorParking'] === 'on' || $data['visitorParking'] === 'true' || $data['visitorParking'] === 1 || $data['visitorParking'] === true) ? 1 : 0;
    $accessDeliveryArea = isset($data['deliveryArea']) && ($data['deliveryArea'] === 'on' || $data['deliveryArea'] === 'true' || $data['deliveryArea'] === 1 ||  $data['deliveryArea'] === true ) ? 1 : 0;
    $accessRestrictedZone = isset($data['restrictedZone']) && ($data['restrictedZone'] === 'on' || $data['restrictedZone'] === 'true' || $data['restrictedZone'] === 1 ||  $data['restrictedZone'] === true) ? 1 : 0;
    $validUntil = !empty($data['validUntil']) ? $data['validUntil'] : null;
    $contactEmail = !empty($data['contactEmail']) ? $data['contactEmail'] : null;



    // --- Insert into database (using prepared statement) ---
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                license_plate, vehicle_type, make, model, year, color,
                owner_name, owner_id, contact_phone, contact_email,
                access_level, valid_until,
                access_main_gate, access_employee_parking, access_visitor_parking,
                access_delivery_area, access_restricted_zone, notes, vehicle_image_path, plate_image_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $licensePlate, $data['vehicleType'], $data['make'], $data['model'], $data['year'], $data['color'],
            $data['ownerName'], $data['ownerID'], $data['contactPhone'],$contactEmail,
            $data['accessLevel'], $validUntil,
           $accessMainGate, $accessEmployeeParking,$accessVisitorParking,
            $accessDeliveryArea, $accessRestrictedZone, $data['notes'], $vehicleImagePath, $plateImagePath
        ]);


        $vehicleId = $pdo->lastInsertId(); // Get the ID of the newly inserted vehicle

        http_response_code(201); // Created
        echo json_encode(['success' => true, 'message' => 'Vehicle registered successfully', 'vehicle_id' => $vehicleId]);

    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>