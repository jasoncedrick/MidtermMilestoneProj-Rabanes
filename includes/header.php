<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">

    <title><?= e($pageTitle ?? 'Recipes') ?> | Barangay Recipes</title>

    <link rel="stylesheet" href="assets/style.css?v=2">
    <script src="assets/script.js" defer></script>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to content</a>

<header class="site-header">
    <div class="header-content">
        <a class="brand" href="index.php">
            <span class="brand-badge" aria-hidden="true">B.</span>
            <span>Barangay Recipes</span>
        </a>

        <nav aria-label="Main navigation">
            <?php if (!empty($_SESSION['user_id'])): ?>

                <a href="index.php"
                   <?= ($activeNav ?? '') === 'all' ? 'aria-current="page"' : '' ?>>
                    Recipes
                </a>

                <a href="index.php?view=favorites"
                   <?= ($activeNav ?? '') === 'favorites' ? 'aria-current="page"' : '' ?>>
                    Favorites
                </a>

                <a href="index.php?view=mine"
                   <?= ($activeNav ?? '') === 'mine' ? 'aria-current="page"' : '' ?>>
                    My recipes
                </a>

                <a href="recipe_form.php"
                   <?= ($activeNav ?? '') === 'share' ? 'aria-current="page"' : '' ?>>
                    Share recipe
                </a>

                <form action="logout.php" method="post" class="nav-logout">
                    <?= csrfField() ?>
                    <button type="submit">Log out</button>
                </form>

            <?php else: ?>

                <a href="login.php"
                   <?= basename($_SERVER['SCRIPT_NAME']) === 'login.php'
                       ? 'aria-current="page"' : '' ?>>
                    Log in
                </a>

                <a href="register.php"
                   <?= basename($_SERVER['SCRIPT_NAME']) === 'register.php'
                       ? 'aria-current="page"' : '' ?>>
                    Register
                </a>

            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main-content" class="container">

    <?php if (isset($_SESSION['message'])): ?>
        <p class="alert success" role="status">
            <?= e($_SESSION['message']) ?>
        </p>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    