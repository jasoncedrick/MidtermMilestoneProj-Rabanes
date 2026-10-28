<?php
/*
 * FAVORITES JSON ENDPOINT
 * Called by fetch() in assets/script.js. Accept only logged-in POST requests
 * with a valid CSRF token and saved=0 or saved=1. Confirm the recipe exists,
 * update the favorites table, and return JSON rather than an HTML page.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
header('Content-Type: application/json; charset=UTF-8');

// JSON responses let JavaScript show errors without reloading the page.
// Central response helper: set status, encode JSON, and stop further execution.
function reply($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Return HTTP errors as JSON so the fetch caller can display useful feedback.
if (empty($_SESSION['user_id'])) {
    reply(['error' => 'Your session expired. Log in again.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    reply(['error' => 'A POST request is required.'], 405);
}
if (!validToken()) {
    reply(['error' => 'Refresh the page and try again.'], 403);
}

// Treat saved as the requested final state, not a toggle; retries do not invert it twice.
$recipeId = id($_POST['recipe_id'] ?? 0);
$saved = text($_POST, 'saved');
if (!in_array($saved, ['0', '1'], true)) {
    reply(['error' => 'Invalid favorite state.'], 422);
}

try {
    $recipeModel = new Recipe($pdo);
    $userId = (int) $_SESSION['user_id'];
    if (!$recipeModel->find($recipeId, $userId)) {
        reply(['error' => 'Recipe no longer exists.'], 404);
    }
    $recipeModel->setFavorite($recipeId, $userId, $saved === '1');
    reply(['saved' => $saved === '1']);
} catch (Throwable $error) {
    error_log($error->getMessage());
    reply(['error' => 'Could not update your favorites. Please try again.'], 500);
}
