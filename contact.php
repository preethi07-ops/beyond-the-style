<?php
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
$form = ['name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'subject' => '', 'message' => ''];
$errors = [];

if (is_post()) {
    csrf_check();
    foreach ($form as $k => $_) {
        $form[$k] = post($k);
    }
    if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($form['subject'] === '' || mb_strlen($form['subject']) > 150) {
        $errors['subject'] = 'Please enter a subject (up to 150 characters).';
    }
    if (mb_strlen($form['message']) < 10 || mb_strlen($form['message']) > 3000) {
        $errors['message'] = 'Your message must be between 10 and 3000 characters.';
    }
    if (!$errors) {
        query('INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?)',
            [$form['name'], $form['email'], $form['subject'], $form['message'], date('Y-m-d H:i:s')]);
        flash('success', 'Thanks for getting in touch! We will reply to you soon.');
        redirect('contact.php');
    }
}

$pageTitle = 'Contact';
include __DIR__ . '/includes/header.php';
$err = fn(string $k) => isset($errors[$k]) ? '<small class="text-error">' . e($errors[$k]) . '</small>' : '';
?>
<section class="section narrow">
    <h2>Contact Us</h2>
    <p class="muted">Questions about an order, sizing or anything else? Send us a message.</p>
    <form method="post" class="panel" novalidate>
        <?= csrf_field() ?>
        <label>Name <input type="text" name="name" value="<?= e($form['name']) ?>" required><?= $err('name') ?></label>
        <label>Email <input type="email" name="email" value="<?= e($form['email']) ?>" required><?= $err('email') ?></label>
        <label>Subject <input type="text" name="subject" value="<?= e($form['subject']) ?>" required><?= $err('subject') ?></label>
        <label>Message <textarea name="message" rows="6" required><?= e($form['message']) ?></textarea><?= $err('message') ?></label>
        <button class="btn" type="submit">Send message</button>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
