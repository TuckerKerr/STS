<?php
require_once(__DIR__ . '/../security.php');
require_staff();

try {
    include('../pdo_connect.php');

    $text = $_POST['text'];
    $time = $_POST['time'];

    $sql = "UPDATE announcements SET visible = 0 WHERE announcement = ? AND date_posted = ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(1, $text);
    $stmt->bindParam(2, $time);
    // Execute the statement
    $stmt->execute();

    header("Location: ../INDEX-HTML/admin.html");
    exit();
} catch(PDOException $e) {
    error_log("AcDelete.php DB error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred."]);
}
?>
