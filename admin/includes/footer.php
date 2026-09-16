<?php
// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}
?>

<!-- Footer -->
<footer class="admin-footer" style="padding: 25px 30px; background: var(--admin-surface); border-top: 1px solid var(--admin-border); margin-top: auto;">
    <div style="max-width: 1400px; margin: auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div style="color: var(--admin-text-muted); font-size: 14px;">
            © <?php echo date('Y'); ?> 
            <a href="/" style="color: var(--admin-primary);" target="_blank">Goolland</a>
            - تمامی حقوق محفوظ است.
        </div>
        <div style="display: flex; gap: 15px;">
            <span style="color: var(--admin-text-muted); font-size: 13px;">
                نسخه ۲.۰.۰
            </span>
            <a href="../" style="color: var(--admin-text-muted); font-size: 13px;" target="_blank">
                مشاهده سایت
                <i class="fas fa-external-link-alt" style="font-size: 10px; margin-right: 5px;"></i>
            </a>
        </div>
    </div>
</footer>
