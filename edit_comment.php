<?php
/*
 * EDIT COMMENT PAGE
 * Look up the comment from the URL id, then reject missing or non-owned records.
 * POST checks the token and text length before Comment::update() saves the body
 * and update timestamp. Success redirects to the recipe comments section.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
requireLogin();

$commentModel = new Comment($pdo);
$commentId = id($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$comment = $commentModel->find($commentId);
if (!$comment) {
    showError('Comment not found.', 404);
}
// Authorization: being logged in does not grant permission to edit somebody else's comment.
if ((int) $comment['user_id'] !== $userId) {
    showError('You can only edit your own comments.', 403);
}

$body = $comment['body'];
$errors = [];
// Validate the submitted body; the model repeats ownership checks and stamps updated_at.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();
    $body = text($_POST, 'body');
    if (!validLength($body, 2, 1000)) {
        $errors[] = 'Comment must contain 2–1,000 characters.';
    } elseif (!$commentModel->update($commentId, $userId, $body)) {
        showError('You can only edit your own comments.', 403);
    } else {
        flash('Comment updated.');
        redirect('recipe.php?id=' . (int) $comment['recipe_id'] . '#comments');
    }
}

$pageTitle = 'Edit comment';
require 'includes/header.php';
?>
<section class="panel form-panel" aria-labelledby="edit-title">
    <h1 id="edit-title">Edit comment</h1>
    <?php showErrors($errors); ?>
    <!-- Keep submitted text in the form when invalid; successful updates redirect back to #comments. -->
    <form method="post" class="form-stack">
        <?= csrfField() ?>
        <label for="body">Your comment</label>
        <textarea id="body" name="body" required minlength="2" maxlength="1000" rows="4"><?= e($body) ?></textarea>
        <div class="actions">
            <button type="submit">Save changes</button>
            <a href="recipe.php?id=<?= (int) $comment['recipe_id'] ?>#comments">Cancel</a>
        </div>
    </form>
</section>
<?php require 'includes/footer.php'; ?>
