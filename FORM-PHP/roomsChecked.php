<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    // Retrieve form data
    $weekStart = new DateTime();
    $weekStart ->modify('monday this week')->setTime(0,0,0);
    $weekStartstr = $weekStart ->format('Y-m-d H:i:s');

    $weekEnd = new DateTime();
    $weekEnd ->modify('sunday this week')->setTime(23,59,59);
    $weekEndstr = $weekEnd->format('Y-m-d H:i:s');

    $buildings = $_GET['building'];
    $rooms = "";

    // Prepare SQL statement to insert data into base_delivery_intake table
    $sql = "SELECT room_number FROM room_check WHERE building LIKE ? AND Completion_time BETWEEN ? AND ? ORDER BY room_number DESC";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(1, $buildings);
    $stmt->bindParam(2, $weekStartstr);
    $stmt->bindParam(3, $weekEndstr);
    
    // Execute the statement
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($results as $data){
        $rooms = $rooms . $data['room_number'] . ", ";
    }

    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
    ]);

    exit();
} catch(PDOException $e) {
    error_log("roomsChecked.php DB error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred."]);
}
?>
