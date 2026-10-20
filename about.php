<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About Us';
include __DIR__ . '/includes/header.php';
?>
<section class="about">
    <h2>About Us</h2>
    <p>
        Beyond The Style offers premium clothing and accessories for men and women.
        Our mission is to provide high-quality fashion at affordable prices while
        keeping up with the latest trends.
    </p>
    <p>
        From everyday essentials to statement pieces, every item in our collection is chosen to help you
        express yourself with confidence, season after season.
    </p>
    <div class="actions">
        <a class="btn" href="<?= e(url('products.php')) ?>">Explore the collection</a>
        <a class="btn btn-outline" href="<?= e(url('contact.php')) ?>">Get in touch</a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
