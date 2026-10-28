<?php
/*
 * COMMENT MODEL
 * Contains comment CRUD queries using prepared statements. JOIN loads author
 * names for display. Update and delete enforce ownership; updated_at enables
 * the Edited label. The calling page validates comment length and CSRF tokens.
 * Study navigation: see README.md in the repository root.
 */
class Comment
{
    private $db;

    // Keep the shared database connection for all comment operations.
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // JOIN users to retrieve author names and list this recipe's newest comments first.
    public function all($recipeId)
    {
        $statement = $this->db->prepare(
            'SELECT c.*, u.name AS author FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.recipe_id = ? ORDER BY c.created_at DESC, c.id DESC'
        );
        $statement->execute([$recipeId]);
        return $statement->fetchAll();
    }

    // Fetch one comment by primary key, or false if it no longer exists.
    public function find($id)
    {
        $statement = $this->db->prepare('SELECT * FROM comments WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch();
    }

    // Insert a comment linked to its recipe and session user; timestamps use database defaults.
    public function add($recipeId, $userId, $body)
    {
        $statement = $this->db->prepare(
            'INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)'
        );
        $statement->execute([$recipeId, $userId, $body]);
    }

    // Require ownership, update the text and stamp updated_at, even if text is unchanged.
    public function update($id, $userId, $body)
    {
        $comment = $this->find($id);
        if (!$comment || (int) $comment['user_id'] !== $userId) {
            return false;
        }
        $statement = $this->db->prepare(
            'UPDATE comments SET body = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ? AND user_id = ?'
        );
        $statement->execute([$body, $id, $userId]);
        return true;
    }

    // Require comment ID, recipe ID and owner ID to match; success means exactly one deleted row.
    public function delete($id, $recipeId, $userId)
    {
        $statement = $this->db->prepare(
            'DELETE FROM comments WHERE id = ? AND recipe_id = ? AND user_id = ?'
        );
        $statement->execute([$id, $recipeId, $userId]);
        return $statement->rowCount() === 1;
    }
}
