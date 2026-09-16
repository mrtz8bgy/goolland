/**
 * Goolland - Custom JavaScript Functions
 * Luxury Flower & Plant Shop
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize custom functions
    initSearchAutocomplete();
    initProductTabs();
    initProductGallery();
    initCartPage();
    initCheckoutPage();
    initProfilePage();
    initWishlistPage();
    initOrdersPage();
    initMobileNavigation();
});

// Search Autocomplete
function initSearchAutocomplete() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchResults = document.querySelector('.search-results');
    
    if (!searchInput || !searchResults) return;
    
    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        
        const query = this.value.trim();
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetchSearchSuggestions(query);
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

function fetchSearchSuggestions(query) {
    const searchResults = document.querySelector('.search-results');
    
    fetch('includes/search.php?action=suggest&q=' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {
        if (data.success && data.results && data.results.length > 0) {
            displaySearchSuggestions(data.results);
        } else {
            searchResults.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error fetching search suggestions:', error);
    });
}

function displaySearchSuggestions(results) {
    const searchResults = document.querySelector('.search-results');
    searchResults.innerHTML = '';
    
    results.forEach(result => {
        const item = document.createElement('a');
        item.href = 'product.php?slug=' + result.slug;
        item.className = 'search-result-item';
        item.innerHTML = `
            <div class="search-result-image">
                <img src="assets/images/products/${result.image}" alt="${result.name}" loading="lazy">
            </div>
            <div class="search-result-info">
                <h4>${result.name}</h4>
                <p>${formatPrice(result.price)}</p>
            </div>
        `;
        searchResults.appendChild(item);
    });
    
    searchResults.style.display = 'block';
}

// Product Tabs
function initProductTabs() {
    const tabContainers = document.querySelectorAll('.product-tabs');
    
    tabContainers.forEach(container => {
        const tabButtons = container.querySelectorAll('.tab-button');
        const tabContents = container.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.dataset.tab;
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                container.querySelector(`.tab-content[data-tab="${tabId}"]`).classList.add('active');
            });
        });
    });
}

// Product Gallery
function initProductGallery() {
    const galleries = document.querySelectorAll('.product-gallery');
    
    galleries.forEach(gallery => {
        const mainImage = gallery.querySelector('.main-image img');
        const thumbnailImages = gallery.querySelectorAll('.thumbnail-image');
        
        if (!mainImage) return;
        
        thumbnailImages.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                const newSrc = thumbnail.dataset.fullImage || thumbnail.querySelector('img').src;
                mainImage.src = newSrc;
                
                // Remove active class from all thumbnails
                thumbnailImages.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked thumbnail
                thumbnail.classList.add('active');
            });
        });
        
        // Initialize first thumbnail as active
        if (thumbnailImages.length > 0) {
            thumbnailImages[0].classList.add('active');
        }
    });
}

// Cart Page Functions
function initCartPage() {
    const cartTable = document.querySelector('.cart-table');
    if (!cartTable) return;
    
    // Quantity change handlers
    const quantityInputs = cartTable.querySelectorAll('.quantity-input input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const cartId = this.dataset.cartId;
            const quantity = parseInt(toEnglishNumbers(this.value)) || 1;
            
            updateCartItem(cartId, quantity).then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });
    
    // Remove item handlers
    const removeButtons = cartTable.querySelectorAll('.remove-item');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cartId = this.dataset.cartId;
            
            if (confirm('آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟')) {
                removeFromCart(cartId).then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        });
    });
}

// Checkout Page Functions
function initCheckoutPage() {
    const checkoutForm = document.querySelector('#checkout-form');
    if (!checkoutForm) return;
    
    // Location selectors
    const stateSelect = checkoutForm.querySelector('select[name="state"]');
    const citySelect = checkoutForm.querySelector('select[name="city"]');
    
    if (stateSelect && citySelect) {
        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            if (stateId) {
                fetchCities(stateId);
            } else {
                citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
            }
        });
    }
    
    // Same as billing address checkbox
    const sameAsBilling = checkoutForm.querySelector('#same_as_billing');
    if (sameAsBilling) {
        sameAsBilling.addEventListener('change', function() {
            toggleShippingAddress(this.checked);
        });
    }
    
    // Form validation
    checkoutForm.addEventListener('submit', function(e) {
        if (!validateCheckoutForm()) {
            e.preventDefault();
        }
    });
}

function fetchCities(stateId) {
    const citySelect = document.querySelector('#checkout-form select[name="city"]');
    if (!citySelect) return;
    
    fetch('includes/locations.php?action=get_cities&state_id=' + stateId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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

function toggleShippingAddress(checked) {
    const shippingFields = document.querySelectorAll('.shipping-field');
    shippingFields.forEach(field => {
        if (checked) {
            field.style.display = 'none';
        } else {
            field.style.display = '';
        }
    });
}

function validateCheckoutForm() {
    const form = document.querySelector('#checkout-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#f44336';
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Profile Page Functions
function initProfilePage() {
    const profileForm = document.querySelector('#profile-form');
    if (!profileForm) return;
    
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';
        submitBtn.disabled = true;
        
        fetch('includes/profile.php?action=update', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('اطلاعات شما با موفقیت به‌روزرسانی شد.');
                location.reload();
            } else {
                alert(data.message || 'خطا در به‌روزرسانی اطلاعات');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            alert('خطا در ارتباط با سرور');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
}

// Wishlist Page Functions
function initWishlistPage() {
    const wishlistItems = document.querySelectorAll('.wishlist-item');
    
    wishlistItems.forEach(item => {
        const removeBtn = item.querySelector('.remove-wishlist');
        const addToCartBtn = item.querySelector('.add-to-cart-wishlist');
        
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                
                if (confirm('آیا مطمئن هستید که می‌خواهید این محصول را از لیست علاقه‌مندی‌ها حذف کنید؟')) {
                    removeFromWishlist(productId).then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
                }
            });
        }
        
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                addToCart(productId, 1, this);
            });
        }
    });
    
    // Clear wishlist button
    const clearWishlistBtn = document.querySelector('.clear-wishlist');
    if (clearWishlistBtn) {
        clearWishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('آیا مطمئن هستید که می‌خواهید تمام محصولات را از لیست علاقه‌مندی‌ها حذف کنید؟')) {
                fetch('includes/wishlist.php?action=clear')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        });
    }
}

// Orders Page Functions
function initOrdersPage() {
    const orderItems = document.querySelectorAll('.order-item');
    
    orderItems.forEach(item => {
        const viewDetailsBtn = item.querySelector('.view-details');
        
        if (viewDetailsBtn) {
            viewDetailsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.dataset.orderId;
                window.location.href = 'order-details.php?id=' + orderId;
            });
        }
    });
}

// Mobile Navigation
function initMobileNavigation() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const mobileNav = document.querySelector('.mobile-nav');
    
    if (mobileNavToggle && mobileNav) {
        mobileNavToggle.addEventListener('click', function() {
            mobileNav.classList.toggle('active');
        });
    }
}

// Utility Functions
function formatPrice(price) {
    return toPersianNumbers(price) + ' تومان';
}

// Initialize on page load
window.addEventListener('load', function() {
    // Additional initialization if needed
});
