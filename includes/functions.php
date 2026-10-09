<?php
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
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text($data, $key)
{
    return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
}

function id($value)
{
    if (!is_scalar($value)) {
        return 0;
    }
    return (int) (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
}

function redirect($page)
{
    header('Location: ' . $page, true, 303);
    exit;
}

function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

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
function csrfToken()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function validToken()
{
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], text($_POST, 'csrf'));
}

function checkToken()
{
    if (!validToken()) {
        showError('The form has expired. Go back, refresh the page and try again.', 403);
    }
}

function flash($message)
{
    $_SESSION['message'] = $message;
}

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

function validLength($value, $min, $max)
{
    $length = mb_strlen($value, 'UTF-8');
    return $length >= $min && $length <= $max;
}

function postedDate($value)
{
    $date = new DateTime($value, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Manila'));
    return $date->format('M j, Y');
}

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
