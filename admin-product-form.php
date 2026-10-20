<?php
/**
 * Shared add/edit product logic and form for admin/add-product.php and admin/edit-product.php.
 * Expects $editing (existing product row or null) before inclusion.
 */
$categories = get_categories();
$productId = $editing ? (int) $editing['id'] : 0;
$extraImages = $editing ? fetch_all('SELECT id, image FROM product_images WHERE product_id = ? ORDER BY id', [$productId]) : [];
$errors = [];
$form = $editing ?: [
    'name' => '', 'category_id' => '', 'gender' => 'unisex', 'description' => '', 'price' => '',
    'discount_price' => '', 'sizes' => 'S,M,L,XL', 'colors' => 'Black', 'stock' => '0', 'image' => '',
];

if (is_post()) {
    csrf_check();
    foreach (['name', 'category_id', 'gender', 'description', 'price', 'discount_price', 'sizes', 'colors', 'stock'] as $k) {
        $form[$k] = post($k);
    }
    $categoryIds = array_map('intval', array_column($categories, 'id'));

    if ($form['name'] === '' || mb_strlen($form['name']) > 150) {
        $errors[] = 'Product name is required (max 150 characters).';
    }
    if (!in_array((int) $form['category_id'], $categoryIds, true)) {
        $errors[] = 'Please choose a category.';
    }
    if (!in_array($form['gender'], ['men', 'women', 'unisex'], true)) {
        $errors[] = 'Please choose a gender.';
    }
    if ($form['description'] === '' || mb_strlen($form['description']) > 5000) {
        $errors[] = 'Description is required (max 5000 characters).';
    }
    if (!is_numeric($form['price']) || (float) $form['price'] <= 0 || (float) $form['price'] > 9999999) {
        $errors[] = 'Price must be a number greater than 0.';
    }
    $discount = null;
    if ($form['discount_price'] !== '') {
        if (!is_numeric($form['discount_price']) || (float) $form['discount_price'] <= 0) {
            $errors[] = 'Discount price must be a number greater than 0 (or leave it empty).';
        } elseif (is_numeric($form['price']) && (float) $form['discount_price'] >= (float) $form['price']) {
            $errors[] = 'Discount price must be lower than the regular price.';
        } else {
            $discount = (float) $form['discount_price'];
        }
    }
    if (!preg_match('/^\d{1,6}$/', $form['stock'])) {
        $errors[] = 'Stock must be a whole number (0 or more).';
    }
    $sizes = implode(',', parse_list($form['sizes']));
    $colors = implode(',', parse_list($form['colors']));
    if ($sizes === '' || mb_strlen($sizes) > 100) {
        $errors[] = 'Enter at least one size, separated by commas (max 100 characters).';
    }
    if ($colors === '' || mb_strlen($colors) > 150) {
        $errors[] = 'Enter at least one colour, separated by commas (max 150 characters).';
    }

    [$mainImage, $imgErr] = save_uploaded_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if ($imgErr) {
        $errors[] = $imgErr;
    }
    if (!$editing && !$mainImage && !$imgErr) {
        $errors[] = 'Please upload a product image.';
    }

    // Additional images (multiple).
    $newExtras = [];
    if (!empty($_FILES['extra']['name']) && is_array($_FILES['extra']['name'])) {
        foreach ($_FILES['extra']['name'] as $i => $_) {
            [$path, $err] = save_uploaded_image([
                'error' => $_FILES['extra']['error'][$i], 'size' => $_FILES['extra']['size'][$i],
                'tmp_name' => $_FILES['extra']['tmp_name'][$i],
            ]);
            if ($err) {
                $errors[] = $err;
            } elseif ($path) {
                $newExtras[] = $path;
            }
        }
    }

    if ($errors) {
        // Do not leave orphaned uploads behind when validation fails.
        delete_uploaded_image($mainImage);
        foreach ($newExtras as $p) {
            delete_uploaded_image($p);
        }
    } else {
        $params = [(int) $form['category_id'], $form['name'], $form['gender'], $form['description'], (float) $form['price'],
                   $discount, $sizes, $colors, (int) $form['stock']];
        if ($editing) {
            $imageSql = $mainImage ? ', image = ?' : '';
            query("UPDATE products SET category_id = ?, name = ?, gender = ?, description = ?, price = ?, discount_price = ?, sizes = ?, colors = ?, stock = ?$imageSql WHERE id = ?",
                array_merge($params, $mainImage ? [$mainImage] : [], [$productId]));
            if ($mainImage) {
                delete_uploaded_image($editing['image']);
            }
            foreach ((array) ($_POST['remove_images'] ?? []) as $rid) {
                $row = fetch_one('SELECT image FROM product_images WHERE id = ? AND product_id = ?', [(int) $rid, $productId]);
                if ($row) {
                    query('DELETE FROM product_images WHERE id = ?', [(int) $rid]);
                    delete_uploaded_image($row['image']);
                }
            }
            $savedId = $productId;
            flash('success', 'Product updated.');
        } else {
            query('INSERT INTO products (category_id, name, gender, description, price, discount_price, sizes, colors, stock, image, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_merge($params, [$mainImage, date('Y-m-d H:i:s')]));
            $savedId = (int) db()->lastInsertId();
            flash('success', 'Product added.');
        }
        foreach ($newExtras as $p) {
            query('INSERT INTO product_images (product_id, image) VALUES (?, ?)', [$savedId, $p]);
        }
        redirect('admin/products.php');
    }
}
?>
<h1><?= $editing ? 'Edit product' : 'Add product' ?></h1>
<?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="panel form-grid" novalidate>
    <?= csrf_field() ?>
    <label class="span-2">Product name <input type="text" name="name" maxlength="150" value="<?= e($form['name']) ?>" required></label>
    <label>Category
        <select name="category_id" required>
            <option value="">Choose&hellip;</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $form['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Gender
        <select name="gender">
            <?php foreach (['men' => 'Men', 'women' => 'Women', 'unisex' => 'Unisex'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $form['gender'] === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="span-2">Description <textarea name="description" rows="5" required><?= e($form['description']) ?></textarea></label>
    <label>Price (<?= e(CURRENCY) ?>) <input type="number" name="price" min="0.01" step="0.01" value="<?= e($form['price']) ?>" required></label>
    <label>Discount price (<?= e(CURRENCY) ?>, optional) <input type="number" name="discount_price" min="0.01" step="0.01" value="<?= e($form['discount_price']) ?>"></label>
    <label>Sizes (comma separated) <input type="text" name="sizes" value="<?= e($form['sizes']) ?>" placeholder="S,M,L,XL" required></label>
    <label>Colours (comma separated) <input type="text" name="colors" value="<?= e($form['colors']) ?>" placeholder="Black,Blue" required></label>
    <label>Stock quantity <input type="number" name="stock" min="0" step="1" value="<?= e($form['stock']) ?>" required></label>
    <label>Main image <?= $editing ? '(leave empty to keep the current one)' : '' ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $editing ? '' : 'required' ?>>
    </label>
    <?php if ($editing): ?>
        <div class="span-2"><span class="muted">Current image</span><br>
            <img class="thumb-lg" src="<?= e(product_image_url($editing['image'])) ?>" alt="">
        </div>
    <?php endif; ?>
    <label class="span-2">Additional images (optional, JPG/PNG/WEBP up to 2 MB each)
        <input type="file" name="extra[]" accept="image/jpeg,image/png,image/webp" multiple>
    </label>
    <?php if ($extraImages): ?>
        <div class="span-2">
            <span class="muted">Existing additional images &ndash; tick to remove</span>
            <div class="extra-list">
                <?php foreach ($extraImages as $ei): ?>
                    <label class="extra-item"><img class="thumb-lg" src="<?= e(product_image_url($ei['image'])) ?>" alt="">
                        <input type="checkbox" name="remove_images[]" value="<?= (int) $ei['id'] ?>"> Remove</label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="span-2 actions">
        <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Add product' ?></button>
        <a class="btn btn-outline" href="<?= e(url('admin/products.php')) ?>">Cancel</a>
    </div>
</form>
