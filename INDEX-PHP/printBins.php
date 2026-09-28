<?php
//require_once(__DIR__ . '/../security.php');
//require_login();

include('../db_connection.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
$action = $_GET['action'];
$binCampus = $_GET['campus'];
//STEP THROUGH

try {
    if($action === "check"){
        $sqlBin = mysqli_prepare($conn,"SELECT bin_date FROM ewaste_bin WHERE campus = ? ORDER BY bin_date ASC LIMIT 1");
        mysqli_stmt_bind_param($sqlBin, "s", $binCampus);
        mysqli_stmt_execute($sqlBin);
        mysqli_stmt_bind_result($sqlBin, $bin_date);
        mysqli_stmt_fetch($sqlBin);
        mysqli_stmt_close($sqlBin);

        $sql = mysqli_prepare($conn,"SELECT asset_tag, device_type, model_number, Serial_Number_Service_Tag, bin_num, Campus FROM ewaste WHERE Completion_time > ?");
        mysqli_stmt_bind_param($sql, "s", $bin_date);
        mysqli_stmt_execute($sql);
        mysqli_stmt_bind_result($sql, $asset_tag, $deviceType, $model, $serviceTag, $bin, $campus);
        $ewaste = [];
        while(mysqli_stmt_fetch($sql)){
            $ewaste[] = [
                "asset" => $asset_tag,
                "device" => $deviceType,
                "model" => $model, 
                "tag" => $serviceTag,
                "bin" => $bin,
                "campus" => $campus
            ];
        }
        mysqli_stmt_close($sql);

        //DELETE THE DATE FROM THE BIN THAT WAS JUST PRINTED AS A WAY OF A RESET
        $sqlClear = mysqli_prepare($conn,"UPDATE ewaste_bin SET selected = 0, bin_date = '9' WHERE campus = ?");
        mysqli_stmt_bind_param($sqlClear, "s", $campus);
        mysqli_stmt_execute($sqlClear);

        echo json_encode($ewaste);
    }
} catch (mysqli_sql_exception $e) {
    error_log("DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>