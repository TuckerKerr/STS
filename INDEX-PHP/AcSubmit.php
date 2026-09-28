<?php
require_once(__DIR__ . '/../security.php');
require_staff();

try {
    include('../pdo_connect.php');

    $full_name = $_SESSION['username'];
    $annoucement = $_POST['announcementText'];
    $subject = $_POST['announcementSubject'];
    $completion_time = date('Y-m-d H:i:s');

    // Prepare SQL statement to insert data into MySQL table
    $sql = "INSERT INTO announcements (staff, subjectLine, announcement, date_posted) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(1, $full_name);
    $stmt->bindParam(2, $subject);
    $stmt->bindParam(3, $annoucement);
    $stmt->bindParam(4, $completion_time);

    // Execute the statement
    $stmt->execute();

    // Webhook URL/signature live server-side in db_connection.php (not committed to source control)
    include('../db_connection.php');
    if (!empty($announcementWebhookUrl)) {
        $payload = [
            "announcementTitle" => $subject,
            "announcementMessage" => $annoucement
        ];

        $ch = curl_init($announcementWebhookUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("Webhook Error: " . curl_error($ch));
        }
    }

    // Redirect to the thank you page
    header("Location: ../INDEX-HTML/admin.html");
    exit();
} catch(PDOException $e) {
    error_log("AcSubmit.php DB error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred."]);
}
?>
