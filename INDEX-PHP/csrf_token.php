<?php
require_once(__DIR__ . '/../security.php');
require_login();

header('Content-Type: application/json');
echo json_encode(['token' => csrf_token()]);
