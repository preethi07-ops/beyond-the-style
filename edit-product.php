<?php
require_once __DIR__ . '/../includes/admin-header.php';

$editing = get_product((int) get('id', post('id')));
if (!$editing) {
    flash('error', 'Product not found.');
    redirect('admin/products.php');
}
$pageTitle = 'Edit product';
include __DIR__ . '/../includes/admin-product-form.php';
include __DIR__ . '/../includes/admin-footer.php';
