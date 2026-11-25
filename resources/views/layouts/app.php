<?php
$layoutUser = $_SESSION['user'] ?? null;
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vending</title>
    <link rel="stylesheet" href="/assets/app.css">
    <script defer src="/assets/app.js"></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="/products">Vending</a>
    <nav aria-label="Main navigation">
        <a href="/products">Products</a>
        <?php if (($layoutUser['role'] ?? '') === 'Admin'): ?>
            <a href="/admin">Admin panel</a>
        <?php endif; ?>
        <?php if ($layoutUser): ?>
            <span class="welcome"><?= e($layoutUser['name']) ?></span>
            <form class="inline-form" action="/logout" method="post">
                <input type="hidden" name="_csrf" value="<?= e(\App\Security\Csrf::token()) ?>">
                <button class="link-button" type="submit">Sign out</button>
            </form>
        <?php else: ?>
            <a href="/login">Sign in</a>
            <a href="/register">Create account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
    <?php if ($flashMessage): ?>
        <p class="flash"><?= e($flashMessage) ?></p>
    <?php endif; ?>
    <?= $content ?>
</main>
<footer>Vending System</footer>
</body>
</html>
