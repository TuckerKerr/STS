<?php
session_start(); // Start the session

try {
    include('../pdo_connect.php');

    // Capture form data
    $resolved = $_POST['resolved'];
    $clean = $_POST['clean'];
    $satisfaction = $_POST['satisfaction'];
// Check if 'feedback' is set before accessing it to avoid undefined array key warning
$feedback = isset($_POST['feedback']) ? $_POST['feedback'] : '';  // Default to empty string if not set

    $follow_up = $_POST['follow_up'];
    $contact_email = isset($_POST['contact_email']) ? $_POST['contact_email'] : null;
    $contact_phone = isset($_POST['contact_phone']) ? $_POST['contact_phone'] : null;
    $rating = $_POST['rating'];
    $form_submit_time = $_POST['form_submit_time'];

    // Prepare SQL statement to insert data into MySQL table
    $sql = "INSERT INTO student_assessment (resolved, satisfaction, feedback, rating, form_submit_time) 
    VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    // Bind values to placeholders
    $stmt->bindValue(1, $resolved);
    $stmt->bindValue(2, $satisfaction);
    $stmt->bindValue(3, $feedback);
    $stmt->bindValue(4, $rating);
    $stmt->bindValue(5, $form_submit_time);

    // Execute the query
    $stmt->execute();

    // Redirect to the thank you page
    header("Location: /sts/thankyou.html");
    exit();

} catch(PDOException $e) {
    error_log("review.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>
