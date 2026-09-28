<?php
require_once(__DIR__ . '/../security.php');
require_login();

 if (isset($_POST['submit'])) {


    // Database connection parameters
    include('../db_connection.php');
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

//connection check
if ($conn->connect_error) {
    error_log("DateRange.php DB connection failed: " . $conn->connect_error);
    die(json_encode(["error" => "An error occurred."]));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];

    $stmt = $conn->prepare('CALL DateRangeProcedure3(?, ?)');
    $stmt->bind_param('ss', $date1, $date2);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
		 // Encode data to JSON
        $jsonData = json_encode($data);
		 // Return JSON data
            echo $jsonData;
		
    } else {
        error_log("DateRange.php query error: " . $stmt->error);
        echo json_encode(["error" => "An error occurred."]);
    }

    $stmt->close();
    $conn->close();
	}
 }
 
?>