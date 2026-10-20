<?php
require_once __DIR__ . '/db.php';

/* ---------- Output / URLs ---------- */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** URL for a file stored as a relative path (handles spaces in names like "l tops.jpeg"). */
function asset_url(string $relativePath): string
{
    $segments = array_map('rawurlencode', explode('/', trim($relativePath)));
    return url(implode('/', $segments));
}

function product_image_url(?string $image): string
{
    $image = trim((string) $image);
    if ($image === '' || !is_file(ROOT_PATH . '/' . $image)) {
        return asset_url('images/logo.jpeg');
    }
    return asset_url($image);
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Only allows redirects to internal pages such as "cart.php?x=1". */
function safe_next(?string $next, string $fallback = 'index.php'): string
{
    $next = (string) $next;
    return preg_match('/^[a-z0-9\-_]+\.php(\?[\w=&%\-.+]*)?$/i', $next) ? $next : $fallback;
}

function money($amount): string
{
    return CURRENCY . number_format((float) $amount, 2);
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired or the request was invalid. Please go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/* ---------- Input helpers ---------- */

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function parse_list(?string $csv): array
{
    $items = array_filter(array_map('trim', explode(',', (string) $csv)), 'strlen');
    return array_values(array_unique($items));
}

function valid_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    return null;
}

/* ---------- Catalogue ---------- */

const PRODUCT_SELECT = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
        COALESCE(NULLIF(p.discount_price, 0), p.price) AS final_price,
        (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) AS avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
    FROM products p
    JOIN categories c ON c.id = p.category_id";

function get_product(int $id): ?array
{
    return fetch_one(PRODUCT_SELECT . ' WHERE p.id = ?', [$id]);
}

function get_categories(): array
{
    return fetch_all('SELECT * FROM categories ORDER BY name');
}

function has_discount(array $p): bool
{
    return !empty($p['discount_price']) && (float) $p['discount_price'] < (float) $p['price'];
}

function discount_percent(array $p): int
{
    return has_discount($p) ? (int) round((1 - $p['discount_price'] / $p['price']) * 100) : 0;
}

function render_stars($rating, int $count = 0): string
{
    $rating = (float) $rating;
    $full = (int) round($rating);
    $html = '<span class="stars" aria-label="Rated ' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $full ? 'on' : 'off') . '">&#9733;</span>';
    }
    $html .= '</span>';
    if ($count > 0) {
        $html .= ' <small class="muted">(' . (int) $count . ')</small>';
    } else {
        $html .= ' <small class="muted">No reviews yet</small>';
    }
    return $html;
}

/** Escapes % and _ so user input is treated literally inside LIKE ... ESCAPE '!'. */
function like_escape(string $s): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
}

/* ---------- Pagination ---------- */

function pagination_links(int $page, int $pages, array $params): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pages">';
    for ($i = 1; $i <= $pages; $i++) {
        $q = http_build_query(array_merge($params, ['page' => $i]));
        $html .= $i === $page
            ? '<span class="current">' . $i . '</span>'
            : '<a href="?' . e($q) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}

/* ---------- Image uploads (admin) ---------- */

/**
 * Validates and stores one uploaded image. Returns [path|null, error|null].
 * The path is relative to the project root, e.g. "assets/uploads/products/ab12.jpg".
 */
function save_uploaded_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The image could not be uploaded (error code ' . (int) $file['error'] . ').'];
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return [null, 'Images must be 2 MB or smaller.'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [null, 'The upload folder could not be created.'];
    }
    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        return [null, 'The image could not be saved. Check folder permissions on assets/uploads/products.'];
    }
    return ['assets/uploads/products/' . $name, null];
}

/** Deletes an image file only if it lives in the uploads folder (never the bundled images). */
function delete_uploaded_image(?string $path): void
{
    if ($path && str_starts_with($path, 'assets/uploads/products/')) {
        $file = ROOT_PATH . '/' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

/* ---------- Cart ----------
 * Guests: stored in the session. Logged-in customers: stored in the `cart` table.
 * A line is identified by "productId|size|color".
 */

function cart_key(int $productId, string $size, string $color): string
{
    return $productId . '|' . $size . '|' . $color;
}

function cart_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** Raw lines: [key => ['product_id','size','color','quantity']] */
function cart_raw(): array
{
    $uid = cart_user_id();
    if ($uid === null) {
        return $_SESSION['cart'] ?? [];
    }
    $lines = [];
    foreach (fetch_all('SELECT product_id, size, color, quantity FROM cart WHERE user_id = ?', [$uid]) as $r) {
        $lines[cart_key((int) $r['product_id'], $r['size'], $r['color'])] = [
            'product_id' => (int) $r['product_id'], 'size' => $r['size'],
            'color' => $r['color'], 'quantity' => (int) $r['quantity'],
        ];
    }
    return $lines;
}

function cart_write_line(int $productId, string $size, string $color, int $qty): void
{
    $uid = cart_user_id();
    if ($uid === null) {
        $key = cart_key($productId, $size, $color);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key] = ['product_id' => $productId, 'size' => $size, 'color' => $color, 'quantity' => $qty];
        }
        return;
    }
    $where = [$uid, $productId, $size, $color];
    if ($qty <= 0) {
        query('DELETE FROM cart WHERE user_id = ? AND product_id = ? AND size = ? AND color = ?', $where);
    } elseif (fetch_value('SELECT COUNT(*) FROM cart WHERE user_id = ? AND product_id = ? AND size = ? AND color = ?', $where)) {
        query('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND size = ? AND color = ?', array_merge([$qty], $where));
    } else {
        query('INSERT INTO cart (user_id, product_id, size, color, quantity) VALUES (?, ?, ?, ?, ?)', array_merge($where, [$qty]));
    }
}

/** Cart lines joined with current product data. Lines whose product was deleted are dropped. */
function cart_items(): array
{
    $items = [];
    foreach (cart_raw() as $key => $line) {
        $p = get_product($line['product_id']);
        if (!$p) {
            cart_write_line($line['product_id'], $line['size'], $line['color'], 0);
            continue;
        }
        $qty = (int) $line['quantity'];
        $items[] = [
            'key'        => $key,
            'product'    => $p,
            'size'       => $line['size'],
            'color'      => $line['color'],
            'quantity'   => $qty,
            'unit_price' => (float) $p['final_price'],
            'line_total' => (float) $p['final_price'] * $qty,
            'in_stock'   => $p['stock'] >= $qty,
        ];
    }
    return $items;
}

function cart_count(): int
{
    $uid = cart_user_id();
    if ($uid === null) {
        return array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
    }
    return (int) fetch_value('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?', [$uid]);
}

function cart_total(array $items): float
{
    return array_sum(array_column($items, 'line_total'));
}

/** Returns [success, message]. */
function cart_add(int $productId, string $size, string $color, int $qty): array
{
    $p = get_product($productId);
    if (!$p) {
        return [false, 'That product no longer exists.'];
    }
    $sizes = parse_list($p['sizes']) ?: ['One Size'];
    $colors = parse_list($p['colors']) ?: ['Standard'];
    if ($size === '') {
        $size = $sizes[0];
    }
    if ($color === '') {
        $color = $colors[0];
    }
    if (!in_array($size, $sizes, true) || !in_array($color, $colors, true)) {
        return [false, 'Please choose a valid size and colour.'];
    }
    if ($qty < 1) {
        return [false, 'Quantity must be at least 1.'];
    }
    if ((int) $p['stock'] < 1) {
        return [false, $p['name'] . ' is out of stock.'];
    }
    $existing = cart_raw()[cart_key($productId, $size, $color)]['quantity'] ?? 0;
    $newQty = $existing + $qty;
    if ($newQty > (int) $p['stock']) {
        return [false, 'Only ' . (int) $p['stock'] . ' of ' . $p['name'] . ' available.'];
    }
    cart_write_line($productId, $size, $color, $newQty);
    return [true, $p['name'] . ' added to your cart.'];
}

function cart_set_quantity(string $key, int $qty): array
{
    $raw = cart_raw();
    if (!isset($raw[$key])) {
        return [false, 'That item is not in your cart.'];
    }
    $line = $raw[$key];
    if ($qty <= 0) {
        cart_write_line($line['product_id'], $line['size'], $line['color'], 0);
        return [true, 'Item removed from your cart.'];
    }
    $p = get_product($line['product_id']);
    if (!$p) {
        cart_write_line($line['product_id'], $line['size'], $line['color'], 0);
        return [false, 'That product is no longer available.'];
    }
    if ($qty > (int) $p['stock']) {
        return [false, 'Only ' . (int) $p['stock'] . ' of ' . $p['name'] . ' in stock.'];
    }
    cart_write_line($line['product_id'], $line['size'], $line['color'], $qty);
    return [true, 'Cart updated.'];
}

function cart_clear(): void
{
    $uid = cart_user_id();
    if ($uid === null) {
        unset($_SESSION['cart']);
    } else {
        query('DELETE FROM cart WHERE user_id = ?', [$uid]);
    }
}

/** Moves a guest's session cart into the database cart right after login. */
function cart_merge_guest_into_user(int $userId): void
{
    $guest = $_SESSION['cart'] ?? [];
    unset($_SESSION['cart']);
    $_SESSION['user_id'] = $userId;
    foreach ($guest as $line) {
        $p = get_product($line['product_id']);
        if (!$p) {
            continue;
        }
        $existing = cart_raw()[cart_key($line['product_id'], $line['size'], $line['color'])]['quantity'] ?? 0;
        $qty = min($existing + $line['quantity'], (int) $p['stock']);
        if ($qty > 0) {
            cart_write_line($line['product_id'], $line['size'], $line['color'], $qty);
        }
    }
}

/* ---------- Wishlist ---------- */

function wishlist_ids(): array
{
    $uid = cart_user_id();
    if ($uid === null) {
        return [];
    }
    static $cache = [];
    if (!isset($cache[$uid])) {
        $cache[$uid] = array_map('intval', array_column(fetch_all('SELECT product_id FROM wishlist WHERE user_id = ?', [$uid]), 'product_id'));
    }
    return $cache[$uid];
}

/* ---------- Order statuses ---------- */

const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

/* ---------- Login throttling (per browser session) ---------- */

function login_blocked(string $bucket): bool
{
    $a = $_SESSION['attempts'][$bucket] ?? ['count' => 0, 'time' => 0];
    if ($a['count'] >= 5 && time() - $a['time'] < 300) {
        return true;
    }
    if ($a['count'] >= 5) {
        unset($_SESSION['attempts'][$bucket]);
    }
    return false;
}

function login_failed(string $bucket): void
{
    $a = $_SESSION['attempts'][$bucket] ?? ['count' => 0, 'time' => 0];
    $_SESSION['attempts'][$bucket] = ['count' => $a['count'] + 1, 'time' => time()];
}

function login_reset(string $bucket): void
{
    unset($_SESSION['attempts'][$bucket]);
}

/* ---------- Orders ---------- */

/**
 * Changes an order's status. Cancelling returns the items to stock (once).
 * A cancelled order cannot be re-opened. Returns [success, message].
 */
function update_order_status(int $orderId, string $newStatus): array
{
    if (!in_array($newStatus, ORDER_STATUSES, true)) {
        return [false, 'Invalid status.'];
    }
    $order = fetch_one('SELECT id, status FROM orders WHERE id = ?', [$orderId]);
    if (!$order) {
        return [false, 'Order not found.'];
    }
    if ($order['status'] === $newStatus) {
        return [true, 'Status unchanged.'];
    }
    if ($order['status'] === 'cancelled') {
        return [false, 'A cancelled order cannot be re-opened.'];
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($newStatus === 'cancelled') {
            foreach (fetch_all('SELECT product_id, quantity FROM order_items WHERE order_id = ? AND product_id IS NOT NULL', [$orderId]) as $it) {
                query('UPDATE products SET stock = stock + ?, sales_count = CASE WHEN sales_count >= ? THEN sales_count - ? ELSE 0 END WHERE id = ?',
                    [$it['quantity'], $it['quantity'], $it['quantity'], $it['product_id']]);
            }
        }
        $paid = $newStatus === 'delivered' ? ", payment_status = 'paid'" : '';
        query("UPDATE orders SET status = ?$paid WHERE id = ?", [$newStatus, $orderId]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        error_log('BTS order status update failed: ' . $ex->getMessage());
        return [false, 'Could not update the order. Please try again.'];
    }
    return [true, 'Order #' . $orderId . ' is now ' . $newStatus . '.'];
}
