</main>

<footer class="site-footer">
    <div class="footer-grid">
        <div>
            <h3>BEYOND THE STYLE</h3>
            <p>Premium fashion for men and women.</p>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= e(url('products.php')) ?>">All Products</a>
            <a href="<?= e(url('men.php')) ?>">Men</a>
            <a href="<?= e(url('women.php')) ?>">Women</a>
        </div>
        <div>
            <h4>Help</h4>
            <a href="<?= e(url('about.php')) ?>">About Us</a>
            <a href="<?= e(url('contact.php')) ?>">Contact</a>
            <a href="<?= e(url('orders.php')) ?>">My Orders</a>
        </div>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Beyond The Style | All Rights Reserved</p>
</footer>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
