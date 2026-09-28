<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');
    
    // Define your date range (ensure these variables are set correctly)
    $start_date = '2023-01-01'; // Example start date
    $end_date = '2023-12-31'; // Example end date

    // Prepare the stored procedure call
    $stmt = $conn->prepare("CALL DateRangeProcedure3(:start_date, :end_date)");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    
    // Execute the stored procedure
    $stmt->execute();

    // Fetch all rows from the result set
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if data is empty and provide a message
    if (empty($data)) {
        echo json_encode(["message" => "No data found for the specified date range."]);
    } else {
        // Output the data in JSON format
        echo json_encode($data);
    }

} catch (PDOException $e) {
    error_log("chartjs.php DB error: " . $e->getMessage());
    echo json_encode(["error" => "An error occurred."]);
}

// Close the connection
$conn = null;
?>
