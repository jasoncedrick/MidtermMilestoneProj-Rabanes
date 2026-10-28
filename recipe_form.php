<?php
/*
 * CREATE / EDIT RECIPE PAGE
 * No id means create; an existing id means edit after checking ownership.
 * POST values are normalized, validated in PHP, and saved only if errors is empty.
 * The same form displays defaults, existing values, or rejected submitted values.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();

$recipeModel = new Recipe($pdo);
$categories = $recipeModel->categories();
$recipeId = id($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$data = [
    'title' => '', 'description' => '', 'category_id' => 0,
    'cook_minutes' => 30, 'ingredients' => [''], 'steps' => ''
];
$errors = [];

// Edit mode: load the recipe and verify the current user owns it before showing the form.
if ($recipeId > 0) {
    $data = $recipeModel->find($recipeId, $userId);
    if (!$data) {
        showError('Recipe not found.', 404);
    }
    if ((int) $data['user_id'] !== $userId) {
        showError('You can only edit your own recipes.', 403);
    }
    $data['ingredients'] = $recipeModel->ingredients($recipeId);
}

// POST is a save attempt. Check CSRF before reading and validating submitted fields.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();
// ingredients[] arrives as an array. Keep empty rows so validation can report them.
    $ingredients = [];
    if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
        foreach ($_POST['ingredients'] as $ingredient) {
            $ingredients[] = is_string($ingredient) ? trim($ingredient) : '';
        }
    }
    $data = [
        'title' => text($_POST, 'title'),
        'description' => text($_POST, 'description'),
        'category_id' => id($_POST['category_id'] ?? 0),
        'cook_minutes' => id($_POST['cook_minutes'] ?? 0),
        'ingredients' => $ingredients,
        'steps' => text($_POST, 'steps')
    ];
// Server validation is authoritative even if browser HTML/JavaScript checks are bypassed.
    $errors = validateRecipe($data, $categories);
// Only an empty error list permits saving. Redirect prevents refresh from repeating the POST.
    if (!$errors) {
        $savedId = $recipeModel->save($data, $userId, $recipeId);
        flash($recipeId ? 'Recipe updated.' : 'Recipe shared successfully.');
        redirect('recipe.php?id=' . $savedId);
    }
}

$pageTitle = $recipeId ? 'Edit recipe' : 'Share a recipe';
$activeNav = 'share';
require 'includes/header.php';
?>
<section class="panel form-panel" aria-labelledby="form-title">
    <h1 id="form-title"><?= e($pageTitle) ?></h1>
    <?php showErrors($errors); ?>

    <!-- HTML required/minlength/maxlength provide browser feedback. PHP repeats validation on POST. -->
    <form method="post" class="form-stack">
        <?= csrfField() ?>
        <!-- fieldset and legend: fields grouped by purpose -->
        <fieldset>
            <legend>Recipe details</legend>
            <label for="title">Title</label>
            <input id="title" name="title" required minlength="3" maxlength="100" value="<?= e($data['title']) ?>">

            <label for="description">Short description</label>
            <textarea id="description" name="description" required minlength="10" maxlength="300" rows="3"><?= e($data['description']) ?></textarea>

            <div class="form-row">
                <div>
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>" <?= (int) $data['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cook_minutes">Cooking time (minutes)</label>
                    <input id="cook_minutes" name="cook_minutes" type="number" required min="1" max="1440" step="1"
                           value="<?= e($data['cook_minutes']) ?>">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Ingredients</legend>
            <p class="hint">Include the amount, for example “2 cups rice.” Remove any empty rows.</p>
            <!-- Each ingredients[] input becomes an array item in PHP; JavaScript adds/removes rows. -->
            <div id="ingredients">
                <?php foreach ($data['ingredients'] ?: [''] as $index => $ingredient): ?>
                    <div class="ingredient-row">
                        <label>Ingredient <?= $index + 1 ?>
                            <input name="ingredients[]" required minlength="2" maxlength="160" value="<?= e($ingredient) ?>">
                        </label>
                        <button type="button" class="remove-ingredient secondary" aria-label="Remove ingredient <?= $index + 1 ?>">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-ingredient" class="secondary">Add ingredient</button>
            <small>Between 1 and 40 ingredients.</small>
        </fieldset>

        <fieldset>
            <legend>Cooking steps</legend>
            <label for="steps">Write each step on a new line</label>
            <textarea id="steps" name="steps" required minlength="20" maxlength="10000" rows="7"><?= e($data['steps']) ?></textarea>
        </fieldset>

        <div class="actions">
            <button type="submit"><?= $recipeId ? 'Save changes' : 'Share recipe' ?></button>
            <a href="<?= $recipeId ? 'recipe.php?id=' . $recipeId : 'index.php' ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require 'includes/footer.php'; ?>
