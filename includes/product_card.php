<?php
// Product card component
// Expected variables:
// $product - Product array with keys: id, name, price, image_path, category_name, etc.

if (!isset($product)) {
    return;
}

// Get product rating
$rating = getAverageProductRating($product['id']);
$reviewCount = getProductReviewCount($product['id']);

// Check if product is in wishlist
$isInWishlist = isLoggedIn() ? isInWishlist(getCurrentUserId(), $product['id']) : false;

// Check if product is featured
$isFeatured = isset($product['is_featured']) && $product['is_featured'];

// Check if product is new (created in last 30 days)
$isNew = isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-30 days');

// Check if product is on sale (has discount)
$isOnSale = isset($product['discount_price']) && $product['discount_price'] < $product['price'];
$displayPrice = $isOnSale ? $product['discount_price'] : $product['price'];
$originalPrice = $isOnSale ? $product['price'] : null;

// Get product images
$productImages = getProductImages($product['id']);
$primaryImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');

// Check stock status
$stockStatus = 'in_stock';
$stockText = 'موجود';
$stockClass = 'in-stock';

if (isset($product['stock']) && $product['stock'] <= 0) {
    $stockStatus = 'out_of_stock';
    $stockText = 'ناموجود';
    $stockClass = 'out-of-stock';
} elseif (isset($product['stock']) && $product['stock'] <= 5) {
    $stockStatus = 'low_stock';
    $stockText = 'تنها ' . toPersianNumbers($product['stock']) . ' عدد باقی مانده';
    $stockClass = 'low-stock';
}
?>

<div class="product-card fade-in" data-product-id="<?php echo $product['id']; ?>">
    <div class="product-card-inner">
        <!-- Product Image -->
        <div class="product-image">
            <a href="product.php?id=<?php echo $product['id']; ?>">
                <img src="<?php echo $primaryImage; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="product-main-image">
                
                <?php if (!empty($productImages) && count($productImages) > 1): ?>
                    <img src="<?php echo $productImages[1]['image_path'] ?? $primaryImage; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="product-hover-image">
                <?php endif; ?>
            </a>
            
            <!-- Product Badges -->
            <div class="product-badges">
                <?php if ($isFeatured): ?>
                    <span class="product-badge featured">ویژه</span>
                <?php endif; ?>
                
                <?php if ($isNew): ?>
                    <span class="product-badge new">جدید</span>
                <?php endif; ?>
                
                <?php if ($isOnSale && $originalPrice): ?>
                    <span class="product-badge sale">
                        <?php
                        $discountPercent = round((($originalPrice - $displayPrice) / $originalPrice) * 100);
                        echo toPersianNumbers($discountPercent) . '%';
                        ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($stockStatus === 'out_of_stock'): ?>
                    <span class="product-badge out-of-stock">ناموجود</span>
                <?php endif; ?>
            </div>
            
            <!-- Product Actions -->
            <div class="product-actions">
                <!-- Wishlist Button -->
                <button class="product-action wishlist-action <?php echo $isInWishlist ? 'active' : ''; ?>"
                        onclick="toggleWishlist(<?php echo $product['id']; ?>, this)"
                        title="افزودن به علاقه‌مندی‌ها">
                    <i class="fas fa-heart"></i>
                </button>
                
                <!-- Quick View Button -->
                <button class="product-action quick-view-action"
                        onclick="showQuickView(<?php echo $product['id']; ?>)"
                        title="مشاهده سریع">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="product-info">
            <!-- Category -->
            <?php if (isset($product['category_name']) && $product['category_name']): ?>
                <div class="product-category">
                    <a href="products.php?category=<?php echo $product['category_id'] ?? 0; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Product Name -->
            <h3 class="product-name">
                <a href="product.php?id=<?php echo $product['id']; ?>">
                    <?php echo htmlspecialchars($product['name']); ?>
                </a>
            </h3>
            
            <!-- Product Rating -->
            <div class="product-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?php echo $i <= $rating ? 'filled' : ''; ?>"></i>
                <?php endfor; ?>
                <?php if ($reviewCount > 0): ?>
                    <span class="rating-count">(<?php echo toPersianNumbers($reviewCount); ?>)</span>
                <?php endif; ?>
            </div>
            
            <!-- Product Price -->
            <div class="product-price">
                <?php if ($isOnSale && $originalPrice): ?>
                    <span class="original-price"><?php echo formatPrice($originalPrice); ?></span>
                <?php endif; ?>
                <span class="current-price"><?php echo formatPrice($displayPrice); ?></span>
            </div>
            
            <!-- Stock Status -->
            <div class="product-stock <?php echo $stockClass; ?>">
                <i class="fas fa-<?php echo $stockStatus === 'in_stock' ? 'check-circle' : ($stockStatus === 'low_stock' ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                <span><?php echo $stockText; ?></span>
            </div>
            
            <!-- Add to Cart Button -->
            <button class="add-to-cart btn btn-primary btn-sm <?php echo $stockStatus === 'out_of_stock' ? 'disabled' : ''; ?>"
                    onclick="addToCart(<?php echo $product['id']; ?>, 1, this)"
                    <?php echo $stockStatus === 'out_of_stock' ? 'disabled' : ''; ?>>
                <i class="fas fa-shopping-cart"></i>
                افزودن به سبد
            </button>
        </div>
    </div>
</div>

<!-- Quick View Modal (will be added dynamically) -->
<script>
// Quick view function
function showQuickView(productId) {
    window.location.href = 'product.php?id=' + productId;
}

// Toggle wishlist
function toggleWishlist(productId, button) {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    
    if (!isLoggedIn) {
        alert('برای افزودن به علاقه‌مندی‌ها، ابتدا وارد حساب کاربری شوید');
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }
    
    const icon = button.querySelector('i');
    const isActive = button.classList.contains('active');
    
    if (isActive) {
        // Remove from wishlist
        fetch('includes/wishlist.php?action=remove&product_id=' + productId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.remove('active');
                icon.classList.remove('fas', 'fa-heart');
                icon.classList.add('far', 'fa-heart');
                
                // Update wishlist count in header
                const wishlistCountEl = document.querySelector('.wishlist-action .action-count');
                if (wishlistCountEl) {
                    const currentCount = parseInt(wishlistCountEl.textContent) || 0;
                    wishlistCountEl.textContent = currentCount - 1;
                    
                    if (currentCount - 1 <= 0) {
                        wishlistCountEl.style.display = 'none';
                    }
                }
            }
        });
    } else {
        // Add to wishlist
        fetch('includes/wishlist.php?action=add&product_id=' + productId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.add('active');
                icon.classList.remove('far', 'fa-heart');
                icon.classList.add('fas', 'fa-heart');
                
                // Update wishlist count in header
                const wishlistCountEl = document.querySelector('.wishlist-action .action-count');
                if (wishlistCountEl) {
                    const currentCount = parseInt(wishlistCountEl.textContent) || 0;
                    wishlistCountEl.textContent = currentCount + 1;
                    wishlistCountEl.style.display = 'inline-block';
                }
            }
        });
    }
}

// Add to cart function
function addToCart(productId, quantity, button) {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    
    if (!isLoggedIn) {
        alert('برای افزودن به سبد خرید، ابتدا وارد حساب کاربری شوید');
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }
    
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال اضافه کردن...';
    button.disabled = true;
    
    fetch('includes/cart.php?action=add&product_id=' + productId + '&quantity=' + quantity, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            button.innerHTML = '<i class="fas fa-check"></i> اضافه شد!';
            button.style.backgroundColor = '#4caf50';
            
            // Update cart count in header
            const cartCountEl = document.querySelector('.cart-action .action-count');
            if (cartCountEl) {
                const currentCount = parseInt(cartCountEl.textContent) || 0;
                cartCountEl.textContent = currentCount + quantity;
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
            alert(data.message || 'خطا در افزودن به سبد خرید');
        }
    })
    .catch(error => {
        button.innerHTML = originalText;
        button.disabled = false;
        alert('خطا در ارتباط با سرور');
    });
}
</script>
