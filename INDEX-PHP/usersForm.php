<?php
require_once(__DIR__ . '/../security.php');
require_staff();

header('Content-Type: application/json');

try {
    include('../pdo_connect.php');

    $full_name = $_SESSION['username'];
    $submitType = $_POST['submitType'];

    if ($submitType === "resetPassword") {
        $usersName = $_POST['existingUsers'] ?? '';
        if ($usersName === '') {
            echo json_encode(["success" => false, "message" => "No user selected."]);
            exit();
        }

        $tempPassword = bin2hex(random_bytes(6)); // 12 hex chars
        $hashedTemp = password_hash($tempPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password_hash = ?, must_change_password = 1 WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $hashedTemp);
        $stmt->bindParam(2, $usersName);
        $stmt->execute();

        echo json_encode([
            "success" => true,
            "message" => "Temporary password generated. Share it with the user directly.",
            "tempPassword" => $tempPassword
        ]);
        exit();
    }

    $accountType = $_POST['accountRadio'];
    $campusType = $_POST['campusRadio'];
    $activeType = $_POST['activeRadio'];

    switch($submitType){
        case "newUser":
            $usersName = $_POST['usersName'];
            if($accountType === "Staff"){
                $sql = "INSERT INTO campus_student_workers(campus, student_name, active) VALUES (?,?,?)";
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(1, $accountType);
                $stmt->bindParam(2, $usersName);
                $stmt->bindParam(3, $activeType);

                $stmt->execute();
            }
            else{
                $sql = "INSERT INTO campus_student_workers(campus, student_name, active) VALUES (?,?,?)";
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(1, $campusType);
                $stmt->bindParam(2, $usersName);
                $stmt->bindParam(3, $activeType);

                $stmt->execute();

                $ScheduleSQL = "INSERT INTO Schedule.Employees(campus, name, dept_id, email, pswd_hash, active) VALUES (?,?, 1, '', '', ?)";

                $scheduleSTMT = $conn->prepare($ScheduleSQL);

                // Bind parameters
                $scheduleSTMT->bindParam(1, $campusType);
                $scheduleSTMT->bindParam(2, $usersName);
                $scheduleSTMT->bindParam(3, $activeType);

                $scheduleSTMT->execute();
            }

        case "oldUser":
            $usersName = $_POST['existingUsers'];

            //update the tables but only the tables taht they need
            if($accountType === "Staff"){
                $sql = "UPDATE campus_student_workers SET campus = ?, active = ? WHERE student_name = ?";
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(1, $accountType);
                $stmt->bindParam(2, $activeType);
                $stmt->bindParam(3, $usersName);

                $stmt->execute();

                $ScheduleSQL = "UPDATE Schedule.Employees SET active = 0 WHERE name = ?";

                $scheduleSTMT = $conn->prepare($ScheduleSQL);

                // Bind parameters
                $scheduleSTMT->bindParam(1, $usersName);

                $scheduleSTMT->execute();
            }
            else{
                $sql = "UPDATE campus_student_workers SET campus = ?, active = ? WHERE student_name = ?";
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(1, $campusType);
                $stmt->bindParam(2, $activeType);
                $stmt->bindParam(3, $usersName);

                $stmt->execute();

                $ScheduleSQL = "UPDATE Schedule.Employees SET campus = ?, active = ? WHERE name = ?";

                $scheduleSTMT = $conn->prepare($ScheduleSQL);

                // Bind parameters
                $scheduleSTMT->bindParam(1, $campusType);
                $scheduleSTMT->bindParam(2, $activeType);
                $scheduleSTMT->bindParam(3, $usersName);

                $scheduleSTMT->execute();
            }
    }
    echo json_encode(["success" => true]);
    exit();
} catch(PDOException $e) {
    error_log("usersForm.php DB error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred."]);
}
?>
