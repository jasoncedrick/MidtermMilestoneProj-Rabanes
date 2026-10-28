<?php
/*
 * LOGOUT ENDPOINT
 * Only a logged-in POST with a valid CSRF token may log out. Clear session data,
 * expire the browser session cookie, destroy the server session, then redirect.
 * This endpoint changes state and does not render its own form.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    showError('Use the Log out button to end your session.', 405);
}
checkToken();
// Remove identity/token data, expire the cookie, then destroy the server-side session.
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $cookie['path'],
    'secure' => $cookie['secure'],
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_destroy();
redirect('login.php');
