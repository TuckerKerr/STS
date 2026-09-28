<?php

header('Content-Type: application/json');


include('db_connection.php');

$action = $_GET['action'];

if($action === "load"){
    $data = [];

    $sql = "
    SELECT
        PCNAME,
        STATUS,
        START_TIME,
        END_TIME
    FROM PXE_Image_Status
    WHERE flag = 0
    ORDER BY START_TIME DESC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {

        $startTime = $row['START_TIME'];
        $endTime = $row['END_TIME'];

        $durationMinutes = null;

        if (!empty($startTime)) {

            $start = strtotime($startTime);

            if (!empty($endTime)) {

                $end = strtotime($endTime);

                $durationMinutes = floor(
                    ($end - $start) / 60
                );
            }
            else {

                $durationMinutes = floor(
                    (time() - $start) / 60
                );
            }
        }

        $isStuck = false;

        if (
            $row['STATUS'] == 'In Progress'
            &&
            $durationMinutes >= 180
        ) {
            $isStuck = true;
        }

        $data[] = [

            "PCNAME" => $row['PCNAME'],

            "STATUS" => $row['STATUS'],

            "START_TIME" => $startTime,

            "END_TIME" => $endTime,

            "DURATION_MINUTES" => $durationMinutes,

            "IS_STUCK" => $isStuck
        ];
    }

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT
    );
}
if($action === "remove"){
    $pcname = $_POST['pcname'];
    $sql = mysqli_prepare($conn,"
        UPDATE PXE_Image_Status
        SET flag = 1
        WHERE PCNAME = ?
    ");
    mysqli_stmt_bind_param($sql, "s", $pcname);
    mysqli_stmt_execute($sql);
    mysqli_stmt_close($sql);
}

$conn->close();

?>