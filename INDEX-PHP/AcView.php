<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    // Retrieve form data
    $viewId = isset($_GET['view']) ? $_GET['view'] : '';

    // Prepare SQL statement to insert data into MySQL table
    $sql = "SELECT * FROM announcementView WHERE visible = 1 ORDER BY time_created DESC LIMIT 5 ";
    $stmt = $conn->prepare($sql);
    
    // Execute the statement
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Redirect to the thank you page
    if (count($data) > 0) {
        $columns = array_keys($data[0]);
    }
    else{
        $columns = 'No Announcemnets';
        $data = 'No Data';
    }

    echo json_encode([
        'success' => true,
        'columns' => $columns,
        'data' => $data
    ]);
    exit();
} catch(PDOException $e) {
    error_log("AcView.php DB error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred."]);
}
?>
