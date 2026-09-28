<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    // Retrieve form data
    $Difficulty = $_POST['range'];
    $name = $_SESSION['username'];
    $Ticket_Number = $_POST['Ticket_Number'];
    $Work_Description = $_POST['Work_Description']; // Ensure this matches the input name in HTML
    $completion_time = date('Y-m-d H:i:s');
    // Prepare SQL statement to insert data into MySQL table
    $sql = "INSERT INTO Field_Work (Work_Description, Ticket_Number, Name, completion_time, difficulty) VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(1, $Work_Description); // Binds the correct Work_Done input
    $stmt->bindParam(2, $Ticket_Number);
    $stmt->bindParam(3, $name);
    $stmt->bindParam(4, $completion_time);
    $stmt->bindParam(5, $Difficulty);
    
    // Execute the statement
    $stmt->execute();

    // Redirect to the thank you page
    header("Location: ../INDEX-HTML/main.html");
    exit();
} catch(PDOException $e) {
    error_log("FWSubmit.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>
