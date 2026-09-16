<?php
session_start();
require_once 'includes/db.php';

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    header("Location: products.php");
    exit;
}

// Get product details
$product = getProductById($productId);

if (!$product) {
    header("Location: 404.php");
    exit;
}

// Increment view count
try {
    $stmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$productId]);
} catch (PDOException $e) {
    // Ignore error
}

// Get product images
$productImages = getProductImages($productId);

// Get product category
$category = getCategoryById($product['category_id']);

// Get product reviews
$reviews = getProductReviews($productId, null, true, 6);

// Get average rating
$averageRating = getAverageProductRating($productId);
$reviewCount = getProductReviewCount($productId);

// Get related products
$relatedProducts = getRelatedProducts($productId, $product['category_id'], 4);

// Get product variations (if any)
$stmt = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ?");
$stmt->execute([$productId]);
$variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product attributes
$stmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id = ?");
$stmt->execute([$productId]);
$attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured products
$featuredProducts = getFeaturedProducts(4);

// Check if product is in wishlist
$isInWishlist = isLoggedIn() ? isInWishlist(getCurrentUserId(), $productId) : false;

// Get user rating (if logged in)
$userRating = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT rating FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([getCurrentUserId(), $productId]);
    $userRating = $stmt->fetchColumn();
}

$pageTitle = htmlspecialchars($product['name']) . ' | ' . htmlspecialchars(getSetting('site_name', 'گولند'));
$pageDescription = htmlspecialchars(substr($product['description'], 0, 200));
require_once 'includes/header.php';

// Add to recently viewed products
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

if (!in_array($productId, $_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'][] = $productId;
    if (count($_SESSION['recently_viewed']) > 10) {
        array_shift($_SESSION['recently_viewed']);
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <?php if ($category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a>
                <i class="fas fa-chevron-left"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>
    </div>
</div>

<!-- Product Details -->
<div class="product-details">
    <div class="container">
        <div class="product-gallery">
            <!-- Main Image -->
            <div class="main-image">
                <?php if (!empty($productImages)): ?>
                    <img src="<?php echo $productImages[0]['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                <?php elseif (!empty($product['image_path'])): ?>
                    <img src="<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                <?php else: ?>
                    <img src="assets/images/no-image.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                <?php endif; ?>
                
                <!-- Product Badges -->
                <div class="product-badges">
                    <?php if ($product['is_featured']): ?>
                        <span class="badge featured">ویژه</span>
                    <?php endif; ?>
                    <?php if ($product['stock'] <= 0): ?>
                        <span class="badge out-of-stock">ناموجود</span>
                    <?php else: ?>
                        <span class="badge in-stock">موجود</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Thumbnail Gallery -->
            <div class="thumbnail-gallery">
                <?php if (!empty($productImages)): ?>
                    <?php foreach ($productImages as $index => $image): ?>
                        <img src="<?php echo $image['image_path']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?> - تصویر <?php echo toPersianNumbers($index + 1); ?>"
                             class="<?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeMainImage(this.src)">
                    <?php endforeach; ?>
                <?php elseif (!empty($product['image_path'])): ?>
                    <img src="<?php echo $product['image_path']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="active"
                         onclick="changeMainImage(this.src)">
                <?php endif; ?>
            </div>
            
            <!-- Share Buttons -->
            <div class="share-buttons">
                <span>اشتراک‌گذاری:</span>
                <a href="https://telegram.me/share/url?url=<?php echo urlencode(getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId); ?>" target="_blank" title="تلگرام">
                    <i class="fab fa-telegram"></i>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode(getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId); ?>" target="_blank" title="واتساپ">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="#" onclick="copyToClipboard('<?php echo getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId; ?>')" title="کپی لینک">
                    <i class="fas fa-link"></i>
                </a>
            </div>
        </div>
        
        <div class="product-info">
            <!-- Product Category -->
            <div class="product-category">
                <?php if ($category): ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php else: ?>
                    <span>بدون دسته‌بندی</span>
                <?php endif; ?>
            </div>
            
            <!-- Product Title -->
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <!-- Product Rating -->
            <div class="product-rating">
                <div class="rating-stars">
                    <?php 
                    $fullStars = floor($averageRating);
                    $halfStar = $averageRating - $fullStars >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    
                    for ($i = 0; $i < $fullStars; $i++):
                        echo '<i class="fas fa-star"></i>';
                    endfor;
                    
                    if ($halfStar):
                        echo '<i class="fas fa-star-half-alt"></i>';
                    endif;
                    
                    for ($i = 0; $i < $emptyStars; $i++):
                        echo '<i class="far fa-star"></i>';
                    endfor;
                    ?>
                </div>
                <span class="rating-count">
                    <a href="#reviews"><?php echo toPersianNumbers($reviewCount); ?> نظر</a>
                </span>
            </div>
            
            <!-- Product Price -->
            <div class="product-price">
                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                    <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                <?php endif; ?>
                <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                <?php if ($product['discount'] && $product['discount'] > 0): ?>
                    <span class="discount"><?php echo toPersianNumbers($product['discount']); ?>% تخفیف</span>
                <?php endif; ?>
            </div>
            
            <!-- Product Description -->
            <div class="product-description">
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
            
            <!-- Product Meta -->
            <div class="product-meta">
                <div class="meta-item">
                    <span class="meta-label">شناسه محصول:</span>
                    <span class="meta-value"><?php echo toPersianNumbers($product['sku'] ?: $product['id']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">دسته‌بندی:</span>
                    <span class="meta-value">
                        <?php if ($category): ?>
                            <a href="products.php?category=<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php else: ?>
                            بدون دسته‌بندی
                        <?php endif; ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">موجودی:</span>
                    <span class="meta-value">
                        <?php echo toPersianNumbers($product['stock']); ?> عدد
                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                            <span class="low-stock">(موجودی کم)</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">بازدید:</span>
                    <span class="meta-value"><?php echo toPersianNumbers($product['view_count']); ?></span>
                </div>
            </div>
            
            <!-- Product Attributes -->
            <?php if (!empty($attributes)): ?>
                <div class="product-attributes">
                    <h3>ویژگی‌ها</h3>
                    <table>
                        <tbody>
                            <?php foreach ($attributes as $attr): ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($attr['name']); ?>:</th>
                                    <td><?php echo htmlspecialchars($attr['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity-selector">
                    <button class="minus" onclick="decreaseQuantity()">-</button>
                    <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1">
                    <button class="plus" onclick="increaseQuantity()">+</button>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <button class="btn btn-primary btn-lg add-to-cart-btn" 
                            data-product-id="<?php echo $product['id']; ?>"
                            onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-shopping-cart"></i>
                        افزودن به سبد خرید
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="fas fa-times-circle"></i>
                        ناموجود
                    </button>
                <?php endif; ?>
                
                <button class="wishlist-btn <?php echo $isInWishlist ? 'active' : ''; ?>" 
                        data-product-id="<?php echo $product['id']; ?>"
                        onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                    <i class="fas fa-heart"></i>
                </button>
                
                <button class="compare-btn" data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
            
            <!-- Delivery Info -->
            <div class="delivery-info">
                <div class="delivery-item">
                    <i class="fas fa-truck"></i>
                    <div>
                        <span>ارسال سریع</span>
                        <p>ارسال در کمتر از 24 ساعت</p>
                    </div>
                </div>
                <div class="delivery-item">
                    <i class="fas fa-box"></i>
                    <div>
                        <span>بسته‌بندی ویژه</span>
                        <p>بسته‌بندی حرفه‌ای و زیبا</p>
                    </div>
                </div>
                <div class="delivery-item">
                    <i class="fas fa-undo"></i>
                    <div>
                        <span>گارانتی راضی بودن</span>
                        <p>7 روز ضمانت بازگشت پول</p>
                    </div>
                </div>
            </div>
            
            <!-- Share Buttons (Mobile) -->
            <div class="share-buttons mobile-share">
                <span>اشتراک‌گذاری:</span>
                <div class="share-icons">
                    <a href="https://telegram.me/share/url?url=<?php echo urlencode(getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId); ?>" target="_blank" title="تلگرام">
                        <i class="fab fa-telegram"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode(getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId); ?>" target="_blank" title="واتساپ">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="#" onclick="copyToClipboard('<?php echo getSetting('site_url', 'http://localhost/goolland') . '/product.php?id=' . $productId; ?>')" title="کپی لینک">
                        <i class="fas fa-link"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Tabs -->
<div class="product-tabs-section">
    <div class="container">
        <div class="product-tabs">
            <button class="tab-btn active" data-tab="description">توضیحات</button>
            <button class="tab-btn" data-tab="reviews">نظرات (<?php echo toPersianNumbers($reviewCount); ?>)</button>
            <?php if (!empty($relatedProducts)): ?>
                <button class="tab-btn" data-tab="related">محصولات مرتبط</button>
            <?php endif; ?>
        </div>
        
        <div class="product-tabs-content">
            <!-- Description Tab -->
            <div class="tab-content active" id="description">
                <div class="product-description-full">
                    <?php echo $product['description']; ?>
                </div>
                
                <?php if (!empty($product['features'])): ?>
                    <div class="product-features">
                        <h3>ویژگی‌های محصول</h3>
                        <ul>
                            <?php 
                            $features = explode("\n", $product['features']);
                            foreach ($features as $feature):
                                if (!empty(trim($feature))):
                            ?>
                                <li><?php echo htmlspecialchars(trim($feature)); ?></li>
                            <?php endif; endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($product['care_instructions'])): ?>
                    <div class="care-instructions">
                        <h3>دستورالعمل مراقبت</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['care_instructions'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Reviews Tab -->
            <div class="tab-content" id="reviews">
                <div class="reviews-section" id="reviews">
                    <?php if (isLoggedIn() && !$userRating): ?>
                        <div class="add-review">
                            <h3>نظری ثبت کنید</h3>
                            <p>شما می‌توانید درباره این محصول نظر بدهید.</p>
                            <form id="review-form" class="review-form">
                                <div class="form-group">
                                    <label>امتیاز دهید:</label>
                                    <div class="rating-selector">
                                        <input type="radio" id="rating-5" name="rating" value="5" required>
                                        <label for="rating-5"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="rating-4" name="rating" value="4">
                                        <label for="rating-4"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="rating-3" name="rating" value="3">
                                        <label for="rating-3"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="rating-2" name="rating" value="2">
                                        <label for="rating-2"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="rating-1" name="rating" value="1">
                                        <label for="rating-1"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="review-title">عنوان نظر:</label>
                                    <input type="text" id="review-title" name="title" placeholder="عنوان نظر خود را بنویسید">
                                </div>
                                <div class="form-group">
                                    <label for="review-comment">نظر شما:</label>
                                    <textarea id="review-comment" name="comment" rows="5" placeholder="نظر خود را درباره این محصول بنویسید"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    ثبت نظر
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <div class="no-reviews">
                                <i class="fas fa-comment-slash"></i>
                                <p>هنوز هیچ نظری برای این محصول ثبت نشده است.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <span class="author-name"><?php echo htmlspecialchars($review['user_name'] ?? 'مشتری'); ?></span>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <span class="review-date"><?php echo formatDate($review['created_at']); ?></span>
                                    </div>
                                    <?php if (!empty($review['title'])): ?>
                                        <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                                    <?php endif; ?>
                                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($reviews) > 6): ?>
                        <div class="load-more-reviews">
                            <button class="btn btn-outline" onclick="loadMoreReviews()">
                                <i class="fas fa-chevron-down"></i>
                                نمایش نظرات بیشتر
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Products Tab -->
            <?php if (!empty($relatedProducts)): ?>
                <div class="tab-content" id="related">
                    <div class="related-products-grid">
                        <?php foreach ($relatedProducts as $related): ?>
                            <?php 
                            // Use the product_card template
                            $tempProduct = $related;
                            include 'includes/product_card.php';
                            ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
    <section class="products-section">
        <div class="container">
            <h2 class="section-title">محصولات ویژه</h2>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $featured): ?>
                    <?php 
                    $tempProduct = $featured;
                    include 'includes/product_card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Change main image
function changeMainImage(src) {
    document.getElementById('main-product-image').src = src;
    
    // Update active thumbnail
    const thumbnails = document.querySelectorAll('.thumbnail-gallery img');
    thumbnails.forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.src === src) {
            thumb.classList.add('active');
        }
    });
}

// Quantity selector
let currentQuantity = 1;
const maxQuantity = <?php echo $product['stock']; ?>;

function increaseQuantity() {
    if (currentQuantity < maxQuantity) {
        currentQuantity++;
        document.getElementById('quantity').value = currentQuantity;
    }
}

function decreaseQuantity() {
    if (currentQuantity > 1) {
        currentQuantity--;
        document.getElementById('quantity').value = currentQuantity;
    }
}

// Add to cart
function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    fetch('includes/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateCartCount();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('خطا در افزودن به سبد خرید', 'error');
    });
}

// Toggle wishlist
function toggleWishlist(productId) {
    <?php if (!isLoggedIn()): ?>
        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    <?php endif; ?>
    
    fetch('includes/toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            const wishlistBtn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
            if (wishlistBtn) {
                wishlistBtn.classList.toggle('active');
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('خطا در تغییر لیست علاقه‌مندی‌ها', 'error');
    });
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('لینک با موفقیت کپی شد', 'success');
    }).catch(() => {
        showNotification('خطا در کپی کردن لینک', 'error');
    });
}

// Update cart count
function updateCartCount() {
    fetch('includes/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            el.textContent = data.count;
        });
    });
}

// Product tabs
const tabButtons = document.querySelectorAll('.product-tabs .tab-btn');
const tabContents = document.querySelectorAll('.product-tabs-content .tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Remove active from all
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Add active to clicked
        button.classList.add('active');
        const targetId = button.dataset.tab;
        const targetContent = document.getElementById(targetId);
        if (targetContent) {
            targetContent.classList.add('active');
        }
    });
});

// Review form submission
const reviewForm = document.getElementById('review-form');
if (reviewForm) {
    reviewForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(reviewForm);
        formData.append('product_id', '<?php echo $productId; ?>');
        
        fetch('includes/add_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                reviewForm.reset();
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('خطا در ثبت نظر', 'error');
        });
    });
}

// Load more reviews
function loadMoreReviews() {
    // This would be implemented with AJAX
    showNotification('این ویژگی به زودی اضافه خواهد شد', 'info');
}
</script>

<?php require_once 'includes/footer.php'; ?>
