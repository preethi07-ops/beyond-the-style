<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    redirect('cart.php');
}
csrf_check();

$action = post('action');
$back = safe_next(post('back'), 'cart.php');

switch ($action) {
    case 'add':
        [$ok, $msg] = cart_add((int) post('product_id'), post('size'), post('color'), max(1, (int) post('quantity', '1')));
        flash($ok ? 'success' : 'error', $msg);
        break;
    case 'update':
        [$ok, $msg] = cart_set_quantity(post('key'), (int) post('quantity'));
        flash($ok ? 'success' : 'error', $msg);
        $back = 'cart.php';
        break;
    case 'remove':
        [$ok, $msg] = cart_set_quantity(post('key'), 0);
        flash($ok ? 'success' : 'error', $msg);
        $back = 'cart.php';
        break;
    case 'clear':
        cart_clear();
        flash('success', 'Your cart is empty.');
        $back = 'cart.php';
        break;
    default:
        flash('error', 'Unknown cart action.');
        $back = 'cart.php';
}
redirect($back);
