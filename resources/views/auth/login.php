<section class="form-card auth-card">
    <h1>Sign in</h1>
    <?php if ($errors !== []): ?><div class="error-box" role="alert">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div><?php endif; ?>
    <form method="post" action="/login">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button" type="submit">Sign in</button>
    </form>
    <p class="muted">No account? <a href="/register">Create one</a>.</p>
</section>
