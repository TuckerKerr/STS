<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    // Query to count rooms checked in the last 7 days
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT building, room_number) AS checked_rooms FROM room_check WHERE date_checked >= (CURDATE() - INTERVAL 7 DAY)");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $checkedRooms = $result['checked_rooms'];

    // Query to get the total number of rooms from buildingRooms
    $stmtRooms = $conn->prepare("SELECT COUNT(*) AS total_rooms FROM buildingRooms");
    $stmtRooms->execute();
    $resultRooms = $stmtRooms->fetch(PDO::FETCH_ASSOC);
    $expectedRooms = $resultRooms['total_rooms'];

    // Calculate unchecked rooms
    $uncheckedRooms = max($expectedRooms - $checkedRooms, 0);

    // Prepare JSON data for Chart.js
    $chartData = [
        "labels" => ["Checked Rooms", "Unchecked Rooms"],
        "values" => [$checkedRooms, $uncheckedRooms]
    ];

    header('Content-Type: application/json');
    echo json_encode($chartData);

} catch (PDOException $e) {
    error_log("roomChart.php DB error: " . $e->getMessage());
    echo json_encode(["error" => "An error occurred."]);
}
?>