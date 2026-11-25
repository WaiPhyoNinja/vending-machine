<a class="back-link" href="/products">&larr; Back to products</a>
<section class="detail-card">
    <div>
        <h1><?= e($product['name']) ?></h1>
        <p class="price price-large">$<?= e(number_format((float) $product['price'], 3)) ?></p>
        <p><?= e($product['quantity_available']) ?> in stock</p>
        <?php if (intval($product['quantity_available']) > 0): ?><a class="button" href="<?= e(product_path($product, true)) ?>">Buy now</a><?php endif; ?>
        <?php if (($user['role'] ?? '') === 'Admin'): ?>
            <a class="button button-light" href="/admin/products/<?= e($product['id']) ?>/edit">Edit product</a>
        <?php endif; ?>
    </div>
</section>
