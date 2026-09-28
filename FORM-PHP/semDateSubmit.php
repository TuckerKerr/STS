<?php
require_once(__DIR__ . '/../security.php');

// Database connection parameters
include('../../schedule/db_connect.php');
try {

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Retrieve form data
    $action = $_GET['action'];
    
    if($action === "submit" && require_staff()){
        $semesterDate = $_POST['endOfSemester'];

        if($semesterDate){
            // Prepare SQL statement to insert data into MySQL table
            $sql = "UPDATE endOfSemester SET endSemesterDate = ? WHERE id = 1";
            $stmt = $conn->prepare($sql);

            // Bind parameters
            $stmt->bindParam(1, $semesterDate);
            // Execute the statement
            $stmt->execute();

            header("Location: ../INDEX-HTML/admin.html");
            exit();
        }
         else{
            header("Location: ../INDEX-HTML/admin.html");
            exit();
        } 
    }
    if($action === "check"){
        $sql = "SELECT endSemesterDate FROM endOfSemester WHERE id = 1";
        $stmt = $conn->prepare($sql);
        // Execute the statement
        $stmt->execute();

        $dates = $stmt->fetch(PDO::FETCH_ASSOC);
        $endDate = $dates["endSemesterDate"];


        echo json_encode([
            "success" => 1,
            "errormessage" => $endDate
        ]);
    }
    
   
} catch(PDOException $e) {
    error_log("semDateSubmit.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
?>