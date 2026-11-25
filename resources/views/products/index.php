<section class="intro">
    <div>
        <h1>Products</h1>
    </div>
</section>

<div class="toolbar">
    <p><?= e($total) ?> <?= $total === 1 ? 'product' : 'products' ?></p>
</div>

<?php if ($products === []): ?>
    <div class="empty-state"><p>No products available.</p></div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">
                <h2><a href="<?= e(product_path($product)) ?>"><?= e($product['name']) ?></a></h2>
                <p class="price">$<?= e(number_format((float) $product['price'], 3)) ?></p>
                <p class="stock"><?= intval($product['quantity_available']) > 0 ? e($product['quantity_available']) . ' in stock' : 'Out of stock' ?></p>
                <a class="button" href="<?= e(product_path($product)) ?>">View product</a>
                <?php if (($user['role'] ?? '') === 'Admin'): ?>
                    <a class="edit-link" href="/admin/products/<?= e($product['id']) ?>/edit">Manage</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Product pages">
        <?php for ($number = 1; $number <= $pages; $number++): ?>
            <a class="<?= $number === $page ? 'current' : '' ?>"
               href="/products?page=<?= $number ?>&amp;sort=<?= e($sort) ?>&amp;direction=<?= e($direction) ?>"><?= $number ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
