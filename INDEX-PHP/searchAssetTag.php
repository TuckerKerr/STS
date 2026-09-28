<?php
require_once(__DIR__ . '/../security.php');
require_login();

try {
    include('../pdo_connect.php');

    // Retrieve asset tag from GET parameter
    $assetTag = $_GET['asset_tag'];
    $safeAssetTag = htmlspecialchars($assetTag, ENT_QUOTES, 'UTF-8');

    // Prepare SQL statement
    $sql = "SELECT * FROM ewaste WHERE asset_tag = :asset_tag";
    $stmt = $conn->prepare($sql);
    // Bind parameter
    $stmt->bindParam(':asset_tag', $assetTag);
    // Execute query
    $stmt->execute();

    // Check if there are any results
    if ($stmt->rowCount() > 0) {
        // Display table header
        echo "<br>";
        echo "<table>";
        echo "<tr><th>Device Type</th><th>Campus</th><th>Name</th><th>Model Number</th><th>Serial Number/Service Tag</th><th>Completion Time</th></tr>";

        // Fetch data and display in table rows
        // PHP for some studpid reasong is case sensitive so if you decide to touch the
        // variable I so carefully put into this code DONT or atleast spell it correctly thank you
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["device_type"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row["Campus"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row["name"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row["model_number"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row["Serial_Number_Service_Tag"], ENT_QUOTES, 'UTF-8') . "</td>";
            // Format completion time to "month/day/year" format
            $completionTime = date("m/d/Y", strtotime($row["Completion_time"]));
            echo "<td>" . htmlspecialchars($completionTime, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<br>No results found for <strong>Asset Tag: " . $safeAssetTag . "</strong> in <strong>Ewaste</strong><br>";
    }

    // Prepare SQL statement
    $sql2 = "SELECT * FROM equipment_return WHERE JWU_Asset_Tag = :asset_tag";
    $stmt2 = $conn->prepare($sql2);
    // Bind parameter
    $stmt2->bindParam(':asset_tag', $assetTag);
    // Execute query
    $stmt2->execute();

    // Check if there are any results
    if ($stmt2->rowCount() > 0) {
        // Display table header
        echo "<br>";
        echo "<table>";
        echo "<tr><th>Dropper Name</th><th>Receiving Staff Member</th><th>Staff Assigned</th><th>Completion Time</th></tr>";

        // Fetch data and display in table rows
        // PHP for some studpid reasong is case sensitive so if you decide to touch the
        // variable I so carefully put into this code DONT or atleast spell it correctly thank you
        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row2["Dropper_Name"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row2["Name"], ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td>" . htmlspecialchars($row2["Staff_Member_Assigned"], ENT_QUOTES, 'UTF-8') . "</td>";
            // Format completion time to "month/day/year" format
            $completionTime2 = date("m/d/Y", strtotime($row2["Completion_time"]));
            echo "<td>" . htmlspecialchars($completionTime2, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        } else {
            echo "<br>No results found for <strong>Asset Tag: " . $safeAssetTag . "</strong> in <strong>Equipment Dropoff</strong><br>";
        }

        // Prepare SQL statement
        $sql3 = "SELECT * FROM printer_list WHERE jwu_asset = :asset_tag";
        $stmt3 = $conn->prepare($sql3);
        // Bind parameter
        $stmt3->bindParam(':asset_tag', $assetTag);
        // Execute query
        $stmt3->execute();

        // Check if there are any results
        if ($stmt3->rowCount() > 0) {
            // Display table header
            echo "<br>";
            echo "<table>";
            echo "<tr><th>Checked By</th><th>Check Date</th><th>Install Location</th><th>Printer Model</th></tr>";

            // Fetch data and display in table rows
            // PHP for some studpid reasong is case sensitive so if you decide to touch the
            // variable I so carefully put into this code DONT or atleast spell it correctly thank you
            while ($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)) {
                $sql4 = "SELECT * FROM printer_check WHERE IP_Address = :ip_address AND (CAST(`it_front_desk`.`printer_check`.`Check_Date` AS DATE) = CURDATE())";
                $stmt4 = $conn->prepare($sql4);
                $stmt4->bindParam(':ip_address', $row3["IP_Address"]);
                // Execute query
                $stmt4->execute();

                if($stmt4->rowCount() > 0) {
                    while ($row4 = $stmt4->fetch(PDO::FETCH_ASSOC)) {
                        $staffChecked = $row4["Checked_By"];
                        $checkDate = $row4["Check_Date"];
                    }
                } else {
                    $staffChecked = "No Check for Current Date";
                    $checkDate = "N/A";
                }
                echo "<tr>";
                echo "<td>" . htmlspecialchars($staffChecked, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($checkDate, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row3["install_location"], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row3["model"], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<br>";
            } else {
                echo "<br>No results found for <strong>Asset Tag: " . $safeAssetTag . "</strong> in <strong>Printer List</strong><br>";
            }
} catch (PDOException $e) {
    error_log("searchAssetTag.php DB error: " . $e->getMessage());
    echo "An error occurred.";
}
$conn = null; // Close connection
?>
