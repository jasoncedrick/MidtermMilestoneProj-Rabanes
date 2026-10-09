<?php
// Handles member accounts. The controller decides when to start a session.
class User
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function findByEmail($email)
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $statement->execute([$email]);
        return $statement->fetch();
    }

    public function register($name, $email, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $statement->execute([$name, $email, $hash]);
        return (int) $this->db->lastInsertId();
    }

    public function login($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
}
