<?php
require_once(__DIR__ . '/../security.php');
require_staff();

try {
    include('../pdo_connect.php');

    // Prepare and execute the stored procedure call
    $stmt = $conn->prepare("SELECT student_name, campus, active FROM campus_student_workers ORDER BY student_name ASC");
    $stmt->execute();

    // Fetch the results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo'<option value="" disabled selected>Select a User</option>';
    // Iterate over the results and call getCount immediately for each model
    foreach ($results as $row) {
        echo '
            <option value="'. htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . ' " data-campus="' . htmlspecialchars($row['campus'], ENT_QUOTES, 'UTF-8') . '" data-active="'. htmlspecialchars($row['active'], ENT_QUOTES, 'UTF-8') . '"> ' . htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . '
            </option>
        ';
    }
} catch (PDOException $e) {
    error_log("getExistingUsers.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}

// Close the PDO connection
$conn = null;
?>
