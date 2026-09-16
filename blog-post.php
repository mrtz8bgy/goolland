<?php
session_start();
require_once 'includes/db.php';

// Get post slug
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

// Get blog post
$post = getBlogPostBySlug($slug);

if (!$post) {
    header("Location: 404.php");
    exit;
}

// Increment view count
try {
    $stmt = $pdo->prepare("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$post['id']]);
} catch (PDOException $e) {
    // Continue without updating view count
}

// Get related posts
$relatedPosts = [];
if ($post['category_id']) {
    try {
        $stmt = $pdo->prepare("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.category_id = ? AND bp.id != ? AND bp.is_published = 1 ORDER BY RAND() LIMIT 3");
        $stmt->execute([$post['category_id'], $post['id']]);
        $relatedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $relatedPosts = [];
    }
}

// Get recent posts
$recentPosts = [];
try {
    $stmt = $pdo->query("SELECT bp.*, u.name as author_name, bc.name as category_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id LEFT JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_published = 1 AND bp.id != {$post['id']} ORDER BY bp.published_at DESC LIMIT 5");
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentPosts = [];
}

// Get comments
$comments = [];
try {
    $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.is_approved = 1 AND bc.parent_id IS NULL ORDER BY bc.created_at DESC");
    $stmt->execute([$post['id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get comment replies
    foreach ($comments as &$comment) {
        try {
            $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.parent_id = ? AND bc.is_approved = 1 ORDER BY bc.created_at ASC");
            $stmt->execute([$post['id'], $comment['id']]);
            $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $comment['replies'] = [];
        }
    }
} catch (PDOException $e) {
    $comments = [];
}

// Handle comment submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!isLoggedIn()) {
        $error = 'برای ارسال نظر، ابتدا وارد حساب کاربری شوید.';
    } else {
        $commentContent = trim($_POST['comment'] ?? '');
        
        if (empty($commentContent)) {
            $error = 'لطفا نظر خود را وارد کنید.';
        } else {
            $commentData = [
                'post_id' => $post['id'],
                'user_id' => getCurrentUserId(),
                'parent_id' => null,
                'author_name' => $_SESSION['name'] ?? '',
                'author_email' => $_SESSION['email'] ?? '',
                'content' => $commentContent,
                'is_approved' => 0 // Comments need to be approved by admin
            ];
            
            $commentId = createBlogComment($commentData);
            
            if ($commentId) {
                $success = 'نظر شما با موفقیت ارسال شد و پس از تایید نمایش داده خواهد شد.';
                
                // Log activity
                logActivity(getCurrentUserId(), 'add_comment', 'Comment added for post: ' . $post['id']);
                
                // Refresh comments
                try {
                    $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.is_approved = 1 AND bc.parent_id IS NULL ORDER BY bc.created_at DESC");
                    $stmt->execute([$post['id']]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($comments as &$comment) {
                        try {
                            $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.parent_id = ? AND bc.is_approved = 1 ORDER BY bc.created_at ASC");
                            $stmt->execute([$post['id'], $comment['id']]);
                            $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $comment['replies'] = [];
                        }
                    }
                } catch (PDOException $e) {
                    // Continue with existing comments
                }
            } else {
                $error = 'خطا در ارسال نظر.';
            }
        }
    }
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    if (!isLoggedIn()) {
        $error = 'برای ارسال پاسخ، ابتدا وارد حساب کاربری شوید.';
    } else {
        $replyContent = trim($_POST['reply'] ?? '');
        $parentCommentId = intval($_POST['parent_comment_id'] ?? 0);
        
        if (empty($replyContent)) {
            $error = 'لطفا پاسخ خود را وارد کنید.';
        } elseif ($parentCommentId <= 0) {
            $error = 'خطا در ارسال پاسخ.';
        } else {
            $replyData = [
                'post_id' => $post['id'],
                'user_id' => getCurrentUserId(),
                'parent_id' => $parentCommentId,
                'author_name' => $_SESSION['name'] ?? '',
                'author_email' => $_SESSION['email'] ?? '',
                'content' => $replyContent,
                'is_approved' => 0
            ];
            
            $replyId = createBlogComment($replyData);
            
            if ($replyId) {
                $success = 'پاسخ شما با موفقیت ارسال شد و پس از تایید نمایش داده خواهد شد.';
                
                // Log activity
                logActivity(getCurrentUserId(), 'add_reply', 'Reply added for comment: ' . $parentCommentId);
                
                // Refresh comments
                try {
                    $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.is_approved = 1 AND bc.parent_id IS NULL ORDER BY bc.created_at DESC");
                    $stmt->execute([$post['id']]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($comments as &$comment) {
                        try {
                            $stmt = $pdo->prepare("SELECT bc.*, u.name as user_name, u.image_path as user_image FROM blog_comments bc LEFT JOIN users u ON bc.user_id = u.id WHERE bc.post_id = ? AND bc.parent_id = ? AND bc.is_approved = 1 ORDER BY bc.created_at ASC");
                            $stmt->execute([$post['id'], $comment['id']]);
                            $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            $comment['replies'] = [];
                        }
                    }
                } catch (PDOException $e) {
                    // Continue with existing comments
                }
            } else {
                $error = 'خطا در ارسال پاسخ.';
            }
        }
    }
}

$pageTitle = htmlspecialchars($post['title']);
$pageDescription = htmlspecialchars($post['meta_description'] ?? $post['excerpt'] ?? substr($post['content'], 0, 160));
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
        <nav class="breadcrumb">
            <a href="index.php">خانه</a>
            <i class="fas fa-chevron-left"></i>
            <a href="blog.php">بلاگ</a>
            <i class="fas fa-chevron-left"></i>
            <?php if ($post['category_name']): ?>
                <a href="blog.php?category=<?php echo $post['category_id']; ?>"><?php echo htmlspecialchars($post['category_name']); ?></a>
                <i class="fas fa-chevron-left"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($post['title']); ?></span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<div class="blog-post-page">
    <div class="container">
        <article class="blog-post">
            <!-- Post Header -->
            <div class="post-header">
                <div class="post-meta">
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo formatDate($post['published_at']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($post['author_name'] ?? 'ادمین'); ?></span>
                    </div>
                    <?php if ($post['category_name']): ?>
                        <div class="meta-item">
                            <i class="fas fa-folder-open"></i>
                            <a href="blog.php?category=<?php echo $post['category_id']; ?>">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?php echo toPersianNumbers($post['view_count'] ?? 0); ?> بازدید</span>
                    </div>
                </div>
                
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                    <div class="post-featured-image">
                        <img src="<?php echo $post['featured_image']; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                <?php endif; ?>
                
                <!-- Excerpt -->
                <?php if ($post['excerpt']): ?>
                    <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Post Content -->
            <div class="post-content">
                <?php echo $post['content']; ?>
            </div>
            
            <!-- Post Tags -->
            <?php if ($post['meta_keywords']): ?>
                <div class="post-tags">
                    <span><i class="fas fa-tags"></i> برچسب‌ها:</span>
                    <?php
                    $tags = explode(',', $post['meta_keywords']);
                    foreach ($tags as $tag):
                        $tag = trim($tag);
                        if (!empty($tag)):
                    ?>
                        <a href="blog.php?query=<?php echo urlencode($tag); ?>">
                            <?php echo htmlspecialchars($tag); ?>
                        </a>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Post Sharing -->
            <div class="post-sharing">
                <span><i class="fas fa-share-alt"></i> اشتراک گذاری:</span>
                <div class="social-share-buttons">
                    <a href="https://facebook.com/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                       target="_blank" class="share-btn facebook">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                       target="_blank" class="share-btn twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>"
                       target="_blank" class="share-btn whatsapp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="#" onclick="copyToClipboard('<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>')" 
                       class="share-btn copy">
                        <i class="fas fa-copy"></i>
                    </a>
                </div>
            </div>
            
            <!-- Author Bio -->
            <?php if ($post['author_name']): ?>
                <div class="author-bio">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>نویسنده: <?php echo htmlspecialchars($post['author_name']); ?></h4>
                        <p>نویسنده این مقاله در گولند، متخصص در زمینه گل و گیاه با سال‌ها تجربه در این حوزه است.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Comments Section -->
            <section class="comments-section">
                <h3><i class="fas fa-comments"></i> نظرات (<?php echo toPersianNumbers(count($comments)); ?>)</h3>
                
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
                
                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                    <p class="no-comments">هنوز نظری برای این مقاله ارسال نشده است. اولین نفر باشید!</p>
                <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                                <div class="comment-avatar">
                                    <?php if ($comment['user_image']): ?>
                                        <img src="<?php echo $comment['user_image']; ?>" alt="<?php echo htmlspecialchars($comment['user_name']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <h4><?php echo htmlspecialchars($comment['user_name'] ?? 'ناشناس'); ?></h4>
                                        <span class="comment-date"><?php echo formatDate($comment['created_at']); ?></span>
                                    </div>
                                    <p class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></p>
                                    
                                    <!-- Reply Button -->
                                    <button class="reply-btn" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-reply"></i>
                                        پاسخ
                                    </button>
                                    
                                    <!-- Reply Form -->
                                    <div class="reply-form-container" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                                        <form method="POST" action="blog-post.php?slug=<?php echo $slug; ?>" class="reply-form">
                                            <input type="hidden" name="parent_comment_id" value="<?php echo $comment['id']; ?>">
                                            <textarea name="reply" class="form-control" placeholder="پاسخ خود را وارد کنید..." required></textarea>
                                            <div class="reply-form-actions">
                                                <button type="submit" name="submit_reply" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-paper-plane"></i>
                                                    ارسال پاسخ
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                    انصراف
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Replies -->
                                    <?php if (!empty($comment['replies'])): ?>
                                        <div class="replies">
                                            <?php foreach ($comment['replies'] as $reply): ?>
                                                <div class="reply">
                                                    <div class="reply-avatar">
                                                        <?php if ($reply['user_image']): ?>
                                                            <img src="<?php echo $reply['user_image']; ?>" alt="<?php echo htmlspecialchars($reply['user_name']); ?>">
                                                        <?php else: ?>
                                                            <i class="fas fa-user"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="reply-content">
                                                        <div class="reply-header">
                                                            <h4><?php echo htmlspecialchars($reply['user_name'] ?? 'ناشناس'); ?></h4>
                                                            <span class="reply-date"><?php echo formatDate($reply['created_at']); ?></span>
                                                        </div>
                                                        <p class="reply-text"><?php echo htmlspecialchars($reply['content']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Comment Form -->
                <?php if (isLoggedIn()): ?>
                    <div class="comment-form">
                        <h3><i class="fas fa-pencil-alt"></i> ارسال نظر</h3>
                        <form method="POST" action="blog-post.php?slug=<?php echo $slug; ?>" class="comment-form">
                            <div class="form-group">
                                <textarea name="comment" class="form-control" rows="5" placeholder="نظر خود را درباره این مقاله وارد کنید..." required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="submit_comment" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    ارسال نظر
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-to-comment">
                        <p>برای ارسال نظر، ابتدا وارد حساب کاربری شوید.</p>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            ورود
                        </a>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
                <section class="related-posts">
                    <h3><i class="fas fa-link"></i> مقالات مرتبط</h3>
                    <div class="related-posts-grid">
                        <?php foreach ($relatedPosts as $relatedPost): ?>
                            <article class="related-post-card">
                                <a href="blog-post.php?slug=<?php echo $relatedPost['slug']; ?>">
                                    <div class="related-post-image">
                                        <?php if ($relatedPost['featured_image']): ?>
                                            <img src="<?php echo $relatedPost['featured_image']; ?>" alt="<?php echo htmlspecialchars($relatedPost['title']); ?>">
                                        <?php else: ?>
                                            <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($relatedPost['title']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="related-post-content">
                                        <h4><?php echo htmlspecialchars($relatedPost['title']); ?></h4>
                                        <span class="related-post-date"><?php echo formatDate($relatedPost['published_at']); ?></span>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>
        
        <!-- Sidebar -->
        <aside class="blog-sidebar">
            <!-- Recent Posts Widget -->
            <div class="sidebar-widget recent-posts-widget">
                <h3 class="widget-title"><i class="fas fa-clock"></i> جدیدترین مقالات</h3>
                <ul>
                    <?php foreach ($recentPosts as $recentPost): ?>
                        <li>
                            <a href="blog-post.php?slug=<?php echo $recentPost['slug']; ?>">
                                <div class="recent-post-image">
                                    <?php if ($recentPost['featured_image']): ?>
                                        <img src="<?php echo $recentPost['featured_image']; ?>" alt="<?php echo htmlspecialchars($recentPost['title']); ?>">
                                    <?php else: ?>
                                        <img src="assets/images/default-blog.jpg" alt="<?php echo htmlspecialchars($recentPost['title']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="recent-post-info">
                                    <h4><?php echo htmlspecialchars($recentPost['title']); ?></h4>
                                    <span class="recent-post-date"><?php echo formatDate($recentPost['published_at']); ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Categories Widget -->
            <div class="sidebar-widget categories-widget">
                <h3 class="widget-title"><i class="fas fa-folder-open"></i> دسته‌بندی‌ها</h3>
                <ul>
                    <?php
                    $blogCategories = getBlogCategories();
                    foreach ($blogCategories as $category):
                        // Count posts in this category
                        $postCount = 0;
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE category_id = ? AND is_published = 1");
                            $stmt->execute([$category['id']]);
                            $postCount = $stmt->fetchColumn();
                        } catch (PDOException $e) {
                            $postCount = 0;
                        }
                    ?>
                        <li>
                            <a href="blog.php?category=<?php echo $category['id']; ?>" 
                               class="<?php echo $category['id'] == $post['category_id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="post-count"><?php echo toPersianNumbers($postCount); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Newsletter Widget -->
            <div class="sidebar-widget newsletter-widget">
                <h3 class="widget-title"><i class="fas fa-paper-plane"></i> خبرنامه</h3>
                <p>برای دریافت آخرین مقالات و اخبار در خبرنامه عضو شوید</p>
                <form action="includes/newsletter.php" method="post" class="newsletter-form">
                    <input type="email" name="email" placeholder="آدرس ایمیل" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-paper-plane"></i>
                        عضویت
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>

<!-- Related Products Section -->
<?php
$relatedProducts = getFeaturedProducts(4);
if (!empty($relatedProducts)):
?>
    <section class="related-products-section">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-gift"></i> محصولات ویژه</h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $product): ?>
                    <?php include 'includes/product_card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<script>
// Toggle reply form
function toggleReplyForm(commentId) {
    const replyForm = document.getElementById('reply-form-' + commentId);
    const replyBtn = event.target.closest('.comment').querySelector('.reply-btn');
    
    if (replyForm.style.display === 'none') {
        replyForm.style.display = 'block';
        replyBtn.innerHTML = '<i class="fas fa-times"></i> انصراف';
        replyBtn.onclick = function() { toggleReplyForm(commentId); };
    } else {
        replyForm.style.display = 'none';
        replyBtn.innerHTML = '<i class="fas fa-reply"></i> پاسخ';
        replyBtn.onclick = function() { toggleReplyForm(commentId); };
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('لینک مقاله با موفقیت کپی شد.');
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
