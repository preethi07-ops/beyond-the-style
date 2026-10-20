<?php
require_once __DIR__ . '/../includes/admin-header.php';

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'category';
}

if (is_post()) {
    csrf_check();
    $action = post('action');
    $id = (int) post('id');
    $name = post('name');
    if ($action === 'delete') {
        if ((int) fetch_value('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]) > 0) {
            flash('error', 'This category still has products. Move or delete them first.');
        } else {
            query('DELETE FROM categories WHERE id = ?', [$id]);
            flash('success', 'Category deleted.');
        }
    } elseif ($action === 'add' || $action === 'rename') {
        if ($name === '' || mb_strlen($name) > 80) {
            flash('error', 'Category name is required (max 80 characters).');
        } else {
            $slug = slugify($name);
            $dupe = fetch_value('SELECT COUNT(*) FROM categories WHERE (LOWER(name) = LOWER(?) OR slug = ?) AND id <> ?', [$name, $slug, $action === 'rename' ? $id : 0]);
            if ($dupe) {
                flash('error', 'A category with that name already exists.');
            } elseif ($action === 'add') {
                query('INSERT INTO categories (name, slug) VALUES (?, ?)', [$name, $slug]);
                flash('success', 'Category added.');
            } else {
                query('UPDATE categories SET name = ?, slug = ? WHERE id = ?', [$name, $slug, $id]);
                flash('success', 'Category renamed.');
            }
        }
    }
    redirect('admin/categories.php');
}

$categories = fetch_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.name');
$pageTitle = 'Categories';
?>
<h1>Categories</h1>
<form method="post" class="filter-bar">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" maxlength="80" placeholder="New category name" required>
    <button class="btn" type="submit">Add category</button>
</form>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Products</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td data-label="Name">
                    <form method="post" class="stock-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <input type="text" name="name" maxlength="80" value="<?= e($c['name']) ?>" aria-label="Category name" required>
                        <button class="btn btn-sm btn-outline" type="submit">Rename</button>
                    </form>
                </td>
                <td data-label="Products"><?= (int) $c['product_count'] ?></td>
                <td class="row-actions">
                    <form method="post" onsubmit="return confirm('Delete this category?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit" <?= $c['product_count'] ? 'disabled title="Has products"' : '' ?>>Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
