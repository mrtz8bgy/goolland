/**
 * Goolland - Additional JavaScript Functions
 * Luxury Flower & Plant Shop
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize functions
    initAJAXFunctions();
    initFormHandlers();
    initNotificationSystem();
});

// AJAX Functions
function initAJAXFunctions() {
    // Global AJAX error handler
    window.addEventListener('error', function(e) {
        if (e.target && e.target.tagName === 'IMG') {
            e.target.src = 'assets/images/placeholder.png';
        }
    });
}

// Form Handlers
function initFormHandlers() {
    // Login form handler
    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitLoginForm(this);
        });
    }
    
    // Register form handler
    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitRegisterForm(this);
        });
    }
    
    // Contact form handler
    const contactForm = document.querySelector('#contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitContactForm(this);
        });
    }
    
    // Newsletter form handler
    const newsletterForms = document.querySelectorAll('.newsletter-form');
    newsletterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitNewsletterForm(this);
        });
    });
    
    // Forgot password form handler
    const forgotPasswordForm = document.querySelector('#forgot-password-form');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForgotPasswordForm(this);
        });
    }
    
    // Reset password form handler
    const resetPasswordForm = document.querySelector('#reset-password-form');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitResetPasswordForm(this);
        });
    }
}

// Login Form Submission
function submitLoginForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ورود...';
    submitBtn.disabled = true;
    
    fetch('includes/auth.php?action=login', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'index.php';
            }
        } else {
            showNotification(data.message || 'خطا در ورود', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Register Form Submission
function submitRegisterForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Validate password match
    const password = form.querySelector('input[name="password"]').value;
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        showNotification('رمز عبور و تکرار آن یکسان نیست', 'error');
        return;
    }
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ثبت‌نام...';
    submitBtn.disabled = true;
    
    fetch('includes/auth.php?action=register', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'login.php';
            }
        } else {
            showNotification(data.message || 'خطا در ثبت‌نام', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Contact Form Submission
function submitContactForm(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ارسال...';
    submitBtn.disabled = true;
    
    fetch('includes/contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('پیام شما با موفقیت ارسال شد. به زودی با شما تماس گرفته خواهد شد.', 'success');
            form.reset();
        } else {
            showNotification(data.message || 'خطا در ارسال پیام', 'error');
        }
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Newsletter Form Submission
function submitNewsletterForm(form) {
    const emailInput = form.querySelector('input[name="email"]');
    const email = emailInput.value.trim();
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    if (!email) {
        showNotification('لطفا آدرس ایمیل خود را وارد کنید', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('لطفا آدرس ایمیل معتبر وارد کنید', 'error');
        return;
    }
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
    
    fetch('includes/newsletter.php', {
        method: 'POST',
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('شما با موفقیت در خبرنامه ثبت‌نام کردید', 'success');
            emailInput.value = '';
        } else {
            showNotification(data.message || 'خطا در ثبت‌نام در خبرنامه', 'error');
        }
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Forgot Password Form Submission
function submitForgotPasswordForm(form) {
    const emailInput = form.querySelector('input[name="email"]');
    const email = emailInput.value.trim();
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    if (!email) {
        showNotification('لطفا آدرس ایمیل خود را وارد کنید', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('لطفا آدرس ایمیل معتبر وارد کنید', 'error');
        return;
    }
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ارسال...';
    submitBtn.disabled = true;
    
    fetch('includes/auth.php?action=forgot_password', {
        method: 'POST',
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.reset();
        } else {
            showNotification(data.message || 'خطا در ارسال درخواست', 'error');
        }
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Reset Password Form Submission
function submitResetPasswordForm(form) {
    const password = form.querySelector('input[name="new_password"]').value;
    const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
    const token = form.querySelector('input[name="token"]').value;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    if (!password) {
        showNotification('لطفا رمز عبور جدید را وارد کنید', 'error');
        return;
    }
    
    if (password.length < 6) {
        showNotification('رمز عبور باید حداقل 6 کاراکتر باشد', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showNotification('رمز عبور و تکرار آن یکسان نیست', 'error');
        return;
    }
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال تغییر...';
    submitBtn.disabled = true;
    
    fetch('includes/auth.php?action=reset_password', {
        method: 'POST',
        body: 'token=' + encodeURIComponent(token) + '&new_password=' + encodeURIComponent(password)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('رمز عبور شما با موفقیت تغییر یافت', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showNotification(data.message || 'خطا در تغییر رمز عبور', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showNotification('خطا در ارتباط با سرور', 'error');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Notification System
function initNotificationSystem() {
    // Create notification container if not exists
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
}

function showNotification(message, type = 'info', timeout = 5000) {
    const notificationContainer = document.querySelector('.notification-container');
    
    if (!notificationContainer) {
        console.log(message);
        return;
    }
    
    // Remove existing notifications
    const existingNotifications = notificationContainer.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Auto remove after timeout
    if (timeout > 0) {
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, timeout);
    }
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Validation Functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize on page load
window.addEventListener('load', function() {
    // Additional initialization if needed
});
