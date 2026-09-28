<?php
require_once(__DIR__ . '/../security.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
include('../db_connection.php');
try {

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Retrieve form data
    $action = $_GET['action'];
    
    if($action === "submit"){
        $binNumber = $_POST['bin_num'];
        $binDate = $_POST['bin_start'];
        $campus = $_POST['campus'];


        if($binDate){
            $sql = "UPDATE ewaste_bin SET selected = 0 WHERE campus = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $campus);
            // Execute the statement
            $stmt->execute();

            // Prepare SQL statement to insert data into MySQL table
            $sqlUpdate = "UPDATE ewaste_bin SET bin_date = ?, selected = 1 WHERE bin_id = ? AND campus = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);

            // Bind parameters
            $stmtUpdate->bindParam(1, $binDate);
            $stmtUpdate->bindParam(2, $binNumber);
            $stmtUpdate->bindParam(3, $campus);
            // Execute the statement
            $stmtUpdate->execute();

        }
    }
    if($action === "check"){
        $binCampus = $_POST['bin_campus'];
        $sql = "SELECT bin_date, bin_id FROM ewaste_bin WHERE selected = 1 AND campus = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $binCampus);
        // Execute the statement
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $endDate = $data["bin_date"];
        $bin = $data["bin_id"];


        echo json_encode([
            "success" => 1,
            "errormessage" => $endDate,
            "bin_num" => $bin
        ]);
    }
    if($action === "select"){
        $binCampus = $_POST['bin_campus'];
        $sql = ("SELECT bin_id FROM ewaste_bin WHERE campus = ?");
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $binCampus);
        // Execute the statement
        $stmt->execute();

        // Fetch the results
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Iterate over the results and call getCount immediately for each model
        foreach ($data as $row) {
            echo '
                <option value="'. htmlspecialchars($row['bin_id'], ENT_QUOTES, 'UTF-8') . '"> Bin ' . htmlspecialchars($row['bin_id'], ENT_QUOTES, 'UTF-8') . '
                </option>
            ';
        }
    }
    if($action === "getDate"){
        $bin = $_POST['bin_id'];
        $sql = ("SELECT bin_date FROM ewaste_bin WHERE bin_id = ?");
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $bin);
        // Execute the statement
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $date = $data["bin_date"];


        echo json_encode([
            "success" => 1,
            "errormessage" => $date,
        ]);
    }
   
} catch(PDOException $e) {
    echo("DB error: " . $e->getMessage());
}
?>