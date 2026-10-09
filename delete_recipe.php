<?php
require 'includes/functions.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    showError('Use the Delete recipe button to remove a recipe.', 405);
}
checkToken();
$recipeModel = new Recipe($pdo);
if (!$recipeModel->delete(id($_POST['id'] ?? 0), (int) $_SESSION['user_id'])) {
    showError('You can only delete your own recipes.', 403);
}
flash('Recipe deleted.');
redirect('index.php?view=mine');
