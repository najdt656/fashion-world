<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();

// ===== Method resolution =====
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$id     = (int)($_GET['id'] ?? 0);

// ===== GET =====
if ($method === 'GET') {
    if ($id === 0) {
        $rows = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
        json_response(['products' => $rows]);
    }
    $s = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $s->execute([$id]);
    $p = $s->fetch();
    if (!$p) json_error('Product not found', 404);
    $s = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id=? ORDER BY sort_order, id");
    $s->execute([$id]);
    $p['extras'] = $s->fetchAll(PDO::FETCH_COLUMN);
    json_response(['product' => $p]);
}

// ===== Mutations require CSRF =====
require_csrf();

// ===== Parse body =====
$rawBody  = file_get_contents('php://input');
$jsonBody = ($rawBody !== '' && $rawBody !== false) ? (json_decode($rawBody, true) ?: []) : [];
$b = !empty($_POST) ? $_POST : $jsonBody;

// ===== Method override (POST + _method=PUT  FormData) =====
if ($method === 'POST' && isset($b['_method'])) {
    $override = strtoupper((string)$b['_method']);
    if (!in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        json_error('Invalid _method override: ' . $override, 400);
    }
    $method = $override;
}

// ===== Field validator =====
$fields = function(array $b, ?array $existing = null): array {
    $get = fn($k, $d) => array_key_exists($k, $b) ? $b[$k] : ($existing[$k] ?? $d);
    $f = [
        'name'             => trim((string)$get('name', '')),
        'category'         => (string)$get('category', 'نسواني'),
        'price_usd'        => (float)$get('price_usd', 0),
        'min_qty'          => (int)$get('min_qty', 10),
        'pack'             => trim((string)$get('pack', '')),
        'colors'           => trim((string)$get('colors', '')),
        'piece_weight'     => (int)$get('piece_weight', 350),
        'stock_status'     => (string)$get('stock_status', 'AVAILABLE'),
        'fomo_left'        => (int)$get('fomo_left', 0),
        'fomo_booked'      => trim((string)$get('fomo_booked', '')),
        'admin_desc'       => trim((string)$get('admin_desc', '')),
        'has_discount'     => !empty($b['has_discount']) ? 1 : 0,
        'discount_percent' => (int)$get('discount_percent', 0),
        'is_clearance'     => !empty($b['is_clearance']) ? 1 : 0,
    ];
    if ($f['name'] === '')       json_error('name is required');
    if ($f['price_usd'] <= 0)    json_error('price_usd must be > 0');
    if (!in_array($f['category'], ['نسواني','رجالي','ولادي','ب.ب'], true))   json_error('Invalid category');
    if (!in_array($f['stock_status'], ['AVAILABLE','LOW','PREORDER','SOLD_OUT'], true)) json_error('Invalid stock_status');
    if ($f['discount_percent'] < 0 || $f['discount_percent'] > 90) json_error('Invalid discount_percent');
    return $f;
};

// ===== Image saver =====
$saveImage = function(array $file): ?string {
    global $config;
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $config['app']['max_image_size']) json_error('Image too large', 413);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($config['app']['allowed_mime'][$mime])) json_error('Invalid image type: ' . $mime, 415);
    $ext  = $config['app']['allowed_mime'][$mime];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dir  = $config['app']['uploads_path'] . '/products';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) json_error('Upload failed', 500);
    return '/uploads/products/' . $name;
};

// ===== POST (create) =====
if ($method === 'POST') {
    $f = $fields($b);
    $mainImg = isset($_FILES['main_image']) ? $saveImage($_FILES['main_image']) : null;
    $f['main_image'] = $mainImg;

    $cols         = array_keys($f);
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql          = 'INSERT INTO products (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')';

    try {
        $pdo->prepare($sql)->execute(array_values($f));
    } catch (PDOException $e) {
        error_log('Product insert failed: ' . $e->getMessage());
        json_error('Insert failed: ' . $e->getMessage(), 500);
    }
    $pid = (int)$pdo->lastInsertId();

    if (!empty($_FILES['extras']['name'][0])) {
        $cnt = min(count($_FILES['extras']['name']), 4);
        for ($i = 0; $i < $cnt; $i++) {
            $file = [
                'name'     => $_FILES['extras']['name'][$i],
                'tmp_name' => $_FILES['extras']['tmp_name'][$i],
                'size'     => $_FILES['extras']['size'][$i],
                'error'    => $_FILES['extras']['error'][$i],
            ];
            $p = $saveImage($file);
            if ($p) {
                $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)')
                    ->execute([$pid, $p, $i]);
            }
        }
    }

    bump_store_version($pdo);
    json_response(['ok' => true, 'id' => $pid]);
}

// ===== PUT (update) =====
if ($method === 'PUT') {
    if ($id <= 0) json_error('Missing or invalid id', 400);

    $s = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $s->execute([$id]);
    $ex = $s->fetch();
    if (!$ex) json_error('Product not found', 404);

    $f = $fields($b, $ex);

    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($ex['main_image']) && strpos($ex['main_image'], '/uploads/') === 0) {
            $oldFile = $config['app']['uploads_path'] . str_replace('/uploads', '', $ex['main_image']);
            if (is_file($oldFile)) @unlink($oldFile);
        }
        $f['main_image'] = $saveImage($_FILES['main_image']);
    }

    $set = implode(',', array_map(fn($c) => "$c=?", array_keys($f)));
    $pdo->prepare("UPDATE products SET $set WHERE id=?")->execute(array_merge(array_values($f), [$id]));

    if (!empty($_POST['delete_extras']) && is_array($_POST['delete_extras'])) {
        foreach ($_POST['delete_extras'] as $path) {
            $path = trim((string)$path);
            if ($path === '') continue;

            $pdo->prepare('DELETE FROM product_images WHERE product_id=? AND image_path=?')
                ->execute([$id, $path]);

            if (strpos($path, '/uploads/') === 0) {
                $file = $config['app']['uploads_path'] . str_replace('/uploads', '', $path);
                if (is_file($file)) @unlink($file);
            }
        }
    }

    if (!empty($_FILES['extras']['name'][0])) {
        $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id=?');
        $cntStmt->execute([$id]);
        $existing = (int)$cntStmt->fetchColumn();
        $maxNew = max(0, 4 - $existing);

        $cnt = min(count($_FILES['extras']['name']), $maxNew);
        for ($i = 0; $i < $cnt; $i++) {
            $file = [
                'name'     => $_FILES['extras']['name'][$i],
                'tmp_name' => $_FILES['extras']['tmp_name'][$i],
                'size'     => $_FILES['extras']['size'][$i],
                'error'    => $_FILES['extras']['error'][$i],
            ];
            $p = $saveImage($file);
            if ($p) {
                $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id=?');
                $sortStmt->execute([$id]);
                $nextSort = (int)$sortStmt->fetchColumn() + 1;
                $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)')
                    ->execute([$id, $p, $nextSort]);
            }
        }
    }

    bump_store_version($pdo);
    json_response(['ok' => true, 'id' => $id]);
}

// ===== DELETE =====
if ($method === 'DELETE') {
    if ($id <= 0) json_error('Missing or invalid id', 400);
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    bump_store_version($pdo);
    json_response(['ok' => true]);
}

json_error('Method not allowed: ' . $method, 405);
