<?php
require_once "includes/header.php";

// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

// Get team members (placeholder - can be added to database)
$team_members = [
    ['name' => 'محمد رضا', 'position' => 'بنیانگذار و مدیر', 'image' => null, 'description' => 'بیش از 10 سال تجربه در زمینه کشت و فروش گل و گیاه'],
    ['name' => 'فاطمه کرمی', 'position' => 'متخصص گیاهان آپارتمانی', 'image' => null, 'description' => 'کارشناس ارشد باغبانی و نگهداری از گیاهان داخلی'],
    ['name' => 'علی حسینی', 'position' => 'مدیر بازاریابی', 'image' => null, 'description' => 'متخصص در بازاریابی دیجیتال و فروش آنلاین'],
];

// Get statistics
$products_count = getTableCount('products', "status = 'publish'", $conn);
$articles_count = getTableCount('articles', "status = 'publish'", $conn);
$categories_count = getTableCount('categories', "status = 'active'", $conn);
$orders_count = getTableCount('orders', "status = 'completed'", $conn);

?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>درباره ما</h1>
            <p>داستان ما در مورد عشق به گل‌ها و گیاهان است</p>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="about-grid">
            <!-- About Text -->
            <div class="about-text">
                <div class="section-title">
                    <h2>کی هستیم و چه می‌کنیم؟</h2>
                </div>
                
                <div class="about-content">
                    <?php if (!empty($settings['about_text'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($settings['about_text'])); ?></p>
                    <?php else: ?>
                        <p>
                            در <strong>Goolland</strong>، ما معتقدیم که گل‌ها و گیاهان می‌توانند زندگی را زیباتر، شادتر و سالم‌تر کنند. 
                            با بیش از 10 سال تجربه در زمینه کشت و فروش گل و گیاه، ما بهترین محصولات را با کیفیت بالا 
                            و قیمت مناسب به شما ارائه می‌دهیم.
                        </p>
                        <p>
                            تیم ما متشکل از متخصصین باتجربه در زمینه باغبانی و نگهداری از گیاهان است. ما نه تنها 
                            محصولات باکیفیت ارائه می‌دهیم، بلکه راهنمایی‌ها و مشاوره‌های تخصصی را نیز در اختیار شما 
                            قرار می‌دهیم تا بتوانید بهترین انتخاب را داشته باشید.
                        </p>
                        <p>
                            ماموریت ما این است که زیبایی طبیعت را به خانه‌ها، دفاتر و زندگی مردم بیاوریم. ما معتقدیم 
                            که هر کسی سزاوار داشتن فضای سبز و زیبایی در اطراف خود است.
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Values -->
                <div class="about-values">
                    <h3>ارزش‌های ما</h3>
                    <div class="values-grid">
                        <div class="value-item">
                            <div class="value-icon">🌱</div>
                            <div class="value-content">
                                <h4>کیفیت بالا</h4>
                                <p>ما فقط محصولاتی با بالاترین کیفیت را ارائه می‌دهیم</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">💚</div>
                            <div class="value-content">
                                <h4>عشق به طبیعت</h4>
                                <p>ما به طبیعت عشق می‌ورزیم و سعی می‌کنیم آن را حفظ کنیم</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">👨‍👩‍👧‍👦</div>
                            <div class="value-content">
                                <h4>رضایت مشتری</h4>
                                <p>رضایت شما برای ما از همه چیز مهم‌تر است</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <div class="value-icon">🚚</div>
                            <div class="value-content">
                                <h4>تحویل سریع</h4>
                                <p>ما سعی می‌کنیم سفارشات شما را در سریع‌ترین زمان تحویل دهیم</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- About Image -->
            <div class="about-image">
                <div class="image-placeholder">
                    <?php if (!empty($settings['logo'])): ?>
                        <img src="/assets/images/<?php echo htmlspecialchars($settings['logo']); ?>" alt="Goolland">
                    <?php else: ?>
                        <div class="placeholder-icon">🌿</div>
                        <span>درباره Goolland</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="section stats-section">
    <div class="container">
        <div class="section-title">
            <h2>آمار و ارقام</h2>
            <p>برخی از دستاوردهای ما</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($products_count); ?></div>
                <div class="stat-label">محصول مختلف</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($categories_count); ?></div>
                <div class="stat-label">دسته‌بندی</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($articles_count); ?></div>
                <div class="stat-label">مقاله آموزشی</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($orders_count); ?></div>
                <div class="stat-label">سفارش موفق</div>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="section team-section">
    <div class="container">
        <div class="section-title">
            <h2>تیم ما</h2>
            <p>با اعضای تیم آشنا شوید</p>
        </div>
        
        <div class="team-grid">
            <?php foreach ($team_members as $member): ?>
                <div class="team-member">
                    <div class="member-image">
                        <?php if ($member['image']): ?>
                            <img src="/assets/images/<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <?php else: ?>
                            <div class="member-placeholder">👤</div>
                        <?php endif; ?>
                    </div>
                    <div class="member-info">
                        <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p class="member-position"><?php echo htmlspecialchars($member['position']); ?></p>
                        <p class="member-description"><?php echo htmlspecialchars($member['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section why-choose-us">
    <div class="container">
        <div class="why-grid">
            <div class="why-image">
                <div class="image-placeholder">
                    <div class="placeholder-icon">❓</div>
                    <span>چرا Goolland؟</span>
                </div>
            </div>
            <div class="why-content">
                <div class="section-title">
                    <h2>چرا از Goolland خرید کنیم؟</h2>
                </div>
                
                <div class="why-list">
                    <div class="why-item">
                        <div class="why-icon">✅</div>
                        <div class="why-text">
                            <h4>محصولات تازه و باکیفیت</h4>
                            <p>ما محصولات خود را مستقیماً از مزارع و گلخانه‌های معتبر تهیه می‌کنیم</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon">🌍</div>
                        <div class="why-text">
                            <h4>تحویل در سراسر کشور</h4>
                            <p>ما به تمام شهرهای ایران سرویس‌دهی می‌کنیم</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon">💰</div>
                        <div class="why-text">
                            <h4>قیمت‌های رقابتی</h4>
                            <p>ما بهترین قیمت‌ها را با بالاترین کیفیت ارائه می‌دهیم</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon">🛡️</div>
                        <div class="why-text">
                            <h4>ضمانت کیفیت</h4>
                            <p>اگر از محصول ما راضی نبودید، پول شما را بازگردانیم</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon">💬</div>
                        <div class="why-text">
                            <h4>پشتیبانی 24/7</h4>
                            <p>تیم پشتیبانی ما همیشه آماده پاسخگویی به سوال‌های شماست</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>آماده‌اید تا سفارش دهید؟</h2>
            <p>همین امروز از محصولات ما دیدن کنید و سفارش خود را ثبت نمایید</p>
            <div class="cta-buttons">
                <a href="/products.php" class="btn btn-primary">
                    مشاهده محصولات
                </a>
                <a href="/contact.php" class="btn btn-secondary">
                    تماس با ما
                </a>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>
