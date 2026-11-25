<section class="form-card auth-card">
    <h1>Create account</h1>
    <?php if ($errors !== []): ?><div class="error-box" role="alert"><ul>
        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
    </ul></div><?php endif; ?>
    <form method="post" action="/register">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label for="name">Your name</label>
        <input id="name" name="name" required maxlength="120" autocomplete="name" value="<?= e($values['name'] ?? '') ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" required autocomplete="email" value="<?= e($values['email'] ?? '') ?>">
        <label for="password">Password <span class="muted">(at least 12 characters)</span></label>
        <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
        <button class="button" type="submit">Create account</button>
    </form>
</section>
