<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    redirect('wishlist.php');
}
csrf_check();
$back = safe_next(post('back'), 'wishlist.php');

$user = current_user();
if (!$user) {
    flash('error', 'Please log in to use your wishlist.');
    redirect('login.php?next=' . rawurlencode($back));
}

$productId = (int) post('product_id');
if (!fetch_value('SELECT COUNT(*) FROM products WHERE id = ?', [$productId])) {
    flash('error', 'That product no longer exists.');
    redirect($back);
}

if (post('action') === 'remove') {
    query('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?', [$user['id'], $productId]);
    flash('success', 'Removed from your wishlist.');
} else {
    if (!fetch_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND product_id = ?', [$user['id'], $productId])) {
        query('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)', [$user['id'], $productId]);
    }
    flash('success', 'Saved to your wishlist.');
}
redirect($back);
