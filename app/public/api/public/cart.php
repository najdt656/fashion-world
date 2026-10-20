<?php
require __DIR__ . '/../../../src/bootstrap.php';
$vid = get_visitor_id($pdo, $config);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt=$pdo->prepare("
        SELECT c.product_id id, c.qty, p.name, p.price_usd, p.min_qty, p.piece_weight, p.main_image img, p.category, p.stock_status
        FROM carts c JOIN products p ON p.id=c.product_id WHERE c.visitor_id=? ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$vid]);
    $items = $stmt->fetchAll();
    foreach ($items as &$i) { $i['id']=(int)$i['id']; $i['qty']=(int)$i['qty']; $i['price_usd']=(float)$i['price_usd']; $i['min_qty']=(int)$i['min_qty']; $i['piece_weight']=(int)$i['piece_weight']; }
    json_response(['items'=>$items]);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$pid = (int)($body['product_id'] ?? 0);

if ($method === 'POST') {
    $qty = (int)($body['qty'] ?? 0);
    if ($pid<=0 || $qty<=0) json_error('Invalid data');
    $pdo->prepare('INSERT INTO carts (visitor_id,product_id,qty) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE qty=qty+VALUES(qty)')->execute([$vid,$pid,$qty]);
    json_response(['ok'=>true]);
}

if ($method === 'PATCH') {
    $qty = (int)($body['qty'] ?? 0);
    if ($pid<=0 || $qty<0) json_error('Invalid data');
    if ($qty === 0) $pdo->prepare('DELETE FROM carts WHERE visitor_id=? AND product_id=?')->execute([$vid,$pid]);
    else $pdo->prepare('UPDATE carts SET qty=? WHERE visitor_id=? AND product_id=?')->execute([$qty,$vid,$pid]);
    json_response(['ok'=>true]);
}

if ($method === 'DELETE') {
    if ($pid > 0) $pdo->prepare('DELETE FROM carts WHERE visitor_id=? AND product_id=?')->execute([$vid,$pid]);
    else $pdo->prepare('DELETE FROM carts WHERE visitor_id=?')->execute([$vid]);
    json_response(['ok'=>true]);
}

json_error('Method not allowed', 405);
