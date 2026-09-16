<?php
session_start();
require_once "includes/db.php";';

$error = '';
$success = '';

// Get site info
$siteEmail = getSetting('site_email', 'info@goolland.ir');
$sitePhone = getSetting('site_phone', '021-12345678');
$siteAddress = getSetting('site_address', 'تهران، خیابان ولیعصر، پلاک 123');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($name)) {
        $error = 'لطفا نام و نام خانوادگی را وارد کنید.';
    } elseif (empty($email)) {
        $error = 'لطفا آدرس ایمیل را وارد کنید.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'لطفا آدرس ایمیل معتبر وارد کنید.';
    } elseif (empty($subject)) {
        $error = 'لطفا موضوع را وارد کنید.';
    } elseif (empty($message)) {
        $error = 'لطفا پیام را وارد کنید.';
    } else {
        // Send message
        $result = contactUs([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ]);
        
        if ($result['success']) {
            $success = $result['message'];
            
            // Clear form
            $_POST = [];
            
            // Log activity if user is logged in
            if (isLoggedIn()) {
                logActivity(getCurrentUserId(), 'contact_us', 'User sent contact message');
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Get FAQs
$faqs = getFAQsByCategory();

$pageTitle = 'تماس با ما';
$pageDescription = 'تماس با فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>تماس با ما</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>تماس با ما</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="contact-page">
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-container">
            <!-- Contact Info -->
            <div class="contact-info">
                <div class="contact-info-header">
                    <h2><i class="fas fa-info-circle"></i> اطلاعات تماس</h2>
                    <p>برای ارتباط با ما می‌توانید از روش‌های زیر استفاده کنید</p>
                </div>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>آدرس ایمیل</h4>
                            <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>">
                                <?php echo htmlspecialchars($siteEmail); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-text">
                            <h4>شماره تلفن</h4>
                            <a href="tel:<?php echo htmlspecialchars($sitePhone); ?>">
                                <?php echo htmlspecialchars($sitePhone); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>آدرس</h4>
                            <p><?php echo htmlspecialchars($siteAddress); ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-text">
                            <h4>ساعات کاری</h4>
                            <p>شنبه تا چهارشنبه: 9:00 تا 18:00</p>
                            <p>پنجشنبه: 9:00 تا 14:00</p>
                            <p>جمعه: تعطیل</p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="social-media-section">
                    <h3><i class="fas fa-share-alt"></i> شبکه‌های اجتماعی</h3>
                    <div class="social-media-links">
                        <?php
                        $socialMedia = getSocialMediaLinks();
                        foreach ($socialMedia as $social):
                        ?>
                            <a href="<?php echo htmlspecialchars($social['url']); ?>" 
                               target="_blank" 
                               title="<?php echo htmlspecialchars($social['name']); ?>"
                               class="social-link">
                                <i class="fab fa-<?php echo htmlspecialchars($social['icon']); ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Map -->
                <div class="map-section">
                    <h3><i class="fas fa-map"></i> نقشه</h3>
                    <div class="map-container">
                        <!-- Google Map Embed -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3238.913822399227!2d51.3889736153167!3d35.7020479800687!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3f8e00442b73116b%3A0x3f8e00442b73116b!2sTehran%2C%20Iran!5e0!3m2!1sen!2sus!4v1634567890123!5m2!1sen!2sus"
                            width="100%"
                            height="300"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-section">
                <div class="contact-form-header">
                    <h2><i class="fas fa-paper-plane"></i> ارسال پیام</h2>
                    <p>پیام خود را برای ما ارسال کنید، به زودی پاسخ خواهیم داد</p>
                </div>
                
                <form method="POST" action="contact.php" class="contact-form">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">نام و نام خانوادگی <span style="color: #f44336;">*</span></label>
                                <input type="text" id="name" name="name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? (isLoggedIn() ? $_SESSION['name'] : '')); ?>" 
                                       placeholder="نام و نام خانوادگی خود را وارد کنید" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">آدرس ایمیل <span style="color: #f44336;">*</span></label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? (isLoggedIn() ? $_SESSION['email'] : '')); ?>" 
                                       placeholder="آدرس ایمیل خود را وارد کنید" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">شماره تلفن</label>
                                <input type="tel" id="phone" name="phone" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? (isLoggedIn() ? $_SESSION['phone'] : '')); ?>" 
                                       placeholder="شماره تلفن خود را وارد کنید">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="subject">موضوع <span style="color: #f44336;">*</span></label>
                                <select id="subject" name="subject" class="form-control select-control" required>
                                    <option value="" disabled>موضوع را انتخاب کنید</option>
                                    <option value="سوال درباره محصول" <?php echo ($_POST['subject'] ?? '') === 'سوال درباره محصول' ? 'selected' : ''; ?>>سوال درباره محصول</option>
                                    <option value="پیگیری سفارش" <?php echo ($_POST['subject'] ?? '') === 'پیگیری سفارش' ? 'selected' : ''; ?>>پیگیری سفارش</option>
                                    <option value="شکایت" <?php echo ($_POST['subject'] ?? '') === 'شکایت' ? 'selected' : ''; ?>>شکایت</option>
                                    <option value="پیشنهاد" <?php echo ($_POST['subject'] ?? '') === 'پیشنهاد' ? 'selected' : ''; ?>>پیشنهاد</option>
                                    <option value="سایر" <?php echo ($_POST['subject'] ?? '') === 'سایر' ? 'selected' : ''; ?>>سایر</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">پیام <span style="color: #f44336;">*</span></label>
                        <textarea id="message" name="message" 
                                  class="form-control" 
                                  rows="6" 
                                  placeholder="پیام خود را وارد کنید" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            ارسال پیام
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <?php if (!empty($faqs)): ?>
            <div class="faq-section" style="margin-top: 40px;">
                <div class="container">
                    <h2 class="section-title"><i class="fas fa-question-circle"></i> سوالات متداول</h2>
                    <div class="faq-container">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFAQ(this)">
                                    <i class="fas fa-chevron-down"></i>
                                    <h4><?php echo htmlspecialchars($faq['question']); ?></h4>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle FAQ
function toggleFAQ(element) {
    const faqItem = element.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const icon = element.querySelector('i');
    
    if (answer.style.display === 'block') {
        answer.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-item').forEach(item => {
            item.querySelector('.faq-answer').style.display = 'none';
            item.querySelector('.faq-question i').classList.remove('fa-chevron-up');
            item.querySelector('.faq-question i').classList.add('fa-chevron-down');
        });
        
        answer.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

// Form validation
const form = document.querySelector('.contact-form');
form.addEventListener('submit', function(e) {
    // Additional client-side validation can be added here
    return true;
});
</script>

<?php require_once 'includes/footer.php'; ?>
