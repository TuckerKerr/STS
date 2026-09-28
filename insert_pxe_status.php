<?php
include('db_connection.php');

$pcname = $_POST['pcname'];
$status = $_POST['status'];

if ($status == "In Progress") {

    $stmt = $conn->prepare(
        "INSERT INTO PXE_Image_Status
        (PCNAME, STATUS, START_TIME)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            STATUS = VALUES(STATUS),
            START_TIME = NOW(),
            END_TIME = NULL"
    );

    $stmt->bind_param("ss", $pcname, $status);

} elseif ($status == "Complete") {

    $stmt = $conn->prepare(
        "UPDATE PXE_Image_Status
        SET STATUS = ?,
            END_TIME = NOW()
        WHERE PCNAME = ?"
    );

    $stmt->bind_param("ss", $status, $pcname);
}

$stmt->execute();

echo "OK";

$stmt->close();
$conn->close();
?>
