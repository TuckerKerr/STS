<?php
require_once(__DIR__ . '/../security.php');
secure_session_start();

// Database connection parameters
include('../db_connection.php');

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("register.php DB connection failed: " . $conn->connect_error);
    die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
}

if(isset($_POST['email']) && isset($_POST['username'])  && !isset($_POST['password'])){
    // Setting/updating an email requires an authenticated session, and only for your own account.
    require_login();

    $email = $_POST['email'];
    $user = $_POST['username'];

    if (empty($_SESSION['username']) || $_SESSION['username'] !== $user) {
        echo json_encode(["success" => false, "message" => "You can only update your own email."]);
        exit();
    }

    if(!str_ends_with($email, '@jwu.edu')){
        echo json_encode(["success" => false, "message" => "Email invalid"]);
        exit();
    }
    else{

    $sqlEmail = "SELECT email FROM users WHERE username = ?;";
    $stmtEmail = $conn->prepare($sqlEmail);

    $stmtEmail->bind_param("s", $user);
    $stmtEmail->execute();
    $results=$stmtEmail->get_result();
    $row = $results->fetch_row();

    if ($row[0] === '' || is_null($row[0])) {
        $sqlAddEmail="UPDATE users SET email = ? WHERE username = ?";
        $stmtAddEmail = $conn->prepare($sqlAddEmail);

        $stmtAddEmail->bind_param("ss", $email, $user);
        $stmtAddEmail->execute();

        echo json_encode(["success" => true, "message" => "Email Successfully Added"]);

        $stmtAddEmail->close();
    }
}
}
else{

// Get the form data
if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $email = $_POST['email'];
    $registrationCode = $_POST['registrationCode'] ?? '';

    if(!str_ends_with($email, '@jwu.edu')){
        echo json_encode(["success" => false, "message" => "Email is Invalid"]);
        exit();
    }
    else{

    // Verify the shared registration code before anything else
    $sqlCode = "SELECT setting_value FROM app_settings WHERE setting_key = 'registration_code_hash'";
    $codeResult = $conn->query($sqlCode);
    $codeRow = $codeResult ? $codeResult->fetch_assoc() : null;

    if (!$codeRow || !password_verify($registrationCode, $codeRow['setting_value'])) {
        echo json_encode(["success" => false, "message" => "Invalid registration code."]);
        exit();
    }

    // Check if the username exists in the campus_student_workers table and if they are active
    $sql = "SELECT student_name FROM campus_student_workers WHERE student_name = ? AND active = 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("register.php prepare failed: " . $conn->error);
        die(json_encode(["success" => false, "message" => "An error occurred. Please try again later."]));
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();  // Store the result

    // If a match is found in the campus_student_workers table and the user is active, proceed
    if ($stmt->num_rows > 0) {
        // Check if the user already exists in the users table
        $sqlCheck = "SELECT username FROM users WHERE username = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $user);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Username already exists."]);
        } else {
            // If not, insert the new user into the users table
            $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
            $sqlInsert = "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("sss", $user, $hashedPassword, $email);

            if ($stmtInsert->execute()) {
                echo json_encode(["success" => true, "message" => "Registration successful!", "name" => $user]);
            } else {
                error_log("register.php insert failed: " . $stmtInsert->error);
                echo json_encode(["success" => false, "message" => "Registration failed."]);
            }

            $stmtInsert->close();
        }

        $stmtCheck->close();
    } else {
        echo json_encode(["success" => false, "message" => "Username not found in campus student workers or inactive."]);
    }

    // Close statements
    $stmt->close();
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request. Username or password not set."]);
}
}

// Close the connection
$conn->close();
?>
