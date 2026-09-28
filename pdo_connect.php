<?php
// Shared PDO connection, included by scripts one directory below the site root.
include(__DIR__ . '/db_connection.php');
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
