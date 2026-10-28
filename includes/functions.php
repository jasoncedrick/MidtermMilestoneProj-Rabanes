<?php
/*
 * SHARED BOOTSTRAP AND HELPERS
 * Runs before page HTML: configure/start the session, send response headers,
 * load the PDO connection and model classes, then define reusable helpers.
 * Controllers use these helpers for validation, login, CSRF checks and safe output.
 * Study navigation: see README.md in the repository root.
 */
// Shared setup. Every page includes this file before displaying HTML.
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);
ini_set('session.use_strict_mode', '1');
session_start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Recipe.php';
require_once __DIR__ . '/../classes/Comment.php';

// Safe text output. Use e() whenever displaying text from a member.
// Escape text at HTML output time to prevent supplied markup from becoming executable HTML.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Read only a string from an input array and trim surrounding whitespace; otherwise return empty text.
function text($data, $key)
{
    return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
}

// Accept a positive integer ID; invalid values become 0, which controllers treat as missing/invalid.
function id($value)
{
    if (!is_scalar($value)) {
        return 0;
    }
    return (int) (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
}

// Send HTTP 303 and stop execution. After POST, the next page is requested with GET.
function redirect($page)
{
    header('Location: ' . $page, true, 303);
    exit;
}

// Authentication guard: redirect visitors who have no logged-in session user ID.
function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Regenerate the session ID to prevent session fixation and issue a fresh CSRF token.
function loginMember($user)
{
    session_regenerate_id(true);
    $_SESSION = [
        'user_id' => (int) $user['id'],
        'name' => $user['name'],
        'csrf' => bin2hex(random_bytes(32))
    ];
}

// A CSRF token protects every form that changes data.
// Create a random session token once; reuse it in forms belonging to this session.
function csrfToken()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Return an escaped hidden input that submits the session token with a form.
function csrfField()
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

// Compare the submitted CSRF token with the session token using hash_equals().
function validToken()
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], text($_POST, 'csrf'));
}

// Reject a missing/wrong token with 403 before a modifying form is processed.
function checkToken()
{
    if (!validToken()) {
        showError('The form has expired. Go back, refresh the page and try again.', 403);
    }
}

// Store a one-time message; header.php displays and removes it on the next page.
function flash($message)
{
    $_SESSION['message'] = $message;
}

// Set an HTTP error status, render a friendly shared-layout page, then stop.
function showError($message, $status)
{
    http_response_code($status);
    $pageTitle = 'Unable to continue';
    require __DIR__ . '/header.php';
    echo '<section class="panel"><h1>Unable to continue</h1><p>' . e($message) . '</p>';
    echo '<a class="button" href="index.php">Back to recipes</a></section>';
    require __DIR__ . '/footer.php';
    exit;
}

// Display the collected validation errors. Escape each message before output.
function showErrors($errors)
{
    if (!$errors) {
        return;
    }
    echo '<section class="alert error" role="alert"><h2>Please check your input</h2><ul>';
    foreach ($errors as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></section>';
}

// Count UTF-8 characters and require an inclusive minimum/maximum length.
function validLength($value, $min, $max)
{
    $length = mb_strlen($value, 'UTF-8');
    return $length >= $min && $length <= $max;
}

// Interpret database times as UTC, convert to Manila time, and show the posting date.
function postedDate($value)
{
    $date = new DateTime($value, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Manila'));
    return $date->format('M j, Y');
}

// Collect all field errors: lengths, real category, minutes, ingredient count/items, steps.
// Return an empty array when valid; callers must check this before saving.
function validateRecipe($data, $categories)
{
    $errors = [];
    if (!validLength($data['title'], 3, 100)) {
        $errors[] = 'Title must contain 3–100 characters.';
    }
    if (!validLength($data['description'], 10, 300)) {
        $errors[] = 'Description must contain 10–300 characters.';
    }
    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if (!in_array($data['category_id'], $categoryIds, true)) {
        $errors[] = 'Choose a valid category.';
    }
    if ($data['cook_minutes'] < 1 || $data['cook_minutes'] > 1440) {
        $errors[] = 'Cooking time must be a whole number from 1 to 1,440 minutes.';
    }
    if (count($data['ingredients']) < 1 || count($data['ingredients']) > 40) {
        $errors[] = 'Add between 1 and 40 ingredients.';
    }
    foreach ($data['ingredients'] as $ingredient) {
        if (!validLength($ingredient, 2, 160)) {
            $errors[] = 'Each ingredient needs 2–160 characters. Remove empty ingredient rows.';
            break;
        }
    }
    if (!validLength($data['steps'], 20, 10000)) {
        $errors[] = 'Cooking steps must contain 20–10,000 characters.';
    }
    return $errors;
}

// Log technical failures without displaying SQL or credentials to visitors.
set_exception_handler(function ($error) {
    error_log($error->getMessage());
    showError('Something went wrong. Please try again.', 500);
});
