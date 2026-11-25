<?php
$adminUser = $_SESSION['user'] ?? null;
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
    <link rel="stylesheet" href="/assets/admin.css">
    <script defer src="/assets/app.js"></script>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="/admin"><span class="admin-brand-mark">VM</span><span> Shop<small>Administration</small></span></a>
        <nav class="admin-nav" aria-label="Admin navigation">
            <a href="/admin" class="<?= $path === '/admin' ? 'active' : '' ?>" <?= $path === '/admin' ? 'aria-current="page"' : '' ?>>Dashboard</a>
            <a href="/admin/products/create" class="<?= str_starts_with($path, '/admin/products') ? 'active' : '' ?>" <?= str_starts_with($path, '/admin/products') ? 'aria-current="page"' : '' ?>>Add product</a>
            <a href="/admin/transactions" class="<?= $path === '/admin/transactions' ? 'active' : '' ?>" <?= $path === '/admin/transactions' ? 'aria-current="page"' : '' ?>>Sales</a>
        </nav>
        <div class="admin-sidebar-bottom">
            <a href="/products">View storefront <span aria-hidden="true">↗</span></a>
            <div class="admin-account">
                <span class="admin-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($adminUser['name'] ?? 'A'), 0, 1))) ?></span>
                <span class="admin-account-name"><?= e($adminUser['name'] ?? 'Admin') ?><small>Administrator</small></span>
            </div>
            <form action="/logout" method="post">
                <input type="hidden" name="_csrf" value="<?= e(\App\Security\Csrf::token()) ?>">
                <button type="submit" class="admin-signout">Sign out</button>
            </form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar"><span>Vending management</span><span class="admin-topbar-user"><?= e($adminUser['name'] ?? 'Admin') ?></span></header>
        <main class="admin-content">
            <?php if ($flashMessage): ?><p class="flash" role="status"><?= e($flashMessage) ?></p><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
