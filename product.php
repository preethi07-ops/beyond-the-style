<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product-card.php';

$id = (int) get('id');
$product = $id > 0 ? get_product($id) : null;
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="empty"><p>Sorry, that product could not be found.</p><a class="btn" href="' . e(url('products.php')) . '">Back to shop</a></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();

// Submit / update a review.
if (is_post() && post('action') === 'review') {
    csrf_check();
    $user = require_login();
    $rating = (int) post('rating');
    $comment = mb_substr(post('comment'), 0, 1000);
    if ($rating < 1 || $rating > 5) {
        flash('error', 'Please choose a rating from 1 to 5 stars.');
    } else {
        $existing = fetch_value('SELECT id FROM reviews WHERE user_id = ? AND product_id = ?', [$user['id'], $id]);
        if ($existing) {
            query('UPDATE reviews SET rating = ?, comment = ?, created_at = ? WHERE id = ?', [$rating, $comment, date('Y-m-d H:i:s'), $existing]);
            flash('success', 'Your review was updated.');
        } else {
            query('INSERT INTO reviews (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)', [$id, $user['id'], $rating, $comment, date('Y-m-d H:i:s')]);
            flash('success', 'Thanks for your review!');
        }
    }
    redirect('product.php?id=' . $id . '#reviews');
}

$sizes = parse_list($product['sizes']) ?: ['One Size'];
$colors = parse_list($product['colors']) ?: ['Standard'];
$stock = (int) $product['stock'];
$images = [$product['image']];
foreach (fetch_all('SELECT image FROM product_images WHERE product_id = ? ORDER BY id', [$id]) as $row) {
    $images[] = $row['image'];
}
$reviews = fetch_all('SELECT r.*, u.name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC, r.id DESC', [$id]);
$myReview = null;
foreach ($reviews as $r) {
    if ($user && (int) $r['user_id'] === (int) $user['id']) {
        $myReview = $r;
    }
}
$related = fetch_all(PRODUCT_SELECT . ' WHERE p.category_id = ? AND p.id <> ? ORDER BY p.sales_count DESC LIMIT 4', [$product['category_id'], $id]);
$wished = in_array($id, wishlist_ids(), true);
$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>
<section class="section product-detail">
    <p class="breadcrumb">
        <a href="<?= e(url('index.php')) ?>">Home</a> /
        <a href="<?= e(url('products.php')) ?>">Shop</a> /
        <a href="<?= e(url('products.php?category=' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a> /
        <?= e($product['name']) ?>
    </p>
    <div class="detail-grid">
        <div class="gallery">
            <img id="main-image" class="main-image" src="<?= e(product_image_url($images[0])) ?>" alt="<?= e($product['name']) ?>">
            <?php if (count($images) > 1): ?>
                <div class="thumbs">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= e(product_image_url($img)) ?>" alt="" data-full="<?= e(product_image_url($img)) ?>" class="thumb">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <p class="product-cat"><?= e($product['category_name']) ?> &middot; <?= e(ucfirst($product['gender'])) ?></p>
            <h2><?= e($product['name']) ?></h2>
            <div class="product-rating"><?= render_stars($product['avg_rating'] ?? 0, (int) $product['review_count']) ?></div>
            <p class="price price-lg">
                <?php if (has_discount($product)): ?>
                    <span class="price-now"><?= money($product['discount_price']) ?></span>
                    <span class="price-old"><?= money($product['price']) ?></span>
                    <span class="badge badge-sale inline">-<?= discount_percent($product) ?>%</span>
                <?php else: ?>
                    <span class="price-now"><?= money($product['price']) ?></span>
                <?php endif; ?>
            </p>
            <p class="description"><?= nl2br(e($product['description'])) ?></p>
            <p class="stock <?= $stock > 0 ? ($stock <= 5 ? 'low' : 'ok') : 'none' ?>">
                <?= $stock > 5 ? 'In stock' : ($stock > 0 ? 'Only ' . $stock . ' left' : 'Out of stock') ?>
            </p>

            <form method="post" action="<?= e(url('cart_action.php')) ?>" class="buy-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="hidden" name="back" value="product.php?id=<?= $id ?>">
                <div class="field-row">
                    <label>Size
                        <select name="size" required>
                            <?php foreach ($sizes as $s): ?><option value="<?= e($s) ?>"><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Colour
                        <select name="color" required>
                            <?php foreach ($colors as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Quantity
                        <input type="number" name="quantity" value="1" min="1" max="<?= max(1, $stock) ?>" required>
                    </label>
                </div>
                <button class="btn btn-lg" type="submit" <?= $stock > 0 ? '' : 'disabled' ?>><?= $stock > 0 ? 'Add to Cart' : 'Sold out' ?></button>
            </form>

            <form method="post" action="<?= e(url('wishlist_action.php')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="<?= $wished ? 'remove' : 'add' ?>">
                <input type="hidden" name="back" value="product.php?id=<?= $id ?>">
                <button class="btn btn-outline" type="submit"><?= $wished ? '&#9829; Remove from wishlist' : '&#9825; Add to wishlist' ?></button>
            </form>
        </div>
    </div>
</section>

<section class="section" id="reviews">
    <h2>Customer Reviews</h2>
    <div class="reviews-grid">
        <div>
            <?php if (!$reviews): ?>
                <p class="empty">No reviews yet. Be the first to review this product.</p>
            <?php endif; ?>
            <?php foreach ($reviews as $r): ?>
                <article class="review">
                    <div><strong><?= e($r['name']) ?></strong> <?= render_stars($r['rating'], 1) ?></div>
                    <?php if ($r['comment'] !== ''): ?><p><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
                    <small class="muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></small>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="panel">
            <?php if ($user): ?>
                <h3><?= $myReview ? 'Update your review' : 'Write a review' ?></h3>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="review">
                    <label>Rating
                        <select name="rating" required>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= (int) ($myReview['rating'] ?? 5) === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>Comment
                        <textarea name="comment" rows="4" maxlength="1000" placeholder="Share your thoughts"><?= e($myReview['comment'] ?? '') ?></textarea>
                    </label>
                    <button class="btn" type="submit">Submit review</button>
                </form>
            <?php else: ?>
                <p><a href="<?= e(url('login.php?next=' . rawurlencode('product.php?id=' . $id))) ?>">Log in</a> to write a review.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="section">
    <h2>You may also like</h2>
    <div class="product-grid">
        <?php foreach ($related as $p) { render_product_card($p); } ?>
    </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
