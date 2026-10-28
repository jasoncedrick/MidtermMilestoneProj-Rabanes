<?php
/*
 * REGISTRATION PAGE
 * GET displays a blank form. POST checks name, email, password and confirmation.
 * User::register() hashes the password; loginMember() signs in the new account.
 * The database UNIQUE email key backs up the duplicate-email check.
 * Study navigation: see README.md in the repository root.
 */
require 'includes/functions.php';
if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$errors = [];
$name = '';
$email = '';
// Server checks still run if someone edits the HTML or submits a request directly.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();
    $name = text($_POST, 'name');
    $email = strtolower(text($_POST, 'email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm']) && is_string($_POST['confirm']) ? $_POST['confirm'] : '';

// Validate the name and email, then the password character count, byte limit and confirmation.
    if (!validLength($name, 2, 60)) {
        $errors[] = 'Name must contain 2–60 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address, up to 190 characters.';
    }
    if (mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        $errors[] = 'Password must contain at least 8 characters and no more than 72 bytes.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Your passwords do not match.';
    }

// Precheck the email for a friendly error. The UNIQUE database key also prevents races.
    $user = new User($pdo);
    if (!$errors && $user->findByEmail($email)) {
        $errors[] = 'That email is already registered.';
    }
// Only valid submissions reach the model; registration is followed by automatic login.
    if (!$errors) {
        try {
            $newId = $user->register($name, $email, $password);
            loginMember(['id' => $newId, 'name' => $name]);
            flash('Your account was created. Welcome!');
            redirect('index.php');
        } catch (PDOException $error) {
            // The UNIQUE email key also protects against simultaneous registrations.
            if ((string) $error->getCode() === '23000') {
                $errors[] = 'That email is already registered.';
            } else {
                throw $error;
            }
        }
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>
<section class="panel auth-panel" aria-labelledby="register-title">
    <h1 id="register-title">Create an account</h1>
    <p>Join the barangay cookbook.</p>
    <?php showErrors($errors); ?>

    <!-- Browser rules and script.js give immediate feedback; register.php performs the authoritative checks. -->
    <form method="post" class="form-stack" id="register-form">
        <?= csrfField() ?>
        <label for="name">Your name</label>
        <input id="name" name="name" required minlength="2" maxlength="60"
               autocomplete="name" value="<?= e($name) ?>">

        <label for="email">Email address</label>
        <input id="email" name="email" type="email" required maxlength="190"
               autocomplete="email" value="<?= e($email) ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="8" maxlength="72"
               autocomplete="new-password" aria-describedby="password-help">
        <small id="password-help">At least 8 characters; no more than 72 bytes.</small>

        <label for="confirm">Confirm password</label>
        <input id="confirm" name="confirm" type="password" required minlength="8" maxlength="72"
               autocomplete="new-password">

        <button type="submit">Register</button>
    </form>
    <p class="form-note">Already registered? <a href="login.php">Log in</a>.</p>
</section>
<?php require 'includes/footer.php'; ?>
