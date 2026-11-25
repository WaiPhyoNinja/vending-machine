<a class="back-link" href="/admin">&larr; Back to dashboard</a>
<section class="intro"><div><p class="eyebrow">Transactions</p><h1>Recent sales</h1></div></section>
<?php if ($transactions === []): ?>
    <div class="empty-state"><h2>No sales just yet.</h2></div>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>When</th><th>Customer</th><th>Product</th><th>Qty</th><th>Each</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $sale): ?>
            <tr>
                <td><?= e($sale['created_at']) ?></td><td><?= e($sale['user_name']) ?></td>
                <td><?= e($sale['product_name']) ?></td><td><?= e($sale['quantity']) ?></td>
                <td>$<?= e(number_format((float) $sale['unit_price'], 3)) ?></td>
                <td>$<?= e(number_format((float) $sale['total_amount'], 3)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
