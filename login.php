<?php
ini_set('display_errors', 0); // Disable error display in the response
ini_set('log_errors', 1);    // Enable error logging

require_once(__DIR__ . '/security.php');
secure_session_start();

// Database connection parameters
include(__DIR__ . '/db_connection.php');

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("login.php DB connection failed: " . $conn->connect_error);
    die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
}

// Function to log debug information (never pass password hashes or full row data)
function debugLog($message, $data = null) {
    error_log("Login Debug - " . $message . ($data !== null ? ": " . print_r($data, true) : ""));
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

function recordLoginAttempt($conn, $user, $ip, $success) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
    if ($stmt) {
        $successInt = $success ? 1 : 0;
        $stmt->bind_param("ssi", $user, $ip, $successInt);
        $stmt->execute();
        $stmt->close();
    }
}

function isLockedOut($conn, $user) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS attempts FROM login_attempts WHERE username = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['attempts'] >= 5;
}

// Get the form data
if (isset($_POST['username']) && isset($_POST['password'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if (isLockedOut($conn, $user)) {
        debugLog("Login blocked due to too many recent failed attempts", $user);
        echo json_encode(["success" => false, "message" => "Too many failed login attempts. Please try again later."]);
        $conn->close();
        exit();
    }

    debugLog("Attempting login for user", $user);

    // Modified query to include agreement status (ENUM column)
    $sql = "
        SELECT
            u.password_hash,
            u.agreement,
            u.id,
            u.email,
            u.must_change_password,
            COALESCE(c.campus, 'student') as campus,
            CASE
                WHEN c.campus = 'staff' THEN 1
                ELSE 0
            END as is_staff
        FROM users u
        LEFT JOIN campus_student_workers c ON u.username = c.student_name
        WHERE u.username = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("login.php prepare failed: " . $conn->error);
        die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
    }

    $stmt->bind_param("s", $user);

    if (!$stmt->execute()) {
        error_log("login.php execute failed: " . $stmt->error);
        recordLoginAttempt($conn, $user, $clientIp, false);
        die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the password
        if (password_verify($pass, $row['password_hash'])) {
            recordLoginAttempt($conn, $user, $clientIp, true);

            // Regenerate the session ID on every successful login to prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['username'] = $user;
            $_SESSION['is_staff'] = (bool)$row['is_staff'];
            $_SESSION['campus'] = $row['campus'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            unset($_SESSION['csrf_token']); // force a fresh CSRF token for the new session

            debugLog("Login succeeded for user", $user);

            if ((bool)$row['must_change_password']) {
                echo json_encode([
                    "success" => true,
                    "needs_password_change" => true,
                    "name" => $user,
                    "is_staff" => $_SESSION['is_staff'],
                    "campus" => $_SESSION['campus']
                ]);
            } elseif ($row['email'] === '') {
                debugLog("No email found", $user);
                echo json_encode(["success" => false, "message" => "User needs an email."]);
            } else {
                // Check agreement status
                if ($row['agreement'] === 'no') {
                    debugLog("User needs to accept agreement", $user);
                    echo json_encode([
                        "success" => true,
                        "needs_agreement" => true,
                        "user_id" => $row['id'],
                        "name" => $user,
                        "is_staff" => $_SESSION['is_staff'],
                        "campus" => $_SESSION['campus']
                    ]);
                } else {
                    echo json_encode([
                        "success" => true,
                        "needs_agreement" => false,
                        "name" => $user,
                        "is_staff" => $_SESSION['is_staff'],
                        "campus" => $_SESSION['campus']
                    ]);
                }
            }
        } else {
            debugLog("Password verification failed for user", $user);
            recordLoginAttempt($conn, $user, $clientIp, false);
            echo json_encode(["success" => false, "message" => "Invalid password."]);
        }
    } else {
        debugLog("No user found", $user);
        recordLoginAttempt($conn, $user, $clientIp, false);
        echo json_encode(["success" => false, "message" => "Username not found."]);
    }

    // Close statement
    $stmt->close();
} else {
    debugLog("Invalid request - missing username or password");
    echo json_encode(["success" => false, "message" => "Invalid request. Username or password not set."]);
}

// Close the connection
$conn->close();
