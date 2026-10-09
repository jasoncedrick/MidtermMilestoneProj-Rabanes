<?php
// Recipes, ingredient rows and favorites belong together in this small app.
class Recipe
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function categories()
    {
        return $this->db->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    }

    public function all($userId, $search, $category, $minutes, $view)
    {
        $sql = 'SELECT r.*, u.name AS author, c.name AS category,
                EXISTS(SELECT 1 FROM favorites f
                       WHERE f.recipe_id = r.id AND f.user_id = ?) AS saved
                FROM recipes r
                JOIN users u ON r.user_id = u.id
                JOIN categories c ON r.category_id = c.id
                WHERE 1 = 1';
        $values = [$userId];

        if ($search !== '') {
            $sql .= " AND (r.title LIKE ? ESCAPE '!' OR r.description LIKE ? ESCAPE '!')";
            $keyword = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
            $values[] = $keyword;
            $values[] = $keyword;
        }
        if ($category > 0) {
            $sql .= ' AND r.category_id = ?';
            $values[] = $category;
        }
        if ($minutes > 0) {
            $sql .= ' AND r.cook_minutes <= ?';
            $values[] = $minutes;
        }
        if ($view === 'mine') {
            $sql .= ' AND r.user_id = ?';
            $values[] = $userId;
        }
        if ($view === 'favorites') {
            $sql .= ' AND EXISTS(SELECT 1 FROM favorites f
                                WHERE f.recipe_id = r.id AND f.user_id = ?)';
            $values[] = $userId;
        }

        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    public function find($id, $userId)
    {
        $statement = $this->db->prepare(
            'SELECT r.*, u.name AS author, c.name AS category,
             EXISTS(SELECT 1 FROM favorites f
                    WHERE f.recipe_id = r.id AND f.user_id = ?) AS saved
             FROM recipes r
             JOIN users u ON r.user_id = u.id
             JOIN categories c ON r.category_id = c.id
             WHERE r.id = ?'
        );
        $statement->execute([$userId, $id]);
        return $statement->fetch();
    }

    public function ingredients($recipeId)
    {
        $statement = $this->db->prepare(
            'SELECT item FROM ingredients WHERE recipe_id = ? ORDER BY position'
        );
        $statement->execute([$recipeId]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function save($data, $userId, $id = 0)
    {
        // Check ownership again here, even though the page already checks it.
        if ($id > 0) {
            $recipe = $this->find($id, $userId);
            if (!$recipe || (int) $recipe['user_id'] !== $userId) {
                throw new RuntimeException('You can only edit your own recipes.');
            }
        }

        // Save the recipe and its ingredients together, or undo both on failure.
        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                $statement = $this->db->prepare(
                    'UPDATE recipes SET title = ?, description = ?, category_id = ?,
                     cook_minutes = ?, steps = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ? AND user_id = ?'
                );
                $statement->execute([
                    $data['title'], $data['description'], $data['category_id'],
                    $data['cook_minutes'], $data['steps'], $id, $userId
                ]);
                $statement = $this->db->prepare('DELETE FROM ingredients WHERE recipe_id = ?');
                $statement->execute([$id]);
            } else {
                $statement = $this->db->prepare(
                    'INSERT INTO recipes (title, description, category_id, cook_minutes, steps, user_id)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([
                    $data['title'], $data['description'], $data['category_id'],
                    $data['cook_minutes'], $data['steps'], $userId
                ]);
                $id = (int) $this->db->lastInsertId();
            }

            $statement = $this->db->prepare(
                'INSERT INTO ingredients (recipe_id, item, position) VALUES (?, ?, ?)'
            );
            foreach ($data['ingredients'] as $index => $item) {
                $statement->execute([$id, $item, $index + 1]);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function delete($id, $userId)
    {
        $statement = $this->db->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
        $statement->execute([$id, $userId]);
        return $statement->rowCount() === 1;
    }

    public function setFavorite($recipeId, $userId, $saved)
    {
        if ($saved) {
            // The composite primary key prevents duplicate favorites.
            $statement = $this->db->prepare(
                'INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE recipe_id = VALUES(recipe_id)'
            );
        } else {
            $statement = $this->db->prepare(
                'DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?'
            );
        }
        $statement->execute([$userId, $recipeId]);
    }
}
