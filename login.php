<?php
require 'includes/functions.php';
if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken();
    $email = strtolower(text($_POST, 'email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $user = new User($pdo);
    $member = false;
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) <= 72 && !str_contains($password, "\0")) {
        $member = $user->login($email, $password);
    }
    if ($member) {
        loginMember($member);
        redirect('index.php');
    }
    $errors[] = 'Email or password is incorrect.';
}

$pageTitle = 'Log in';
require 'includes/header.php';
?>
<section class="panel auth-panel" aria-labelledby="login-title">
    <h1 id="login-title">Log in</h1>
    <p>Sign in to browse and share recipes with your neighbors.</p>
    <?php showErrors($errors); ?>

    <form method="post" class="form-stack">
        <?= csrfField() ?>
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" required maxlength="190"
               autocomplete="email" value="<?= e($email) ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required maxlength="72"
               autocomplete="current-password">

        <button type="submit">Log in</button>
    </form>
    <p class="form-note">No account yet? <a href="register.php">Register here</a>.</p>
</section>
<?php require 'includes/footer.php'; ?>
