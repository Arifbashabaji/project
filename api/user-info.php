<?php
  // (Database connection code - using PDO)
  require_once '../db_connect.php';

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      try{
          //Connect to database.
          //Fetch user information.

          //Temporary data
          $userInfo = [
            "user_name" => "User - x",
            "user_role" => "ANPR System"
          ];

          echo json_encode($userInfo);

      } catch (\PDOException $e) {
          http_response_code(500);
          echo json_encode(['error' => 'Database query failed']);
      }

  }
  else{
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
  }
?>