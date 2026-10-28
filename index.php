<?php
/*
 * RECIPE LIST PAGE
 * Loads the shared setup, requires login, reads GET filters, and asks Recipe::all()
 * for matching rows. The HTML loops through those rows to create recipe cards.
 * Read next: classes/Recipe.php for SQL; assets/script.js for Save buttons.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();

$recipeModel = new Recipe($pdo);
$categories = $recipeModel->categories();

// GET parameters keep searches bookmarkable. Normalize and limit text before querying.
$search = mb_substr(text($_GET, 'search'), 0, 100);
$category = id($_GET['category'] ?? 0);
$minutes = id($_GET['minutes'] ?? 0);

// Only the three supported cooking-time filters are accepted; other values mean no limit.
if (!in_array($minutes, [15, 30, 60], true)) {
    $minutes = 0;
}

$view = text($_GET, 'view');

if (!in_array($view, ['all', 'mine', 'favorites'], true)) {
    $view = 'all';
}

// Pass all active filters to the model. It combines filters with AND and orders newest first.
$recipes = $recipeModel->all(
    (int) $_SESSION['user_id'],
    $search,
    $category,
    $minutes,
    $view
);

$titles = [
    'all' => 'Latest recipes',
    'mine' => 'My recipes',
    'favorites' => 'My favorites'
];

$pageTitle = $titles[$view];
$activeNav = $view;

require 'includes/header.php';
?>

<section aria-labelledby="recipes-title">

    <header class="page-heading">
        <div>
            <span class="eyebrow">THE COMMUNITY COOKBOOK</span>

            <h1 id="recipes-title"><?= e($pageTitle) ?></h1>

            <p>
                Welcome, <?= e($_SESSION['name']) ?>.
                Find something good to cook.
            </p>
        </div>

        <a class="button" href="recipe_form.php">Share a recipe</a>
    </header>

    <!-- GET search preserves filters in the URL; selected/value attributes repopulate the form. -->
    <form method="get" class="panel filters" role="search">
        <input type="hidden" name="view" value="<?= e($view) ?>">

        <div>
            <label for="search">Search recipes</label>
            <input
                id="search"
                name="search"
                type="search"
                maxlength="100"
                placeholder="Search a title or description"
                value="<?= e($search) ?>"
            >
        </div>

        <div>
            <label for="category">Category</label>

            <select id="category" name="category">
                <option value="">All categories</option>

                <?php foreach ($categories as $item): ?>
                    <option
                        value="<?= (int) $item['id'] ?>"
                        <?= $category === (int) $item['id'] ? 'selected' : '' ?>
                    >
                        <?= e($item['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="minutes">Cooking time</label>

            <select id="minutes" name="minutes">
                <option value="">Any time</option>

                <?php foreach ([15, 30, 60] as $limit): ?>
                    <option
                        value="<?= $limit ?>"
                        <?= $minutes === $limit ? 'selected' : '' ?>
                    >
                        <?= $limit ?> minutes or less
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Search</button>
    </form>

    <p class="result-info">
        <span id="recipe-count"><?= count($recipes) ?></span>

        <span id="recipe-word">
            <?= count($recipes) === 1 ? 'recipe' : 'recipes' ?>
        </span>

        · Newest first

        <a href="index.php?view=<?= e($view) ?>">Clear filters</a>
    </p>

    <div class="recipe-grid" data-view="<?= e($view) ?>">

        <!-- One article is rendered for each result row. e() escapes user-supplied text. -->
        <?php foreach ($recipes as $recipe): ?>
            <article class="panel recipe-card">

                <header>
                    <div class="card-top">
                        <p class="category-tag">
                            <?= e($recipe['category']) ?>
                        </p>

                        <span class="cook-time">
                            <?= (int) $recipe['cook_minutes'] ?> min
                        </span>
                    </div>

                    <h2>
                        <a href="recipe.php?id=<?= (int) $recipe['id'] ?>">
                            <?= e($recipe['title']) ?>
                        </a>
                    </h2>
                </header>

                <p class="recipe-description">
                    <?= e($recipe['description']) ?>
                </p>

                <p class="meta">
                    By <?= e($recipe['author']) ?>
                </p>

                <footer class="card-footer">
                    <p class="meta">
                        <time datetime="<?= e(
                            str_replace(' ', 'T', $recipe['created_at']) . 'Z'
                        ) ?>">
                            <?= e(postedDate($recipe['created_at'])) ?>
                        </time>

                        <!-- A non-null update timestamp shows Edited; the date beside it remains created_at. -->
                        <?php if ($recipe['updated_at']): ?>
                            <span> · Edited</span>
                        <?php endif; ?>
                    </p>

                    <button
                        type="button"
                        class="favorite-button"
                        data-id="<?= (int) $recipe['id'] ?>"
                        data-saved="<?= (int) $recipe['saved'] ?>"
                        aria-pressed="<?= $recipe['saved'] ? 'true' : 'false' ?>"
                        aria-label="<?= e(
                            ($recipe['saved'] ? 'Unsave ' : 'Save ')
                            . $recipe['title']
                        ) ?>"
                        data-title="<?= e($recipe['title']) ?>"
                    >
                        <?= $recipe['saved'] ? 'Saved' : 'Save' ?>
                    </button>
                </footer>

            </article>
        <?php endforeach; ?>

    </div>

    <section
        id="empty-state"
        class="panel empty-state"
        <?= $recipes ? 'hidden' : '' ?>
    >
        <h2>No recipes here yet</h2>

        <p>
            Try another search, browse all recipes, or share your own.
        </p>

        <a href="index.php">Browse recipes</a>
    </section>

</section>

<?php require 'includes/footer.php'; ?>