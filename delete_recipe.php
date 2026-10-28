<?php
/*
 * DELETE RECIPE ENDPOINT
 * Require login, POST, and a valid CSRF token. Recipe::delete() includes the
 * owner ID in its WHERE clause. Foreign keys cascade deletion to ingredients,
 * comments and favorites. Set a one-time message and redirect to My recipes.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    showError('Use the Delete recipe button to remove a recipe.', 405);
}
checkToken();
$recipeModel = new Recipe($pdo);
// The model deletes only WHERE id AND user_id match; zero deleted rows means failure.
if (!$recipeModel->delete(id($_POST['id'] ?? 0), (int) $_SESSION['user_id'])) {
    showError('You can only delete your own recipes.', 403);
}
flash('Recipe deleted.');
redirect('index.php?view=mine');
