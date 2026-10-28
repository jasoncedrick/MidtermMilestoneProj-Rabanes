<?php
/*
 * USER MODEL
 * Encapsulates account SQL behind methods. The constructor receives an existing
 * PDO connection. Password hashes are stored, while plaintext passwords are
 * only used transiently during registration and password verification.
 * Study navigation: see README.md in the repository root.
 */
// Handles member accounts. The controller decides when to start a session.
class User
{
    private $db;

    // Dependency injection: keep the PDO connection supplied by the page bootstrap.
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Bind the email value into a SELECT; fetch() returns one row or false.
    public function findByEmail($email)
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $statement->execute([$email]);
        return $statement->fetch();
    }

    // Hash the password, insert the account, and return its generated primary-key ID.
    public function register($name, $email, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $statement->execute([$name, $email, $hash]);
        return (int) $this->db->lastInsertId();
    }

    // Look up the email and verify the supplied password against its hash; return row or false.
    public function login($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
}
