<?php
// Self-service password reset has been removed: there is no verified email domain to prove
// identity, and a username+email match alone is not proof of identity. Password resets are now
// admin-mediated — see usersForm.php's "resetPassword" action — and normal password changes go
// through changePassword.php while authenticated.
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Self-service password reset is no longer available. Please contact IT staff to have your password reset."
]);
