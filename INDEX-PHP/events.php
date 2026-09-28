<?php
require_once(__DIR__ . '/../security.php');
require_login();

include('../pdo_connect.php');

set_time_limit(0);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$lastId = 0;

while(true){
    $stmt = $conn->prepare("SELECT id FROM announcementView ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $latestId = $data['id'] ?? 0;

    if($latestId != $lastId){
        echo "event: refresh\n";
        echo "data: New announcement\n\n";
        ob_flush();
        flush();
        $lastId = $latestId;
    }

    sleep(2);
}
?>
