<?php
require_once(__DIR__ . '/../security.php');
require_login();

header('Content-Type: application/json');

include('../db_connection.php');
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("changePassword.php DB connection failed: " . $conn->connect_error);
    die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
}

$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';

if ($currentPassword === '' || $newPassword === '') {
    echo json_encode(["success" => false, "message" => "Both current and new password are required."]);
    exit();
}

if (strlen($newPassword) < 8) {
    echo json_encode(["success" => false, "message" => "New password must be at least 8 characters."]);
    exit();
}

$user = $_SESSION['username'];

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
    echo json_encode(["success" => false, "message" => "Current password is incorrect."]);
    exit();
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE username = ?");
$update->bind_param("ss", $newHash, $user);

if ($update->execute()) {
    echo json_encode(["success" => true, "message" => "Password updated."]);
} else {
    error_log("changePassword.php update failed: " . $update->error);
    echo json_encode(["success" => false, "message" => "Failed to update password."]);
}

$update->close();
$conn->close();
