<?php
require_once "includes/header.php";

// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

// Form submission
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = 'خطا در اعتبارسنجی فرم. لطفاً دوباره امتحان کنید.';
    } else {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error_message = 'لطفاً تمام فیلدهای مورد نیاز را پر کنید.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'لطفاً یک آدرس ایمیل معتبر وارد کنید.';
        } else {
            // Sanitize inputs
            $name = sanitize($name, $conn);
            $email = sanitize($email, $conn);
            $phone = sanitize($phone, $conn);
            $subject = sanitize($subject, $conn);
            $message = sanitize($message, $conn);
            
            // Insert into database
            $stmt = $conn->prepare(
                "INSERT INTO contacts (name, email, phone, subject, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt->bind_param("sssssss", $name, $email, $phone, $subject, $message, $ip_address, $user_agent);
            
            if ($stmt->execute()) {
                $message_sent = true;
                
                // Clear form
                $_POST = [];
            } else {
                $error_message = 'خطا در ارسال پیام. لطفاً دوباره امتحان کنید.';
            }
        }
    }
}

// Get CSRF token
generateCSRFToken();
$csrf_token = $_SESSION['csrf_token'] ?? '';

?>

<!-- Hero Section -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>تماس با ما</h1>
            <p>ما همیشه آماده پاسخگویی به سوال‌های شما هستیم</p>
        </div>
    </div>
</section>

<!-- Contact Info Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>اطلاعات تماس</h2>
            <p>راه‌های ارتباط با ما</p>
        </div>
        
        <div class="contact-info-grid">
            <!-- Phone -->
            <?php if (!empty($settings['phone'])): ?>
                <div class="contact-card">
                    <div class="contact-icon">📞</div>
                    <div class="contact-content">
                        <h3>تلفن</h3>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['phone']); ?>">
                            <?php echo htmlspecialchars($settings['phone']); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Email -->
            <?php if (!empty($settings['email'])): ?>
                <div class="contact-card">
                    <div class="contact-icon">✉️</div>
                    <div class="contact-content">
                        <h3>ایمیل</h3>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>">
                            <?php echo htmlspecialchars($settings['email']); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Address -->
            <?php if (!empty($settings['address'])): ?>
                <div class="contact-card">
                    <div class="contact-icon">📍</div>
                    <div class="contact-content">
                        <h3>آدرس</h3>
                        <p><?php echo nl2br(htmlspecialchars($settings['address'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Working Hours -->
            <?php if (!empty($settings['working_hours'])): ?>
                <div class="contact-card">
                    <div class="contact-icon">⏰</div>
                    <div class="contact-content">
                        <h3>ساعات کاری</h3>
                        <p><?php echo htmlspecialchars($settings['working_hours']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<?php if (!empty($settings['google_map'])): ?>
    <section class="section map-section">
        <div class="container">
            <div class="section-title">
                <h2>موقعیت ما روی نقشه</h2>
            </div>
            <div class="map-container">
                <?php echo $settings['google_map']; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Contact Form Section -->
<section class="section contact-form-section">
    <div class="container">
        <div class="contact-form-grid">
            <div class="contact-form-image">
                <div class="image-placeholder">
                    <div class="placeholder-icon">💬</div>
                    <span>فرم تماس</span>
                </div>
            </div>
            
            <div class="contact-form-wrapper">
                <div class="section-title">
                    <h2>فرم تماس</h2>
                    <p>پیام خود را برای ما ارسال کنید</p>
                </div>
                
                <?php if ($message_sent): ?>
                    <div class="alert alert-success">
                        ✅ پیام شما با موفقیت ارسال شد. به زودی با شما تماس خواهیم گرفت.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">نام و نام خانوادگی *</label>
                            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">آدرس ایمیل *</label>
                            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">شماره تلفن</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">موضوع *</label>
                            <select id="subject" name="subject" required>
                                <option value="" disabled selected>موضوع را انتخاب کنید</option>
                                <option value="سوال درباره محصول" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'سوال درباره محصول') ? 'selected' : ''; ?>>سوال درباره محصول</option>
                                <option value="سفارش گروه" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'سفارش گروه') ? 'selected' : ''; ?>>سفارش گروه</option>
                                <option value="پشتیبانی" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'پشتیبانی') ? 'selected' : ''; ?>>پشتیبانی</option>
                                <option value="همکاری" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'همکاری') ? 'selected' : ''; ?>>همکاری</option>
                                <option value="سایر" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'سایر') ? 'selected' : ''; ?>>سایر</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">پیام شما *</label>
                        <textarea id="message" name="message" rows="6" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="send_message" class="btn btn-primary">
                        ارسال پیام
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section faq-section">
    <div class="container">
        <div class="section-title">
            <h2>سوال‌های متداول</h2>
            <p>پاسخ به سوال‌های رایج</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">
                    <h4>چگونه می‌توانم سفارش دهم؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>شما می‌توانید از طریق سایت ما سفارش دهید. کافی است محصول مورد نظر خود را انتخاب کنید و به سبد خرید اضافه نمایید. سپس مراحل پرداخت را تکمیل کنید.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>آیا تحویل در محل امکان دارد؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>بله، ما در اکثر مناطق تهران و شهرهای بزرگ تحویل در محل را ارائه می‌دهیم. هزینه تحویل بسته به مکان متفاوت است.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>چگونه می‌توانم پرداخت کنم؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>ما روش‌های پرداخت متنوعی را پشتیبانی می‌کنیم از جمله پرداخت آنلاین، پرداخت در محل (برای تهران) و انتقال بانکی.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>آیا گل‌ها تازه هستند؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>بله، تمام گل‌های ما مستقیماً از گلخانه‌ها و مزارع معتبر تهیه می‌شوند و در سریع‌ترین زمان ممکن به دست شما می‌رسند.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>چگونه از گیاهان مراقبت کنم؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>ما مقالات آموزشی زیادی در سایت داریم که به شما یاد می‌دهند چگونه از گیاهان مختلف مراقبت کنید. همچنین می‌توانید با تیم پشتیبانی ما تماس بگیرید.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>آیا می‌توانم سفارش خود را کنسل کنم؟</h4>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    <p>بله، شما می‌توانید سفارش خود را تا قبل از ارسال کنسل کنید. برای این کار کافی است با تیم پشتیبانی ما تماس بگیرید.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Social Media Section -->
<?php if (!empty($settings['instagram']) || !empty($settings['telegram']) || !empty($settings['whatsapp'])): ?>
    <section class="section social-section">
        <div class="container">
            <div class="section-title">
                <h2>ما را در شبکه‌های اجتماعی دنبال کنید</h2>
            </div>
            
            <div class="social-grid">
                <?php if (!empty($settings['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['instagram']); ?>" target="_blank" class="social-card">
                        <div class="social-icon">📸</div>
                        <span>اینستاگرام</span>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($settings['telegram'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['telegram']); ?>" target="_blank" class="social-card">
                        <div class="social-icon">💬</div>
                        <span>تلگرام</span>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($settings['whatsapp'])): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['whatsapp']); ?>" target="_blank" class="social-card">
                        <div class="social-icon">🟢</div>
                        <span>واتساپ</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
require_once "includes/footer.php";
?>
