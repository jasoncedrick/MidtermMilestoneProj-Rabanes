<?php
/*
 * RECIPE DETAILS AND COMMENTS
 * The URL id selects one recipe. GET displays it; POST adds or deletes a comment.
 * The logged-in user ID comes from the session, not from a form field.
 * Read next: classes/Recipe.php and classes/Comment.php for database operations.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$recipeId = id($_GET['id'] ?? 0);
$recipeModel = new Recipe($pdo);
$commentModel = new Comment($pdo);
// find() returns a recipe plus author, category and whether this user saved it.
$recipe = $recipeModel->find($recipeId, $userId);
if (!$recipe) {
    showError('Recipe not found.', 404);
}

$errors = [];
$body = '';
// Handle changes before rendering HTML so a successful POST can redirect safely.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();
    $action = text($_POST, 'action');
// Use an explicit action field to distinguish add-comment and delete-comment requests.
    if ($action === 'add_comment') {
        $body = text($_POST, 'body');
        if (!validLength($body, 2, 1000)) {
            $errors[] = 'Comment must contain 2–1,000 characters.';
        } else {
            $commentModel->add($recipeId, $userId, $body);
            flash('Comment added.');
            redirect('recipe.php?id=' . $recipeId . '#comments');
        }
    } elseif ($action === 'delete_comment') {
        $commentId = id($_POST['comment_id'] ?? 0);
        if (!$commentModel->delete($commentId, $recipeId, $userId)) {
            showError('You can only delete your own comments.', 403);
        }
        flash('Comment removed.');
        redirect('recipe.php?id=' . $recipeId . '#comments');
    } else {
        showError('Unknown comment action.', 400);
    }
}

// Load ordered ingredients and author-enriched comments after processing the request.
$ingredients = $recipeModel->ingredients($recipeId);
$comments = $commentModel->all($recipeId);
$pageTitle = $recipe['title'];
require 'includes/header.php';
?>
<a class="back-link" href="index.php">Back to recipes</a>

<!-- article: the complete, independently meaningful recipe -->
<article class="panel recipe-detail">
    <header>
        <p class="category-tag"><?= e($recipe['category']) ?></p>
        <h1><?= e($recipe['title']) ?></h1>
        <p><?= e($recipe['description']) ?></p>
        <p class="meta">
            By <?= e($recipe['author']) ?> · <?= (int) $recipe['cook_minutes'] ?> minutes ·
            <time datetime="<?= e(str_replace(' ', 'T', $recipe['created_at']) . 'Z') ?>">
                <?= e(postedDate($recipe['created_at'])) ?>
            </time>
            <!-- Edited is an indicator, not edit history or the displayed last-edit time. -->
            <?php if ($recipe['updated_at']): ?><span> · Edited</span><?php endif; ?>
        </p>
        <div class="actions">
            <button type="button" class="favorite-button" data-id="<?= $recipeId ?>"
                    data-saved="<?= (int) $recipe['saved'] ?>" aria-pressed="<?= $recipe['saved'] ? 'true' : 'false' ?>"
                    aria-label="<?= e(($recipe['saved'] ? 'Unsave ' : 'Save ') . $recipe['title']) ?>"
                    data-title="<?= e($recipe['title']) ?>">
                <?= $recipe['saved'] ? 'Saved' : 'Save recipe' ?>
            </button>
            <!-- Only the owner sees edit/delete controls; server-side checks enforce the same rule. -->
            <?php if ((int) $recipe['user_id'] === $userId): ?>
                <a href="recipe_form.php?id=<?= $recipeId ?>">Edit recipe</a>
                <form action="delete_recipe.php" method="post" data-confirm="Delete this recipe and its comments? This cannot be undone.">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $recipeId ?>">
                    <button type="submit" class="danger">Delete recipe</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="recipe-columns">
        <section aria-labelledby="ingredients-title">
            <h2 id="ingredients-title">Ingredients</h2>
            <ul class="ingredient-list">
                <?php foreach ($ingredients as $ingredient): ?>
                    <li><?= e($ingredient) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section aria-labelledby="steps-title">
            <h2 id="steps-title">Cooking steps</h2>
            <ol class="step-list">
                <?php foreach (preg_split('/\R/u', $recipe['steps']) as $step): ?>
                    <?php if (trim($step) !== ''): ?>
                        <li><?= e($step) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
</article>

<section class="panel comments-section" id="comments" aria-labelledby="comments-title">
    <h2 id="comments-title">Comments (<?= count($comments) ?>)</h2>
    <?php showErrors($errors); ?>
    <form method="post" class="form-stack">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_comment">
        <label for="body">Leave a comment</label>
        <textarea id="body" name="body" required minlength="2" maxlength="1000" rows="3"><?= e($body) ?></textarea>
        <button type="submit">Post comment</button>
    </form>

    <?php if (!$comments): ?>
        <p class="hint">No comments yet.</p>
    <?php endif; ?>
    <!-- Render each comment, its author and optional Edited marker; owners also get edit/delete controls. -->
    <?php foreach ($comments as $comment): ?>
        <article class="comment">
            <header>
                <h3><?= e($comment['author']) ?></h3>
                <p class="meta">
                    <time datetime="<?= e(str_replace(' ', 'T', $comment['created_at']) . 'Z') ?>">
                        <?= e(postedDate($comment['created_at'])) ?>
                    </time>
                    <?php if ($comment['updated_at']): ?><span> · Edited</span><?php endif; ?>
                </p>
            </header>
            <p class="comment-body"><?= e($comment['body']) ?></p>
            <?php if ((int) $comment['user_id'] === $userId): ?>
                <footer class="actions">
                    <a href="edit_comment.php?id=<?= (int) $comment['id'] ?>">Edit comment</a>
                    <form method="post" data-confirm="Delete your comment?">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_comment">
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <button type="submit" class="danger">Delete comment</button>
                    </form>
                </footer>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<?php require 'includes/footer.php'; ?>
