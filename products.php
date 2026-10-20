<?php
require_once __DIR__ . '/../includes/admin-header.php';

if (is_post()) {
    csrf_check();
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'delete') {
        $p = fetch_one('SELECT image FROM products WHERE id = ?', [$id]);
        if ($p) {
            $extras = fetch_all('SELECT image FROM product_images WHERE product_id = ?', [$id]);
            query('DELETE FROM products WHERE id = ?', [$id]);
            delete_uploaded_image($p['image']);
            foreach ($extras as $x) {
                delete_uploaded_image($x['image']);
            }
            flash('success', 'Product deleted. Past orders keep their line items.');
        }
    } elseif ($action === 'stock') {
        $stock = post('stock');
        if (preg_match('/^\d{1,6}$/', $stock)) {
            query('UPDATE products SET stock = ? WHERE id = ?', [(int) $stock, $id]);
            flash('success', 'Stock updated.');
        } else {
            flash('error', 'Stock must be a whole number (0 or more).');
        }
    }
    redirect('admin/products.php?' . http_build_query(array_filter(['q' => post('q'), 'page' => post('page')])));
}

$q = mb_substr(get('q'), 0, 100);
$page = max(1, (int) get('page', '1'));
$where = '';
$params = [];
if ($q !== '') {
    $where = " WHERE p.name LIKE ? ESCAPE '!' OR c.name LIKE ? ESCAPE '!'";
    $params = ['%' . like_escape($q) . '%', '%' . like_escape($q) . '%'];
}
$total = (int) fetch_value('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id' . $where, $params);
$pages = max(1, (int) ceil($total / 20));
$page = min($page, $pages);
$products = fetch_all(PRODUCT_SELECT . $where . ' ORDER BY p.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20), $params);
$pageTitle = 'Products';
?>
<div class="section-head">
    <h1>Products <small class="muted">(<?= $total ?>)</small></h1>
    <a class="btn" href="<?= e(url('admin/add-product.php')) ?>">+ Add product</a>
</div>
<form method="get" class="filter-bar">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products or categories">
    <button class="btn" type="submit">Search</button>
</form>
<?php if (!$products): ?>
    <p class="empty">No products found.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Product</th><th>Category</th><th>Gender</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td data-label="Product">
                    <div class="cart-product"><img src="<?= e(product_image_url($p['image'])) ?>" alt="">
                        <div><strong><?= e($p['name']) ?></strong><small class="muted">#<?= (int) $p['id'] ?></small></div></div>
                </td>
                <td data-label="Category"><?= e($p['category_name']) ?></td>
                <td data-label="Gender"><?= e(ucfirst($p['gender'])) ?></td>
                <td data-label="Price">
                    <?= has_discount($p) ? money($p['discount_price']) . ' <small class="price-old">' . money($p['price']) . '</small>' : money($p['price']) ?>
                </td>
                <td data-label="Stock">
                    <form method="post" class="stock-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="stock">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="number" name="stock" min="0" value="<?= (int) $p['stock'] ?>" aria-label="Stock for <?= e($p['name']) ?>">
                        <button class="btn btn-sm btn-outline" type="submit">Save</button>
                    </form>
                </td>
                <td class="row-actions">
                    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/edit-product.php?id=' . (int) $p['id'])) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this product permanently?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pagination_links($page, $pages, array_filter(['q' => $q])) ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
