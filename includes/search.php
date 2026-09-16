<?php
/**
 * Search API
 * Handles search operations
 */

require_once 'config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'suggest':
        suggest();
        break;
    case 'popular':
        getPopularSearches();
        break;
    case 'recent':
        getRecentSearches();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'عملیات مشخص نشده است']);
}

/**
 * Get search suggestions
 */
function suggest() {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'لطفا عبارت جستجو را وارد کنید']);
        return;
    }
    
    global $pdo;
    
    try {
        // Search in products
        $stmt = $pdo->prepare("SELECT name as text, 'product' as type FROM products WHERE name LIKE ? AND is_active = 1 LIMIT 5");
        $stmt->execute(["%$query%"]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search in categories
        $stmt = $pdo->prepare("SELECT name as text, 'category' as type FROM categories WHERE name LIKE ? AND is_active = 1 LIMIT 5");
        $stmt->execute(["%$query%"]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search in blog posts
        $stmt = $pdo->prepare("SELECT title as text, 'post' as type FROM blog_posts WHERE title LIKE ? AND is_published = 1 LIMIT 5");
        $stmt->execute(["%$query%"]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search in pages
        $stmt = $pdo->prepare("SELECT title as text, 'page' as type FROM pages WHERE title LIKE ? AND is_active = 1 LIMIT 5");
        $stmt->execute(["%$query%"]);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine results
        $suggestions = array_merge($products, $categories, $posts, $pages);
        
        echo json_encode(['success' => true, 'suggestions' => $suggestions]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا: ' . $e->getMessage()]);
    }
}

/**
 * Get popular searches
 */
function getPopularSearches() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT query, COUNT(*) as count FROM search_history GROUP BY query ORDER BY count DESC LIMIT 10");
        $popularSearches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'searches' => $popularSearches]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا: ' . $e->getMessage()]);
    }
}

/**
 * Get recent searches
 */
function getRecentSearches() {
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    global $pdo;
    
    try {
        if ($userId > 0) {
            $stmt = $pdo->prepare("SELECT query FROM search_history WHERE user_id = ? GROUP BY query ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT query FROM search_history GROUP BY query ORDER BY created_at DESC LIMIT 10");
        }
        
        $recentSearches = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'searches' => $recentSearches]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'خطا: ' . $e->getMessage()]);
    }
}
