<?php
session_start();
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Get user ID
$userId = getCurrentUserId();

// Get wishlist items
$wishlistItems = getWishlistItemsWithDetails($userId);

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $productId = intval($_POST['product_id']);
    
    $result = removeProductFromWishlist($userId, $productId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh wishlist items
        $wishlistItems = getWishlistItemsWithDetails($userId);
    } else {
        $error = $result['message'];
    }
}

// Handle clear wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_wishlist'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            $success = 'لیست علاقه‌مندی‌ها با موفقیت خالی شد.';
            // Refresh wishlist items
            $wishlistItems = getWishlistItemsWithDetails($userId);
        } else {
            $error = 'خطا در خالی کردن لیست علاقه‌مندی‌ها.';
        }
    } catch (PDOException $e) {
        $error = 'خطا: ' . $e->getMessage();
    }
}

// Handle add to cart from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_from_wishlist'])) {
    $productId = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    $result = addProductToCart($userId, $productId, $quantity);
    
    if ($result['success']) {
        $success = $result['message'];
        
        // Optionally remove from wishlist
        if (isset($_POST['remove_after_add'])) {
            removeProductFromWishlist($userId, $productId);
            $wishlistItems = getWishlistItemsWithDetails($userId);
        }
    } else {
        $error = $result['message'];
    }
}

// Get featured products
$featuredProducts = getFeaturedProducts(4);

$pageTitle = 'لیست علاقه‌مندی‌ها';
$pageDescription = 'لیست علاقه‌مندی‌های شما در فروشگاه آنلاین گل و گیاه گولند';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-heart"></i> لیست علاقه‌مندی‌ها</h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <span>لیست علاقه‌مندی‌ها</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="wishlist-page">
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
        
        <?php if (empty($wishlistItems)): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart"></i>
                <h3>لیست علاقه‌مندی‌ها خالی است</h3>
                <p>شما هنوز هیچ محصولی به لیست علاقه‌مندی‌های خود اضافه نکرده‌اید.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    کشف محصولات
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-container">
                <!-- Wishlist Header -->
                <div class="wishlist-header">
                    <h2><i class="fas fa-heart"></i> محصولات مورد علاقه</h2>
                    <form method="POST" action="wishlist.php">
                        <button type="submit" name="clear_wishlist" class="btn btn-secondary btn-sm" onclick="return confirm('آیا از خالی کردن لیست علاقه‌مندی‌ها مطمئن هستید؟')">
                            <i class="fas fa-trash"></i>
                            خالی کردن لیست
                        </button>
                    </form>
                </div>
                
                <!-- Wishlist Items -->
                <div class="wishlist-items">
                    <?php foreach ($wishlistItems as $item): ?>
                        <?php
                        $product = getProductById($item['product_id']);
                        if (!$product) continue;
                        
                        $productImages = getProductImages($item['product_id']);
                        $primaryImage = !empty($productImages) ? $productImages[0]['image_path'] : ($product['image_path'] ?: 'assets/images/no-image.jpg');
                        
                        // Get product rating
                        $rating = getAverageProductRating($product['id']);
                        $reviewCount = getProductReviewCount($product['id']);
                        
                        // Check stock status
                        $stockStatus = 'in_stock';
                        $stockText = 'موجود';
                        $stockClass = 'in-stock';
                        
                        if ($product['stock'] <= 0) {
                            $stockStatus = 'out_of_stock';
                            $stockText = 'ناموجود';
                            $stockClass = 'out-of-stock';
                        } elseif ($product['stock'] <= 5) {
                            $stockStatus = 'low_stock';
                            $stockText = 'تنها ' . toPersianNumbers($product['stock']) . ' عدد باقی مانده';
                            $stockClass = 'low-stock';
                        }
                        
                        // Check if product is featured
                        $isFeatured = $product['is_featured'] ?? false;
                        $isNew = isset($product['created_at']) && strtotime($product['created_at']) > strtotime('-30 days');
                        $isOnSale = isset($product['discount_price']) && $product['discount_price'] < $product['price'];
                        $displayPrice = $isOnSale ? $product['discount_price'] : $product['price'];
                        $originalPrice = $isOnSale ? $product['price'] : null;
                        
                        // Check if product is in cart
                        $isInCart = false;
                        try {
                            $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                            $stmt->execute([$userId, $product['id']]);
                            $isInCart = $stmt->fetchColumn() !== false;
                        } catch (PDOException $e) {
                            $isInCart = false;
                        }
                        ?>
                        
                        <div class="wishlist-item" data-product-id="<?php echo $product['id']; ?>">
                            <div class="wishlist-item-product">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <div class="wishlist-item-image">
                                        <img src="<?php echo $primaryImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                    <div class="wishlist-item-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="wishlist-item-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $rating ? 'filled' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span>(<?php echo toPersianNumbers($reviewCount); ?>)</span>
                                        </div>
                                        
                                        <div class="wishlist-item-features">
                                            <?php if ($isFeatured): ?>
                                                <span class="feature-badge featured">ویژه</span>
                                            <?php endif; ?>
                                            <?php if ($isNew): ?>
                                                <span class="feature-badge new">جدید</span>
                                            <?php endif; ?>
                                            <?php if ($isOnSale && $originalPrice): ?>
                                                <span class="feature-badge sale">
                                                    <?php
                                                    $discountPercent = round((($originalPrice - $displayPrice) / $originalPrice) * 100);
                                                    echo toPersianNumbers($discountPercent) . '%';
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="wishlist-item-stock <?php echo $stockClass; ?>">
                                            <i class="fas fa-<?php echo $stockStatus === 'in_stock' ? 'check-circle' : ($stockStatus === 'low_stock' ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                                            <span><?php echo $stockText; ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="wishlist-item-price">
                                <?php if ($isOnSale && $originalPrice): ?>
                                    <span class="original-price"><?php echo formatPrice($originalPrice); ?></span>
                                <?php endif; ?>
                                <span class="current-price"><?php echo formatPrice($displayPrice); ?></span>
                            </div>
                            
                            <div class="wishlist-item-actions">
                                <form method="POST" action="wishlist.php">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" 
                                            class="btn btn-sm btn-danger" 
                                            title="حذف از علاقه‌مندی‌ها">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                
                                <?php if ($stockStatus !== 'out_of_stock'): ?>
                                    <form method="POST" action="wishlist.php" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="remove_after_add" value="1">
                                        <button type="submit" name="add_to_cart_from_wishlist" 
                                                class="btn btn-sm btn-primary <?php echo $isInCart ? 'in-cart' : ''; ?>"
                                                title="افزودن به سبد خرید">
                                            <?php if ($isInCart): ?>
                                                <i class="fas fa-check"></i>
                                                در سبد خرید
                                            <?php else: ?>
                                                <i class="fas fa-shopping-cart"></i>
                                                افزودن به سبد
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($featuredProducts)): ?>
    <section class="related-products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-gift"></i> محصولات ویژه</h2>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Wishlist page specific scripts

// Toggle wishlist item
function toggleWishlist(productId, button) {
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
                button.closest('.wishlist-item').remove();
                
                // Update wishlist count in header
                const wishlistCountEl = document.querySelector('.wishlist-action .action-count');
                if (wishlistCountEl) {
                    const currentCount = parseInt(wishlistCountEl.textContent) || 0;
                    wishlistCountEl.textContent = currentCount - 1;
                    
                    if (currentCount - 1 <= 0) {
                        wishlistCountEl.style.display = 'none';
                    }
                }
                
                // Show success message
                alert(data.message);
            }
        });
    }
}

// Add to cart from wishlist
function addToCartFromWishlist(productId, quantity, removeAfterAdd) {
    fetch('includes/cart.php?action=add&product_id=' + productId + '&quantity=' + quantity, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCountEl = document.querySelector('.cart-action .action-count');
            if (cartCountEl) {
                const currentCount = parseInt(cartCountEl.textContent) || 0;
                cartCountEl.textContent = currentCount + quantity;
                cartCountEl.style.display = 'inline-block';
            }
            
            // If remove after add, remove from wishlist
            if (removeAfterAdd) {
                fetch('includes/wishlist.php?action=remove&product_id=' + productId, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.wishlist-item[data-product-id="' + productId + '"]').remove();
                        
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
            }
            
            alert(data.message);
        } else {
            alert(data.message);
        }
    });
}

// Initialize wishlist actions
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for add to cart buttons
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const productId = this.querySelector('input[name="product_id"]').value;
            const quantity = parseInt(this.querySelector('input[name="quantity"]').value) || 1;
            const removeAfterAdd = this.querySelector('input[name="remove_after_add"]').value === '1';
            
            addToCartFromWishlist(productId, quantity, removeAfterAdd);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
