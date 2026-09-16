/**
 * Goolland - Premium Flower Shop JavaScript
 * Version: 2.0.0
 * Description: Enhanced frontend functionality with smooth animations
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌿 Goolland website loaded successfully');
    
    // Initialize all components
    initMobileMenu();
    initBackToTop();
    initHeaderScroll();
    initSmoothScroll();
    initLazyLoading();
    initNewsletter();
    initProductCards();
    initCart();
    initSearch();
    initAnimations();
    initModal();
});

// ============================================
// Mobile Menu
// ============================================

function initMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.createElement('button');
    
    if (!mobileMenuToggle || !mobileMenu) return;
    
    // Add close button to mobile menu
    mobileMenuClose.className = 'mobile-menu-close';
    mobileMenuClose.innerHTML = '✕';
    mobileMenuClose.style.cssText = 'position: absolute; top: 15px; right: 15px; background: none; border: none; color: white; font-size: 24px; cursor: pointer; z-index: 1000;';
    mobileMenu.prepend(mobileMenuClose);
    
    // Toggle mobile menu
    mobileMenuToggle.addEventListener('click', function() {
        mobileMenu.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    // Close mobile menu
    mobileMenuClose.addEventListener('click', function() {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Close menu when clicking on a link
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// ============================================
// Back to Top Button
// ============================================

function initBackToTop() {
    const backToTop = document.getElementById('back-to-top');
    
    if (!backToTop) return;
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    // Scroll to top with smooth animation
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ============================================
// Header Scroll Effect
// ============================================

function initHeaderScroll() {
    const header = document.querySelector('.site-header');
    
    if (!header) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        // Add shadow on scroll
        if (currentScroll > 50) {
            header.style.boxShadow = '0 10px 40px rgba(0,0,0,0.2)';
        } else {
            header.style.boxShadow = '0 10px 40px rgba(0,0,0,0.18)';
        }
        
        lastScroll = currentScroll;
    });
}

// ============================================
// Smooth Scroll
// ============================================

function initSmoothScroll() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ============================================
// Lazy Loading Images
// ============================================

function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

// ============================================
// Newsletter Form
// ============================================

function initNewsletter() {
    const newsletterForm = document.querySelector('.newsletter-form');
    
    if (!newsletterForm) return;
    
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = this.querySelector('input[type="email"]');
        const email = emailInput.value.trim();
        
        if (!email) {
            showNotification('لطفاً آدرس ایمیل خود را وارد کنید', 'error');
            return;
        }
        
        if (!isValidEmail(email)) {
            showNotification('لطفاً یک آدرس ایمیل معتبر وارد کنید', 'error');
            return;
        }
        
        // Submit form via AJAX
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'با موفقیت ثبت شد!', 'success');
                emailInput.value = '';
            } else {
                showNotification(data.message || 'خطا در ثبت ایمیل', 'error');
            }
        })
        .catch(error => {
            showNotification('خطا در اتصال به سرور', 'error');
            console.error('Newsletter error:', error);
        });
    });
}

// ============================================
// Product Cards Animation
// ============================================

function initProductCards() {
    const productCards = document.querySelectorAll('.product-card');
    
    if ('IntersectionObserver' in window) {
        const cardObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });
        
        productCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            cardObserver.observe(card);
        });
    }
}

// ============================================
// Cart Functionality
// ============================================

function initCart() {
    const cartButtons = document.querySelectorAll('.add-to-cart');
    
    cartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            
            if (!productId) return;
            
            // Add to cart via AJAX
            fetch('/cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`${productName} به سبد خرید اضافه شد`, 'success');
                    updateCartCount(data.cart_count);
                } else {
                    showNotification(data.message || 'خطا در افزودن به سبد خرید', 'error');
                }
            })
            .catch(error => {
                showNotification('خطا در اتصال به سرور', 'error');
                console.error('Cart error:', error);
            });
        });
    });
}

// ============================================
// Search Functionality
// ============================================

function initSearch() {
    const searchForm = document.querySelector('.header-search form');
    
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', function(e) {
        const searchInput = this.querySelector('input[type="text"]');
        const searchQuery = searchInput.value.trim();
        
        if (!searchQuery) {
            e.preventDefault();
            showNotification('لطفاً عبارت جستجو را وارد کنید', 'error');
        }
    });
    
    // Auto-suggest search (if enabled)
    const searchInput = document.querySelector('.header-search input[type="text"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            const query = this.value.trim();
            if (query.length < 2) return;
            
            searchTimeout = setTimeout(() => {
                // Implement auto-suggest here
                // fetch('/search-suggest.php?q=' + encodeURIComponent(query))
                // .then(response => response.json())
                // .then(data => {
                //     // Show suggestions
                // });
            }, 300);
        });
    }
}

// ============================================
// Scroll Animations
// ============================================

function initAnimations() {
    // Animate elements on scroll
    const animateOnScroll = document.querySelectorAll('.animate-on-scroll');
    
    if ('IntersectionObserver' in window) {
        const animateObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, {
            threshold: 0.1
        });
        
        animateOnScroll.forEach(element => {
            animateObserver.observe(element);
        });
    }
    
    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero');
    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrollPosition = window.pageYOffset;
            const heroContent = heroSection.querySelector('.hero-content');
            
            if (heroContent && scrollPosition < window.innerHeight) {
                heroContent.style.transform = `translateY(${scrollPosition * 0.3}px)`;
                heroContent.style.opacity = 1 - (scrollPosition / window.innerHeight);
            }
        });
    }
}

// ============================================
// Modal Functionality
// ============================================

function initModal() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Close modal when clicking on close button or outside
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const closeButton = modal.querySelector('.modal-close');
        
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
}

// ============================================
// Utility Functions
// ============================================

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    // Style the notification
    Object.assign(notification.style, {
        position: 'fixed',
        bottom: '20px',
        right: '20px',
        background: type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6',
        color: 'white',
        padding: '15px 25px',
        borderRadius: '12px',
        boxShadow: '0 10px 40px rgba(0,0,0,0.2)',
        display: 'flex',
        alignItems: 'center',
        gap: '15px',
        zIndex: '10000',
        animation: 'slideIn 0.3s ease'
    });
    
    document.body.appendChild(notification);
    
    // Close button functionality
    const closeButton = notification.querySelector('.notification-close');
    closeButton.style.cssText = 'background: none; border: none; color: white; font-size: 20px; cursor: pointer;';
    closeButton.addEventListener('click', function() {
        notification.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Update cart count
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
    });
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('fa-IR').format(price);
}

// ============================================
// CSS Animations (add to head)
// ============================================

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    /* Mobile Menu Styles */
    .mobile-menu {
        position: fixed;
        top: 0;
        right: -100%;
        width: 80%;
        max-width: 350px;
        height: 100vh;
        background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        backdrop-filter: blur(20px);
        border-left: 1px solid rgba(255,255,255,0.1);
        z-index: 9999;
        transition: right 0.3s ease;
        padding-top: 60px;
    }
    
    .mobile-menu.active {
        right: 0;
    }
    
    .mobile-menu nav {
        display: flex;
        flex-direction: column;
        padding: 20px;
    }
    
    .mobile-menu a {
        color: white;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 10px;
        transition: 0.2s;
    }
    
    .mobile-menu a:hover {
        background: rgba(255,255,255,0.1);
    }
    
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }
    
    /* Back to Top */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #86efac, #22c55e);
        border: none;
        color: #031109;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 10px 35px rgba(34,197,94,0.3);
        transition: all 0.3s ease;
        z-index: 999;
        display: none;
    }
    
    .back-to-top:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 45px rgba(34,197,94,0.4);
    }
    
    /* WhatsApp Float */
    .whatsapp-float {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #25d366;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 10px 35px rgba(37, 211, 102, 0.3);
        transition: all 0.3s ease;
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .whatsapp-float:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 45px rgba(37, 211, 102, 0.4);
    }
    
    /* Newsletter */
    .newsletter-section {
        padding: 60px 0;
        background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border-top: 1px solid rgba(255,255,255,0.05);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .newsletter-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
    }
    
    .newsletter-text h3 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    .newsletter-text p {
        color: var(--muted);
    }
    
    .newsletter-form {
        display: flex;
        gap: 10px;
    }
    
    .newsletter-form input {
        padding: 12px 18px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(0,0,0,0.3);
        color: white;
        min-width: 280px;
    }
    
    .newsletter-form input::placeholder {
        color: var(--muted);
    }
    
    .newsletter-form button {
        min-width: 120px;
    }
    
    @media (max-width: 768px) {
        .newsletter-content {
            flex-direction: column;
            text-align: center;
        }
        
        .newsletter-form {
            flex-direction: column;
        }
        
        .newsletter-form input {
            min-width: auto;
        }
        
        .mobile-menu-toggle {
            display: block;
        }
    }
    
    /* Header Cart */
    .header-cart {
        position: relative;
        color: white;
        font-size: 20px;
        text-decoration: none;
        transition: 0.2s;
    }
    
    .header-cart:hover {
        color: var(--green-light);
    }
    
    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--green);
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Breadcrumb */
    .breadcrumb {
        padding: 15px 0;
        background: rgba(255,255,255,0.02);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .breadcrumb .container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .breadcrumb a,
    .breadcrumb span {
        color: var(--muted);
        font-size: 14px;
    }
    
    .breadcrumb a:hover {
        color: var(--green-light);
    }
    
    .breadcrumb-separator {
        color: rgba(255,255,255,0.2);
    }
    
    /* Footer Styles */
    .footer-section {
        flex: 1;
        min-width: 200px;
    }
    
    .footer-title {
        font-size: 18px;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }
    
    .footer-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 40px;
        height: 2px;
        background: var(--green);
        border-radius: 20px;
    }
    
    .footer-description {
        color: var(--muted);
        line-height: 2;
        margin-bottom: 20px;
    }
    
    .footer-links {
        list-style: none;
    }
    
    .footer-links li {
        margin-bottom: 12px;
    }
    
    .footer-links a {
        color: var(--muted);
        transition: 0.2s;
    }
    
    .footer-links a:hover {
        color: var(--green-light);
        transform: translateX(-5px);
    }
    
    .footer-articles {
        list-style: none;
    }
    
    .footer-articles li {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .footer-articles a {
        color: var(--muted);
        transition: 0.2s;
    }
    
    .footer-articles a:hover {
        color: var(--green-light);
    }
    
    .article-date {
        display: block;
        font-size: 12px;
        color: var(--muted-2);
        margin-top: 5px;
    }
    
    .footer-contact {
        list-style: none;
    }
    
    .footer-contact li {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .contact-icon {
        font-size: 18px;
    }
    
    .footer-contact a,
    .footer-contact span {
        color: var(--muted);
    }
    
    .footer-contact a:hover {
        color: var(--green-light);
    }
    
    .footer-social {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .social-icon {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
        border: 1px solid rgba(255,255,255,0.1);
        transition: 0.2s;
    }
    
    .social-icon:hover {
        background: var(--green);
        border-color: var(--green);
        transform: translateY(-3px);
    }
    
    .footer-bottom {
        padding-top: 40px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    
    .footer-stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 30px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        display: block;
        font-size: 28px;
        font-weight: 800;
        color: var(--green-light);
    }
    
    .stat-label {
        display: block;
        font-size: 14px;
        color: var(--muted);
        margin-top: 5px;
    }
    
    .footer-copyright {
        text-align: center;
    }
    
    .footer-copyright p {
        color: var(--muted-2);
        font-size: 14px;
    }
    
    .footer-copyright strong {
        color: var(--green-light);
    }
`;
document.head.appendChild(style);

console.log('🌿 All JavaScript components initialized successfully');
