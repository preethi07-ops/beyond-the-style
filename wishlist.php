<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product-card.php';

$user = require_login();
$pageTitle = 'My Wishlist';
$products = fetch_all(PRODUCT_SELECT . ' JOIN wishlist w ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.id DESC', [$user['id']]);
include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <h2>My Wishlist</h2>
    <?php if ($products): ?>
        <div class="product-grid">
            <?php foreach ($products as $p) { render_product_card($p); } ?>
        </div>
    <?php else: ?>
        <div class="empty">
            <p>Your wishlist is empty. Tap the heart on any product to save it here.</p>
            <a class="btn" href="<?= e(url('products.php')) ?>">Browse products</a>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
