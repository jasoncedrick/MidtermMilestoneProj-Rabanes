<?php
class Comment
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

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

    public function find($id)
    {
        $statement = $this->db->prepare('SELECT * FROM comments WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch();
    }

    public function add($recipeId, $userId, $body)
    {
        $statement = $this->db->prepare(
            'INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)'
        );
        $statement->execute([$recipeId, $userId, $body]);
    }

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

    public function delete($id, $recipeId, $userId)
    {
        $statement = $this->db->prepare(
            'DELETE FROM comments WHERE id = ? AND recipe_id = ? AND user_id = ?'
        );
        $statement->execute([$id, $recipeId, $userId]);
        return $statement->rowCount() === 1;
    }
}
