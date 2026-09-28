<?php
// Shared session/auth/CSRF helpers, included by scripts one directory below the site root
// (or directly at the site root) via require_once.

function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function send_json_error($statusCode, $message) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function csrf_token() {
    secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    // Only mutating requests need a CSRF check.
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'HEAD') {
        return;
    }

    $token = '';
    foreach (['HTTP_X_CSRF_TOKEN', 'HTTP_X_CSRF_Token'] as $key) {
        if (isset($_SERVER[$key])) {
            $token = $_SERVER[$key];
            break;
        }
    }
    // Native (non-fetch) form submissions can't set a custom header, so also
    // accept the token as a POST field (injected by global.js's injectCsrfTokens()).
    if ($token === '' && !empty($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }

    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        send_json_error(403, 'Invalid or missing CSRF token.');
    }
}

function require_login() {
    secure_session_start();
    if (empty($_SESSION['username'])) {
        send_json_error(401, 'You must be logged in to do that.');
    }
    verify_csrf();
}

function require_staff() {
    require_login();
    if (empty($_SESSION['is_staff']) || $_SESSION['is_staff'] !== true) {
        send_json_error(403, 'You do not have permission to do that.');
    }
}
