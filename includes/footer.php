</main>

<footer class="site-footer">

    <div class="container footer-inner">

        <div>
            <h3>
                <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>
            </h3>

            <p>
                <?php echo htmlspecialchars($settings["description"] ?? "فروش و معرفی گل و گیاه"); ?>
            </p>
        </div>

        <div>
            <h3>ارتباط با ما</h3>

            <?php if (!empty($settings["phone"])): ?>
                <p>📞 <?php echo htmlspecialchars($settings["phone"]); ?></p>
            <?php endif; ?>

            <?php if (!empty($settings["address"])): ?>
                <p>📍 <?php echo htmlspecialchars($settings["address"]); ?></p>
            <?php endif; ?>
        </div>

        <div>
            <h3>شبکه‌های اجتماعی</h3>

            <?php if (!empty($settings["instagram"])): ?>
                <a href="<?php echo htmlspecialchars($settings["instagram"]); ?>" target="_blank">
                    Instagram
                </a>
            <?php endif; ?>

            <?php if (!empty($settings["telegram"])): ?>
                <a href="<?php echo htmlspecialchars($settings["telegram"]); ?>" target="_blank">
                    Telegram
                </a>
            <?php endif; ?>

            <?php if (!empty($settings["whatsapp"])): ?>
                <a href="<?php echo htmlspecialchars($settings["whatsapp"]); ?>" target="_blank">
                    WhatsApp
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="copyright">

        © <?php echo date("Y"); ?>

        <?php echo htmlspecialchars($settings["site_name"] ?? "Goolland"); ?>

        - تمامی حقوق محفوظ است.

    </div>

</footer>

<script src="/assets/js/script.js"></script>

</body>
</html>