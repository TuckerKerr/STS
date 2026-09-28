<?php
require_once(__DIR__ . '/../security.php');
require_login();

try{
    include('../pdo_connect.php');

    $sql = "INSERT INTO scan_run_log (scan_run_date) VALUES (NOW());";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $downcity_printers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $printers_as_json = json_encode($downcity_printers);

    echo "var studentPrinters = $printers_as_json";
} catch(PDOException $e) {
    error_log("timestamp.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>
