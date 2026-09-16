/**
 * Goolland - Main JavaScript File
 * Luxury Flower & Plant Shop
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initMobileMenu();
    initCategoriesMenu();
    initPriceSlider();
    initNiceSelect();
    initSlickSliders();
    initBackToTop();
    initScrollAnimations();
    initTestimonialSlider();
    initQuantityInputs();
});

// Mobile Menu Functions
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', toggleMobileMenu);
    }
}

function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
    
    if (mobileMenu) {
        mobileMenu.classList.toggle('active');
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.classList.toggle('active');
    }
    
    // Toggle body scroll
    if (mobileMenu && mobileMenu.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Categories Menu Functions
function initCategoriesMenu() {
    const categoriesToggle = document.querySelector('.categories-toggle');
    const categoriesDropdown = document.querySelector('.categories-dropdown');
    
    if (categoriesToggle && categoriesDropdown) {
        categoriesToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            categoriesDropdown.classList.toggle('active');
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!categoriesToggle.contains(e.target) && !categoriesDropdown.contains(e.target)) {
                categoriesDropdown.classList.remove('active');
            }
        });
    }
}

// Price Slider
function initPriceSlider() {
    const priceSliders = document.querySelectorAll('.price-slider');
    
    priceSliders.forEach(slider => {
        const minPrice = parseInt(slider.dataset.min) || 0;
        const maxPrice = parseInt(slider.dataset.max) || 10000000;
        const minInput = slider.parentElement.querySelector('input[name="min_price"]');
        const maxInput = slider.parentElement.querySelector('input[name="max_price"]');
        
        if (minInput && maxInput) {
            // Create slider
            noUiSlider.create(slider, {
                start: [parseInt(minInput.value) || minPrice, parseInt(maxInput.value) || maxPrice],
                connect: true,
                step: 1000,
                range: {
                    'min': minPrice,
                    'max': maxPrice
                },
                direction: 'rtl',
                tooltips: [
                    {to: function(v) { return toPersianNumbers(v) + ' تومان'; }},
                    {to: function(v) { return toPersianNumbers(v) + ' تومان'; }}
                ]
            });
            
            // Update inputs on slider change
            slider.noUiSlider.on('update', function(values) {
                minInput.value = toPersianNumbers(Math.round(values[0]));
                maxInput.value = toPersianNumbers(Math.round(values[1]));
            });
            
            // Update slider on input change
            minInput.addEventListener('change', function() {
                const value = parseInt(toEnglishNumbers(this.value)) || minPrice;
                slider.noUiSlider.set([value, null]);
            });
            
            maxInput.addEventListener('change', function() {
                const value = parseInt(toEnglishNumbers(this.value)) || maxPrice;
                slider.noUiSlider.set([null, value]);
            });
        }
    });
}

// Nice Select
function initNiceSelect() {
    if (typeof $.fn.niceSelect !== 'undefined') {
        $('select.select-control').niceSelect();
    }
}

// Slick Sliders
function initSlickSliders() {
    if (typeof $.fn.slick !== 'undefined') {
        // Hero slider
        $('.hero-slider').slick({
            rtl: true,
            dots: true,
            arrows: true,
            infinite: true,
            speed: 500,
            fade: true,
            cssEase: 'linear',
            autoplay: true,
            autoplaySpeed: 5000
        });
        
        // Products slider
        $('.products-slider').slick({
            rtl: true,
            dots: true,
            arrows: true,
            infinite: true,
            speed: 300,
            slidesToShow: 4,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
        
        // Testimonials slider
        $('.testimonials-slider').slick({
            rtl: true,
            dots: true,
            arrows: false,
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
        
        // Brands slider
        $('.brands-slider').slick({
            rtl: true,
            dots: false,
            arrows: false,
            infinite: true,
            speed: 300,
            slidesToShow: 5,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 3000,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 2
                    }
                }
            ]
        });
    }
}

// Testimonial Slider (Custom)
function initTestimonialSlider() {
    const testimonialSliders = document.querySelectorAll('.testimonials-slider');
    
    testimonialSliders.forEach(slider => {
        const track = slider.querySelector('.testimonials-track');
        const dotsContainer = slider.querySelector('.slider-dots');
        const prevBtn = slider.querySelector('.slider-nav .prev');
        const nextBtn = slider.querySelector('.slider-nav .next');
        
        if (!track || !dotsContainer) return;
        
        const items = track.querySelectorAll('.testimonial-card');
        const itemCount = items.length;
        let currentIndex = 0;
        const itemsPerPage = 3;
        const totalPages = Math.ceil(itemCount / itemsPerPage);
        
        // Create dots
        for (let i = 0; i < totalPages; i++) {
            const dot = document.createElement('span');
            dot.className = 'dot' + (i === 0 ? ' active' : '');
            dot.dataset.slide = i;
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
        
        const dots = dotsContainer.querySelectorAll('.dot');
        
        function updateSlider() {
            const offset = -currentIndex * 100;
            track.style.transform = `translateX(${offset}%)`;
            
            // Update dots
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
            
            // Update buttons
            if (prevBtn) prevBtn.style.display = currentIndex === 0 ? 'none' : 'block';
            if (nextBtn) nextBtn.style.display = currentIndex === totalPages - 1 ? 'none' : 'block';
        }
        
        function goToSlide(index) {
            currentIndex = index;
            updateSlider();
        }
        
        function nextSlide() {
            if (currentIndex < totalPages - 1) {
                currentIndex++;
                updateSlider();
            }
        }
        
        function prevSlide() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        }
        
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);
        
        updateSlider();
    });
}

// Back to Top Button
function initBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });
    }
}

// Scroll to Top Function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Scroll Animations
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.fade-in, .slide-up, .slide-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, {
        threshold: 0.1
    });
    
    animatedElements.forEach(el => {
        observer.observe(el);
    });
}

// Quantity Inputs
function initQuantityInputs() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    quantityInputs.forEach(inputGroup => {
        const input = inputGroup.querySelector('input');
        const minusBtn = inputGroup.querySelector('.minus');
        const plusBtn = inputGroup.querySelector('.plus');
        const max = parseInt(input.dataset.max) || 99;
        const min = parseInt(input.dataset.min) || 1;
        
        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(toEnglishNumbers(input.value)) || min;
                value = Math.max(min, value - 1);
                input.value = toPersianNumbers(value);
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(toEnglishNumbers(input.value)) || min;
                value = Math.min(max, value + 1);
                input.value = toPersianNumbers(value);
            });
        }
    });
}

// Utility Functions
function toPersianNumbers(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return String(num).replace(/\d/g, digit => persianDigits[parseInt(digit)]);
}

function toEnglishNumbers(str) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    let result = str;
    for (let i = 0; i < 10; i++) {
        result = result.replace(new RegExp(persianDigits[i], 'g'), i);
    }
    return result;
}

// Format Price
function formatPrice(price) {
    return toPersianNumbers(price) + ' تومان';
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('fa-IR', options);
}

// AJAX Functions
function fetchData(url, options = {}) {
    return fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        ...options
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    });
}

// Cart Functions
function addToCart(productId, quantity = 1, button = null) {
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال اضافه کردن...';
        button.disabled = true;
    }
    
    return fetchData('includes/cart.php?action=add&product_id=' + productId + '&quantity=' + quantity)
    .then(data => {
        if (button) {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> اضافه شد!';
                button.style.backgroundColor = '#4caf50';
                
                // Update cart count in header
                const cartCountEl = document.querySelector('.cart-action .action-count');
                if (cartCountEl) {
                    const currentCount = parseInt(toEnglishNumbers(cartCountEl.textContent)) || 0;
                    cartCountEl.textContent = toPersianNumbers(currentCount + quantity);
                    cartCountEl.style.display = 'inline-block';
                }
                
                // Reset button after delay
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 2000);
            } else {
                button.innerHTML = originalText;
                button.disabled = false;
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'خطا در افزودن به سبد خرید');
                }
            }
        }
        
        return data;
    })
    .catch(error => {
        if (button) {
            button.innerHTML = originalText;
            button.disabled = false;
        }
        alert('خطا در ارتباط با سرور');
        return Promise.reject(error);
    });
}

function updateCartItem(cartId, quantity) {
    return fetchData('includes/cart.php?action=update&cart_id=' + cartId + '&quantity=' + quantity);
}

function removeFromCart(cartId) {
    return fetchData('includes/cart.php?action=remove&cart_id=' + cartId);
}

// Wishlist Functions
function addToWishlist(productId, button = null) {
    if (button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
    }
    
    return fetchData('includes/wishlist.php?action=add&product_id=' + productId)
    .then(data => {
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        
        if (data.success) {
            if (button) {
                button.classList.add('active');
                button.innerHTML = '<i class="fas fa-heart"></i>';
            }
            
            // Update wishlist count in header
            const wishlistCountEl = document.querySelector('.wishlist-action .action-count');
            if (wishlistCountEl) {
                const currentCount = parseInt(toEnglishNumbers(wishlistCountEl.textContent)) || 0;
                wishlistCountEl.textContent = toPersianNumbers(currentCount + 1);
                wishlistCountEl.style.display = 'inline-block';
            }
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'خطا در افزودن به علاقه‌مندی‌ها');
            }
        }
        
        return data;
    })
    .catch(error => {
        if (button) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        alert('خطا در ارتباط با سرور');
        return Promise.reject(error);
    });
}

function removeFromWishlist(productId, button = null) {
    return fetchData('includes/wishlist.php?action=remove&product_id=' + productId)
    .then(data => {
        if (data.success) {
            if (button) {
                button.classList.remove('active');
                button.innerHTML = '<i class="far fa-heart"></i>';
            }
            
            // Update wishlist count in header
            const wishlistCountEl = document.querySelector('.wishlist-action .action-count');
            if (wishlistCountEl) {
                const currentCount = parseInt(toEnglishNumbers(wishlistCountEl.textContent)) || 0;
                const newCount = currentCount - 1;
                wishlistCountEl.textContent = toPersianNumbers(newCount);
                
                if (newCount <= 0) {
                    wishlistCountEl.style.display = 'none';
                }
            }
        }
        
        return data;
    });
}

// Newsletter Subscription
function subscribeToNewsletter(email) {
    return fetchData('includes/newsletter.php', {
        method: 'POST',
        body: 'email=' + encodeURIComponent(email)
    });
}

// Contact Form Submission
function submitContactForm(formData) {
    return fetch('includes/contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json());
}

// Product Quick View
function showQuickView(productId) {
    window.location.href = 'product.php?slug=' + productId;
}

// Image Zoom
function initImageZoom() {
    const productImages = document.querySelectorAll('.product-image-zoom');
    
    productImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

// Countdown Timer
function initCountdownTimers() {
    const countdownElements = document.querySelectorAll('.countdown-timer');
    
    countdownElements.forEach(el => {
        const endDate = new Date(el.dataset.endDate).getTime();
        
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                clearInterval(timer);
                el.innerHTML = 'منقضی شده';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            el.innerHTML = 
                toPersianNumbers(days) + 'd ' +
                toPersianNumbers(hours) + 'h ' +
                toPersianNumbers(minutes) + 'm ' +
                toPersianNumbers(seconds) + 's';
        }, 1000);
    });
}

// Initialize on page load
window.addEventListener('load', function() {
    initImageZoom();
    initCountdownTimers();
});

// Export functions for use in other scripts
window.Goolland = {
    toPersianNumbers,
    toEnglishNumbers,
    formatPrice,
    formatDate,
    addToCart,
    updateCartItem,
    removeFromCart,
    addToWishlist,
    removeFromWishlist,
    subscribeToNewsletter,
    showQuickView,
    scrollToTop
};
