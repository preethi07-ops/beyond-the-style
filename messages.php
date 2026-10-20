<?php
require_once __DIR__ . '/../includes/admin-header.php';

if (is_post()) {
    csrf_check();
    query('DELETE FROM contact_messages WHERE id = ?', [(int) post('id')]);
    flash('success', 'Message deleted.');
    redirect('admin/messages.php');
}
$messages = fetch_all('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 100');
$pageTitle = 'Messages';
?>
<h1>Contact messages</h1>
<?php if (!$messages): ?>
    <p class="empty">No messages yet.</p>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
    <article class="panel message">
        <div class="section-head">
            <h3><?= e($m['subject']) ?></h3>
            <small class="muted"><?= e(date('d M Y H:i', strtotime($m['created_at']))) ?></small>
        </div>
        <p><strong><?= e($m['name']) ?></strong> &lt;<a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>&gt;</p>
        <p><?= nl2br(e($m['message'])) ?></p>
        <form method="post" onsubmit="return confirm('Delete this message?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
        </form>
    </article>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
