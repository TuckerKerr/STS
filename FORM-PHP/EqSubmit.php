<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    $full_name = $_SESSION['username'];
    $dropper_name = $_POST['deliverer'];
    $asset_tag = $_POST['asset_tag'];
    $Staff_Member_Assigned = $_POST['Staff_Member_Assigned']; 
    $additional_info = $_POST['additional_info'];
    $start_time = $_POST['form_open_time'];
    $completion_time = date('Y-m-d H:i:s'); 

    // Prepare SQL statement to insert data into MySQL table
    $sql = "INSERT INTO equipment_return (Start_time, Completion_time, Name, Dropper_Name, JWU_Asset_Tag, Staff_Member_Assigned, Additional_Info) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(1, $start_time);
    $stmt->bindParam(2, $completion_time);
    $stmt->bindParam(3, $full_name);
    $stmt->bindParam(4, $dropper_name);
    $stmt->bindParam(5, $asset_tag);
    $stmt->bindParam(6, $Staff_Member_Assigned);
    $stmt->bindParam(7, $additional_info);

    // Execute the statement
    $stmt->execute();

    // Redirect to the thank you page
    header("Location: ../INDEX-HTML/main.html");
    exit();
} catch(PDOException $e) {
    error_log("EqSubmit.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>
