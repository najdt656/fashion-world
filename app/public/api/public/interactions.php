<?php
require __DIR__ . '/../../../src/bootstrap.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// قراءة جسم الطلب بأمان
$rawBody = file_get_contents('php://input');
$body = [];
if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $body = $decoded;
    }
}

// استخراج المعاملات
$action = $_GET['action'] ?? '';
$pid = isset($body['product_id']) ? (int)$body['product_id'] : 0;

if ($pid <= 0) {
    json_error('Invalid or missing product_id');
}

// التحقق من وجود المنتج
$check = $pdo->prepare('SELECT id FROM products WHERE id = ?');
$check->execute([$pid]);
if (!$check->fetch()) {
    json_error('Product not found', 404);
}

$vid = get_visitor_id($pdo, $config);

// تنفيذ الإجراء المطلوب
switch ($action) {
    case 'view':
        $stmt = $pdo->prepare('INSERT IGNORE INTO visitor_views (visitor_id, product_id) VALUES (?, ?)');
        $stmt->execute([$vid, $pid]);
        
        // زيادة العداد فقط إذا كانت المشاهدة جديدة
        if ($stmt->rowCount() > 0) {
            $pdo->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$pid]);
        }
        json_response(['ok' => true, 'counted' => $stmt->rowCount() > 0]);
        break;

    case 'like':
        // ... (باقي كود الإعجاب، التقييم، المفضلة يبقى كما هو، مع إضافة التحقق من المدخلات)
        $stmt = $pdo->prepare('DELETE FROM visitor_likes WHERE visitor_id = ? AND product_id = ?');
        $stmt->execute([$vid, $pid]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->prepare('UPDATE products SET likes = GREATEST(0, likes - 1) WHERE id = ?')->execute([$pid]);
            $liked = false;
        } else {
            $pdo->prepare('INSERT INTO visitor_likes (visitor_id, product_id) VALUES (?, ?)')->execute([$vid, $pid]);
            $pdo->prepare('UPDATE products SET likes = likes + 1 WHERE id = ?')->execute([$pid]);
            $liked = true;
        }
        
        $likes = $pdo->prepare('SELECT likes FROM products WHERE id = ?');
        $likes->execute([$pid]);
        json_response(['ok' => true, 'liked' => $liked, 'likes' => (int)$likes->fetchColumn()]);
        break;

    case 'rate':
        $stars = isset($body['stars']) ? (int)$body['stars'] : 0;
        if ($stars < 1 || $stars > 5) {
            json_error('Invalid stars (must be 1-5)');
        }
        
        $pdo->prepare('INSERT INTO visitor_ratings (visitor_id, product_id, stars) VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE stars = VALUES(stars)')->execute([$vid, $pid, $stars]);
        
        $agg = $pdo->prepare('SELECT COALESCE(SUM(stars),0) as s, COUNT(*) as c FROM visitor_ratings WHERE product_id = ?');
        $agg->execute([$pid]);
        $row = $agg->fetch();
        
        $pdo->prepare('UPDATE products SET rating_sum = ?, rating_count = ? WHERE id = ?')
            ->execute([(int)$row['s'], (int)$row['c'], $pid]);
        
        json_response(['ok' => true, 'rating_sum' => (int)$row['s'], 'rating_count' => (int)$row['c']]);
        break;

    case 'wishlist':
        $stmt = $pdo->prepare('DELETE FROM wishlists WHERE visitor_id = ? AND product_id = ?');
        $stmt->execute([$vid, $pid]);
        
        if ($stmt->rowCount() > 0) {
            json_response(['ok' => true, 'wished' => false]);
        } else {
            $pdo->prepare('INSERT INTO wishlists (visitor_id, product_id) VALUES (?, ?)')->execute([$vid, $pid]);
            json_response(['ok' => true, 'wished' => true]);
        }
        break;

    default:
        json_error('Unknown action: ' . $action, 400);
}
