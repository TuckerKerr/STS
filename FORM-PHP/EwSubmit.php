<?php
//require_once(__DIR__ . '/../security.php');
//require_login();

include('../db_connection.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Retrieve form data
    $Campus = $_POST['Campus'];
    $deviceType = $_POST['Device-Type'];
    $assetTag = $_POST['Asset-Tag'];
    $modelNumber = $_POST['Model-Number'];
    $Serial_Number_Service_Tag = $_POST['Serial_Number'];
    $start_time = $_POST['form_open_time'];
    $completion_time = date('Y-m-d H:i:s');
    $ssd_serial = $_POST['SSD_Serial'];
    $Name = $_POST['username'];
    $bin_id = $_POST['Bin_Num'];

    // Prepare SQL statement to insert data into MySQL table
    $sql = "INSERT INTO ewaste (Start_time, Completion_time, name, Campus, device_type, asset_tag, model_number, Serial_Number_Service_Tag, ssd_serial, bin_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind parameters (mysqli style — one call, type string first)
    // s = string, i = integer
    $stmt->bind_param(
        "sssssssssi",
        $start_time,
        $completion_time,
        $Name,
        $Campus,
        $deviceType,
        $assetTag,
        $modelNumber,
        $Serial_Number_Service_Tag,
        $ssd_serial,
        $bin_id
    );

    // Execute the statement
    $stmt->execute();

    if (!empty($EwasteWebhookURL)) {
        $payload = [
            "asset_tag" => $assetTag,
            "service_tag" => $Serial_Number_Service_Tag,
            "bin_num" => $bin_id
        ];

        $ch = curl_init($EwasteWebhookURL);

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
    header("Location: ../INDEX-HTML/main.html");
    exit();
} catch (mysqli_sql_exception $e) {
    error_log("EwSubmit.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>