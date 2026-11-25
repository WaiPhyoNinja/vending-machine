<a class="back-link" href="/admin">&larr; Back to dashboard</a>
<section class="form-card">
    <p class="eyebrow">Products</p>
    <h1><?= $editing ? 'Edit product' : 'Add product' ?></h1>
    <?php if ($errors !== []): ?>
        <div class="error-box" role="alert"><strong>Just a couple of things to fix:</strong><ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="<?= $editing ? '/admin/products/' . e($product['id']) : '/admin/products' ?>" data-validate>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label for="name">Product name</label>
        <input id="name" name="name" required maxlength="120" value="<?= e($product['name']) ?>">
        <label for="price">Price (USD)</label>
        <input id="price" name="price" type="number" min="0.001" step="0.001" required value="<?= e($product['price']) ?>">
        <label for="quantity_available">Quantity on hand</label>
        <input id="quantity_available" name="quantity_available" type="number" min="0" step="1" required value="<?= e($product['quantity_available']) ?>">
        <button class="button" type="submit"><?= $editing ? 'Save changes' : 'Add product' ?></button>
    </form>
    <?php if ($editing): ?>
        <form class="delete-form" method="post" action="/admin/products/<?= e($product['id']) ?>/delete" data-confirm="Remove this product? Sales history is kept, so products already sold cannot be removed.">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button class="button button-danger" type="submit">Remove product</button>
        </form>
    <?php endif; ?>
</section>
