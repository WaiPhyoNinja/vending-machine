<section class="admin-heading">
    <div>
        <h1>Dashboard</h1>
    </div>
    <a class="button" href="/admin/products/create">Add a Product</a>
</section>

<section class="admin-section">
    <div class="admin-section-heading">
        <div><h2>Products</h2><p class="muted"><?= e($total) ?> products</p></div>
        <a href="/products">View shop</a>
    </div>
    <?php if ($products === []): ?>
        <div class="empty-state"><p>No Products added yet.</p><a href="/admin/products/create">Add your first product</a></div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Product</th><th>Price</th><th>In stock</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['name']) ?></td>
                    <td>$<?= e(number_format((float) $product['price'], 3)) ?></td>
                    <td><?= e($product['quantity_available']) ?></td>
                    <td><a href="/admin/products/<?= e($product['id']) ?>/edit">Edit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($total > count($products)): ?><p class="muted">Showing the first <?= count($products) ?> Products. <a href="/products">View all in the shop</a>.</p><?php endif; ?>
    <?php endif; ?>
</section>

<section class="admin-section">
    <div class="admin-section-heading"><h2>Recent sales</h2><a href="/admin/transactions">View all sales</a></div>
    <?php if ($recentSales === []): ?>
        <div class="empty-state"><p>No sales yet.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>When</th><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($recentSales as $sale): ?>
                <tr>
                    <td><?= e($sale['created_at']) ?></td>
                    <td><?= e($sale['user_name']) ?></td>
                    <td><?= e($sale['product_name']) ?></td>
                    <td><?= e($sale['quantity']) ?></td>
                    <td>$<?= e(number_format((float) $sale['total_amount'], 3)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
