<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    $full_name = $_SESSION['username'];
    $text = $_POST['text'];
    $action = $_POST['action'];


    if($action === "thumbsUp"){
        $sqlFind = "SELECT id FROM announcements WHERE announcement = ?";
        $stmtFind = $conn->prepare($sqlFind);

        // Bind parameters
        $stmtFind->bindValue(1, $text);
        // Execute the statement
        $stmtFind->execute();

        //get data from the statement to get the ID
        $texts = $stmtFind->fetch(PDO::FETCH_ASSOC);
        $textID = $texts["id"];

        $sqlInfo = "SELECT id FROM likes WHERE announcement_id = ? AND username = ?";
        $stmtInfo= $conn->prepare($sqlInfo);

        // Bind parameters
        $stmtInfo->bindValue(1, $textID);
        $stmtInfo->bindValue(2, $full_name);
        // Execute the statement
        $stmtInfo->execute();
        //get data from the statement to get the ID
        $LoD = $stmtInfo->fetch();

        if($LoD){
            // Prepare SQL statement to insert data into MySQL table
            $sqlUp = "DELETE FROM likes WHERE username LIKE ? AND announcement_id LIKE ?";
            $stmtUp = $conn->prepare($sqlUp);

            // Bind parameters
            $stmtUp->bindValue(1, $full_name);
            $stmtUp->bindValue(2, $textID);
            // Execute the statement
            $stmtUp->execute();
        }
        else{
            // Prepare SQL statement to insert data into MySQL table
            $sqlUp = "INSERT INTO likes (username, announcement_id) VALUES (?,?)";
            $stmtUp = $conn->prepare($sqlUp);

            // Bind parameters
            $stmtUp->bindValue(1, $full_name);
            $stmtUp->bindValue(2, $textID);
            // Execute the statement
            $stmtUp->execute();

            // Prepare SQL statement to insert data into MySQL table
            $sqlDown = "DELETE FROM disliked WHERE username LIKE ? AND announcement_id LIKE ?";
            $stmtDown = $conn->prepare($sqlDown);

            // Bind parameters
            $stmtDown->bindValue(1, $full_name);
            $stmtDown->bindValue(2, $textID);
            // Execute the statement
            $stmtDown->execute();
        }
    }
    if($action === "thumbsDown"){
        $sqlFind = "SELECT id FROM announcements WHERE announcement = ?";
        $stmtFind = $conn->prepare($sqlFind);

        // Bind parameters
        $stmtFind->bindValue(1, $text);
        // Execute the statement
        $stmtFind->execute();

        //get data from the statement to get the ID
        $texts = $stmtFind->fetch(PDO::FETCH_ASSOC);
        $textID = $texts["id"];

        $sqlInfo = "SELECT id FROM disliked WHERE announcement_id = ? AND username = ?";
        $stmtInfo= $conn->prepare($sqlInfo);

        // Bind parameters
        $stmtInfo->bindValue(1, $textID);
        $stmtInfo->bindValue(2, $full_name);
        // Execute the statement
        $stmtInfo->execute();
        //get data from the statement to get the ID
        $LoD = $stmtInfo->fetch();

        if($LoD){
            // Prepare SQL statement to insert data into MySQL table
            $sqlUp = "DELETE FROM disliked WHERE username LIKE ? AND announcement_id LIKE ?";
            $stmtUp = $conn->prepare($sqlUp);

            // Bind parameters
            $stmtUp->bindValue(1, $full_name);
            $stmtUp->bindValue(2, $textID);
            // Execute the statement
            $stmtUp->execute();
        }
        else{
            // Prepare SQL statement to insert data into MySQL table
            $sqlDown = "INSERT INTO disliked (username, announcement_id) VALUES (?,?)";
            $stmtDown = $conn->prepare($sqlDown);

            // Bind parameters
            $stmtDown->bindValue(1, $full_name);
            $stmtDown->bindValue(2, $textID);
            // Execute the statement
            $stmtDown->execute();

            // Prepare SQL statement to insert data into MySQL table
            $sqlUp = "DELETE FROM likes WHERE username LIKE ? AND announcement_id LIKE ?";
            $stmtUp = $conn->prepare($sqlUp);

            // Bind parameters
            $stmtUp->bindValue(1, $full_name);
            $stmtUp->bindValue(2, $textID);
            // Execute the statement
            $stmtUp->execute();
        }
    }
    // Redirect to the thank you page
    header("Location: ../INDEX-HTML/admin.html");
    exit();
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
