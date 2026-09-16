/**
 * Goolland - Custom JavaScript
 * Custom scripts and extensions
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize custom functionality
    initProductPage();
    initCartPage();
    initCheckoutPage();
    initProfilePage();
    initBlogPage();
    initSearchPage();
    initCustomScripts();
});

// Product Page Functions
function initProductPage() {
    const productPage = document.querySelector('.product-page');
    if (!productPage) return;
    
    // Product image gallery
    initProductGallery();
    
    // Product quantity selector
    initProductQuantity();
    
    // Product tabs
    initProductTabs();
    
    // Review form
    initReviewForm();
    
    // Related products slider
    initRelatedProductsSlider();
}

// Product Image Gallery
function initProductGallery() {
    const gallery = document.querySelector('.product-gallery');
    if (!gallery) return;
    
    const mainImage = gallery.querySelector('.product-main-image img');
    const thumbnailImages = gallery.querySelectorAll('.product-thumbnail img');
    
    thumbnailImages.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const src = this.getAttribute('src');
            const alt = this.getAttribute('alt');
            
            if (mainImage) {
                mainImage.setAttribute('src', src);
                mainImage.setAttribute('alt', alt);
            }
            
            // Remove active class from all thumbnails
            thumbnailImages.forEach(t => t.parentElement.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.parentElement.classList.add('active');
        });
    });
    
    // Image zoom
    if (mainImage) {
        const zoomBtn = document.createElement('button');
        zoomBtn.className = 'product-zoom-btn';
        zoomBtn.innerHTML = '<i class="fas fa-search-plus"></i>';
        zoomBtn.title = 'بزرگنمایی تصویر';
        
        mainImage.parentElement.appendChild(zoomBtn);
        
        zoomBtn.addEventListener('click', function() {
            openImageModal(mainImage.getAttribute('src'), mainImage.getAttribute('alt'));
        });
    }
}

// Open image modal
function openImageModal(src, alt) {
    const modal = document.createElement('div');
    modal.className = 'image-modal-overlay';
    modal.innerHTML = `
        <div class="image-modal">
            <button class="modal-close" onclick="this.closest('.image-modal-overlay').remove()">
                <i class="fas fa-times"></i>
            </button>
            <img src="${src}" alt="${alt}">
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Product Quantity Selector
function initProductQuantity() {
    const quantityInput = document.querySelector('.product-quantity-input');
    if (!quantityInput) return;
    
    const input = quantityInput.querySelector('input');
    const minusBtn = quantityInput.querySelector('.minus');
    const plusBtn = quantityInput.querySelector('.plus');
    const maxStock = parseInt(input.dataset.maxStock) || 99;
    
    if (minusBtn && plusBtn && input) {
        minusBtn.addEventListener('click', function() {
            let value = parseInt(toEnglishNumbers(input.value)) || 1;
            value = Math.max(1, value - 1);
            input.value = toPersianNumbers(value);
        });
        
        plusBtn.addEventListener('click', function() {
            let value = parseInt(toEnglishNumbers(input.value)) || 1;
            value = Math.min(maxStock, value + 1);
            input.value = toPersianNumbers(value);
            
            if (value >= maxStock) {
                alert('حداکثر موجودی ' + toPersianNumbers(maxStock) + ' عدد است.');
            }
        });
    }
}

// Product Tabs
function initProductTabs() {
    const tabs = document.querySelectorAll('.product-tab-button');
    const tabContents = document.querySelectorAll('.product-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

// Review Form
function initReviewForm() {
    const reviewForm = document.querySelector('.review-form');
    if (!reviewForm) return;
    
    const ratingStars = reviewForm.querySelectorAll('.rating-star');
    const ratingInput = reviewForm.querySelector('input[name="rating"]');
    
    ratingStars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            
            // Update stars
            ratingStars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('filled');
                } else {
                    s.classList.remove('filled');
                }
            });
            
            // Update input
            if (ratingInput) {
                ratingInput.value = rating;
            }
        });
    });
}

// Related Products Slider
function initRelatedProductsSlider() {
    const slider = document.querySelector('.related-products-slider');
    if (!slider) return;
    
    if (typeof $.fn.slick !== 'undefined') {
        $(slider).slick({
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
    }
}

// Cart Page Functions
function initCartPage() {
    const cartPage = document.querySelector('.cart-page');
    if (!cartPage) return;
    
    // Quantity inputs
    initCartQuantityInputs();
    
    // Remove item buttons
    initRemoveItemButtons();
    
    // Clear cart button
    initClearCartButton();
    
    // Coupon form
    initCouponForm();
}

// Cart Quantity Inputs
function initCartQuantityInputs() {
    const quantityForms = document.querySelectorAll('.quantity-form');
    
    quantityForms.forEach(form => {
        const input = form.querySelector('input[name="quantity"]');
        const minusBtn = form.querySelector('.minus');
        const plusBtn = form.querySelector('.plus');
        const maxStock = parseInt(input.dataset.max) || 99;
        
        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(toEnglishNumbers(input.value)) || 1;
                value = Math.max(1, value - 1);
                input.value = toPersianNumbers(value);
                form.querySelector('button[type="submit"]').click();
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(toEnglishNumbers(input.value)) || 1;
                value = Math.min(maxStock, value + 1);
                input.value = toPersianNumbers(value);
                form.querySelector('button[type="submit"]').click();
            });
        }
    });
}

// Remove Item Buttons
function initRemoveItemButtons() {
    const removeButtons = document.querySelectorAll('.remove-btn');
    
    removeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('آیا از حذف این محصول از سبد خرید مطمئن هستید؟')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
}

// Clear Cart Button
function initClearCartButton() {
    const clearBtn = document.querySelector('button[name="clear_cart"]');
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            if (!confirm('آیا از خالی کردن سبد خرید مطمئن هستید؟')) {
                e.preventDefault();
                return false;
            }
            return true;
        });
    }
}

// Coupon Form
function initCouponForm() {
    const couponForm = document.querySelector('.coupon-form');
    if (!couponForm) return;
    
    const couponInput = couponForm.querySelector('input[name="coupon_code"]');
    const applyBtn = couponForm.querySelector('button[name="apply_coupon"]');
    
    if (couponInput && applyBtn) {
        couponForm.addEventListener('submit', function(e) {
            const couponCode = couponInput.value.trim();
            
            if (couponCode.length < 3) {
                e.preventDefault();
                alert('کد تخفیف باید حداقل 3 کاراکتر باشد.');
                return false;
            }
            
            return true;
        });
    }
}

// Checkout Page Functions
function initCheckoutPage() {
    const checkoutPage = document.querySelector('.checkout-page');
    if (!checkoutPage) return;
    
    // Shipping method selection
    initShippingMethod();
    
    // Payment method selection
    initPaymentMethod();
    
    // Form validation
    initCheckoutValidation();
    
    // Address selection
    initAddressSelection();
}

// Shipping Method
function initShippingMethod() {
    const shippingMethods = document.querySelectorAll('input[name="shipping_method"]');
    
    shippingMethods.forEach(radio => {
        radio.addEventListener('change', function() {
            const price = parseInt(this.dataset.price) || 0;
            updateShippingCost(price);
        });
    });
}

// Update Shipping Cost
function updateShippingCost(price) {
    const shippingCostEl = document.getElementById('shipping_cost_display');
    const finalTotalEl = document.getElementById('final_total_display');
    
    if (!shippingCostEl || !finalTotalEl) return;
    
    const subtotal = parseInt(toEnglishNumbers(shippingCostEl.dataset.subtotal)) || 0;
    const discount = parseInt(toEnglishNumbers(shippingCostEl.dataset.discount)) || 0;
    const oldShipping = parseInt(toEnglishNumbers(shippingCostEl.dataset.oldShipping)) || 0;
    
    const newTotal = subtotal + price - oldShipping - discount;
    
    shippingCostEl.textContent = price > 0 ? formatPrice(price) : 'رایگان';
    finalTotalEl.textContent = formatPrice(newTotal);
    
    // Update data attributes
    shippingCostEl.dataset.oldShipping = price;
}

// Payment Method
function initPaymentMethod() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    paymentMethods.forEach(radio => {
        radio.addEventListener('change', function() {
            // Show/hide payment info based on method
            const method = this.value;
            
            // Hide all payment info sections
            document.querySelectorAll('.payment-info-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected payment info
            const paymentInfo = document.getElementById('payment-info-' + method);
            if (paymentInfo) {
                paymentInfo.style.display = 'block';
            }
        });
    });
}

// Checkout Validation
function initCheckoutValidation() {
    const checkoutForm = document.querySelector('.checkout-form');
    if (!checkoutForm) return;
    
    checkoutForm.addEventListener('submit', function(e) {
        // Validate required fields
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = '#f44336';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('لطفا فیلدهای مورد نیاز را پر کنید.');
            return false;
        }
        
        return true;
    });
}

// Address Selection
function initAddressSelection() {
    const addressRadios = document.querySelectorAll('input[name="shipping_address_id"]');
    
    addressRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const addressId = this.value;
            const address = this.dataset.address;
            
            // Fill address fields
            if (address) {
                const addressData = JSON.parse(address);
                document.getElementById('shipping_first_name').value = addressData.first_name || '';
                document.getElementById('shipping_last_name').value = addressData.last_name || '';
                document.getElementById('shipping_phone').value = addressData.phone || '';
                document.getElementById('shipping_address').value = addressData.address || '';
                document.getElementById('shipping_postal_code').value = addressData.postal_code || '';
                document.getElementById('shipping_province_id').value = addressData.province_id || '';
                
                // Load cities for the selected province
                const provinceId = addressData.province_id;
                if (provinceId) {
                    loadCities(provinceId, 'shipping_city_id');
                    setTimeout(() => {
                        document.getElementById('shipping_city_id').value = addressData.city_id || '';
                    }, 500);
                }
            }
        });
    });
}

// Load Cities
function loadCities(provinceId, citySelectId) {
    fetch('includes/locations.php?action=get_cities&province_id=' + provinceId)
    .then(response => response.json())
    .then(data => {
        const citySelect = document.getElementById(citySelectId);
        if (citySelect) {
            citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
            
            data.cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
        }
    });
}

// Profile Page Functions
function initProfilePage() {
    const profilePage = document.querySelector('.profile-page');
    if (!profilePage) return;
    
    // Tab switching
    initProfileTabs();
    
    // Address management
    initAddressManagement();
    
    // Form validation
    initProfileValidation();
}

// Profile Tabs
function initProfileTabs() {
    const tabs = document.querySelectorAll('.profile-nav a');
    const tabContents = document.querySelectorAll('.profile-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('href').substring(1);
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.parentElement.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.parentElement.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });
}

// Address Management
function initAddressManagement() {
    // Add address form
    const addAddressForm = document.querySelector('.add-address-form');
    if (addAddressForm) {
        addAddressForm.addEventListener('submit', function(e) {
            // Validate form
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#f44336';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('لطفا فیلدهای مورد نیاز را پر کنید.');
                return false;
            }
            
            return true;
        });
    }
}

// Profile Validation
function initProfileValidation() {
    const profileForms = document.querySelectorAll('.profile-form');
    
    profileForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#f44336';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('لطفا فیلدهای مورد نیاز را پر کنید.');
                return false;
            }
            
            return true;
        });
    });
}

// Blog Page Functions
function initBlogPage() {
    const blogPage = document.querySelector('.blog-page');
    if (!blogPage) return;
    
    // Comment form
    initCommentForm();
    
    // Reply form
    initReplyForm();
    
    // Social sharing
    initSocialSharing();
}

// Comment Form
function initCommentForm() {
    const commentForm = document.querySelector('.comment-form');
    if (!commentForm) return;
    
    commentForm.addEventListener('submit', function(e) {
        const commentTextarea = this.querySelector('textarea[name="comment"]');
        
        if (!commentTextarea.value.trim()) {
            e.preventDefault();
            alert('لطفا نظر خود را وارد کنید.');
            return false;
        }
        
        return true;
    });
}

// Reply Form
function initReplyForm() {
    const replyButtons = document.querySelectorAll('.reply-btn');
    
    replyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById('reply-form-' + commentId);
            
            if (replyForm) {
                replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
            }
        });
    });
}

// Social Sharing
function initSocialSharing() {
    const shareButtons = document.querySelectorAll('.share-btn');
    
    shareButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url || window.location.href;
            const title = this.dataset.title || document.title;
            
            let shareUrl = '';
            
            if (this.classList.contains('facebook')) {
                shareUrl = 'https://www.facebook.com/sharer.php?u=' + encodeURIComponent(url);
            } else if (this.classList.contains('twitter')) {
                shareUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title);
            } else if (this.classList.contains('whatsapp')) {
                shareUrl = 'https://wa.me/?text=' + encodeURIComponent(title + ' - ' + url);
            } else if (this.classList.contains('telegram')) {
                shareUrl = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(title);
            } else if (this.classList.contains('copy')) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('لینک با موفقیت کپی شد.');
                });
                return;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        });
    });
}

// Search Page Functions
function initSearchPage() {
    const searchPage = document.querySelector('.search-page');
    if (!searchPage) return;
    
    // Filter toggles
    initFilterToggles();
    
    // Price range slider
    initSearchPriceSlider();
}

// Filter Toggles
function initFilterToggles() {
    const filterToggles = document.querySelectorAll('.filter-toggle');
    
    filterToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const filterContent = this.nextElementSibling;
            filterContent.style.display = filterContent.style.display === 'none' ? 'block' : 'none';
        });
    });
}

// Search Price Slider
function initSearchPriceSlider() {
    const priceSlider = document.querySelector('.search-price-slider');
    if (!priceSlider) return;
    
    const minInput = document.getElementById('min_price');
    const maxInput = document.getElementById('max_price');
    const minPrice = parseInt(priceSlider.dataset.min) || 0;
    const maxPrice = parseInt(priceSlider.dataset.max) || 10000000;
    
    if (minInput && maxInput) {
        noUiSlider.create(priceSlider, {
            start: [parseInt(toEnglishNumbers(minInput.value)) || minPrice, parseInt(toEnglishNumbers(maxInput.value)) || maxPrice],
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
        
        priceSlider.noUiSlider.on('update', function(values) {
            minInput.value = toPersianNumbers(Math.round(values[0]));
            maxInput.value = toPersianNumbers(Math.round(values[1]));
        });
    }
}

// Custom Scripts
function initCustomScripts() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animate numbers on scroll
    initAnimateNumbers();
    
    // Lazy loading for images
    initLazyLoading();
}

// Animate Numbers
function initAnimateNumbers() {
    const animatedNumbers = document.querySelectorAll('.animate-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const endValue = parseInt(target.dataset.value) || 0;
                const duration = parseInt(target.dataset.duration) || 2000;
                
                animateValue(target, 0, endValue, duration);
                observer.unobserve(target);
            }
        });
    }, { threshold: 0.5 });
    
    animatedNumbers.forEach(num => observer.observe(num));
}

// Animate Value
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        
        element.textContent = toPersianNumbers(value);
        
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    
    window.requestAnimationFrame(step);
}

// Lazy Loading
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.setAttribute('src', img.dataset.src);
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }
}

// Export functions
window.GoollandCustom = {
    initProductGallery,
    initProductQuantity,
    initProductTabs,
    initReviewForm,
    initCartQuantityInputs,
    updateShippingCost,
    initCheckoutValidation,
    loadCities,
    initProfileTabs,
    initAddressManagement,
    initCommentForm,
    initReplyForm,
    initSocialSharing,
    animateValue,
    initLazyLoading
};
