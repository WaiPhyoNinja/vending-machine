<a class="back-link" href="<?= e(product_path($product)) ?>">&larr; Back to <?= e($product['name']) ?></a>
<section class="form-card">
    <h1>Purchase <?= e($product['name']) ?></h1>
    <p class="muted">$<?= e(number_format((float) $product['price'], 3)) ?> each · <?= e($product['quantity_available']) ?> available</p>
    <?php if ($errors !== []): ?>
        <div class="error-box" role="alert"><ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <?php if (intval($product['quantity_available']) < 1): ?>
        <p class="empty-state">Out of stock.</p>
    <?php elseif (!isset($_SESSION['user'])): ?>
        <p><a href="/login">Sign in</a> to complete your purchase.</p>
    <?php else: ?>
        <form method="post" action="<?= e(product_path($product, true)) ?>" data-validate>
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label for="quantity">Quantity</label>
            <input id="quantity" name="quantity" type="number" min="1"
                   max="<?= min(100, intval($product['quantity_available'])) ?>" value="1" required>
            <button class="button" type="submit">Confirm purchase</button>
        </form>
    <?php endif; ?>
</section>
