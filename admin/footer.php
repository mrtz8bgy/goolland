<?php
// Get site settings
$site_name = getSetting('site_name', 'گولند - فروشگاه گل و گیاه');
$site_copyright = getSetting('site_copyright', '© ' . date('Y') . ' گولند. تمام حقوق محفوظ است.');
?>

</main>
</div>

<!-- Footer -->
<footer class="admin-footer">
    <div class="container">
        <div class="footer-content">
            <p><?php echo $site_copyright; ?></p>
            <p>ساخت: <a href="https://github.com/mrtz8bgy/goolland" target="_blank">mrtz8bgy</a></p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" title="بازگشت به بالا">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Load Admin JavaScript -->
<script src="assets/js/admin.js"></script>

<!-- Custom JavaScript -->
<?php if (isset($customJS)): ?>
    <script><?php echo $customJS; ?></script>
<?php endif; ?>

</body>
</html>
