</div> <!-- .page-content -->
<footer class="site-footer cyberpunk-footer">
    <div class="container footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <a class="brand-logo" href="<?= BASE_URL ?>">
                    Blinde<span>Doos</span>
                </a>
                <p class="footer-tag">The Unknown — Curated blindbox selection</p>
            </div>

            <nav class="footer-nav" aria-label="Footer Navigation">
                <a class="footer-link" href="<?= BASE_URL ?>">Home</a>
                <a class="footer-link" href="<?= BASE_URL ?>views/pages/products/product_list.php">Shop</a>
                <a class="footer-link" href="<?= BASE_URL ?>views/pages/about.php">About</a>
                <a class="footer-link" href="<?= BASE_URL ?>views/pages/contact.php">Contact</a>
                <a class="footer-link" href="<?= BASE_URL ?>views/pages/member/profile.php?section=edit-profile">Account</a>
            </nav>

            <div class="footer-newsletter">
                <form class="newsletter-form" method="post" action="#" onsubmit="return false;" aria-label="Subscribe to newsletter">
                    <label for="newsletter-email" class="sr-only">Email address</label>
                    <input id="newsletter-email" type="email" name="email" placeholder="you@domain.com" required>
                    <button type="submit" class="btn-subscribe">Subscribe</button>
                </form>
                <p class="newsletter-desc">Get exclusive drops &amp; early access — no spam.</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> BlindeDoos</p>
            <p class="footer-credits">Made with <span aria-hidden="true">&hearts;</span> in the shadows</p>
        </div>
    </div>
</footer>
</body>

</html>