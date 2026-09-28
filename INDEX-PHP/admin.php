<?php
require_once(__DIR__ . '/../security.php');
require_staff();

header('Content-Type: application/json');

include('../db_connection.php');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("admin.php DB connection failed: " . $conn->connect_error);
    die(json_encode(['success' => false, 'message' => 'An error occurred.']));
}

$sql = "SELECT * FROM Field_Work WHERE status = 'pending' ORDER BY completion_time DESC LIMIT 10";
$result = $conn->query($sql);
$requests = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $requests[] = [
            'id' => $row['id'],
            'ticket' => $row['Ticket_Number'],
            'name' => $row['Name'],
            'description' => $row['Work_Description'],
            'difficulty' => intval($row['Difficulty']),
            'time' => $row['completion_time'],
        ];
    }
}

echo json_encode($requests);
$conn->close();
?>
