<?php
require __DIR__ . '/../../../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

$vid = get_visitor_id($pdo, $config);
$b = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim((string)($b['buyer_name'] ?? ''));
$phone = trim((string)($b['buyer_phone'] ?? ''));
$addr = trim((string)($b['buyer_address'] ?? ''));
$notes = trim((string)($b['buyer_notes'] ?? ''));
$ref = trim((string)($b['referred_by'] ?? ''));
$items = $b['items'] ?? [];

if ($name===''||$phone===''||$addr==='') json_error('Missing buyer data');
if (!is_array($items)||count($items)===0) json_error('Cart empty');

// إعادة حساب الأسعار من DB (لا تثق بالعميل)
$ids = array_map(fn($i)=>(int)$i['product_id'], $items);
$in  = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id,name,price_usd,min_qty,piece_weight FROM products WHERE id IN ($in)");
$stmt->execute($ids);
$map = [];
foreach ($stmt as $r) $map[(int)$r['id']] = $r;

$totalUsd = 0; $totalPieces = 0; $totalGrams = 0;
$orderItems = [];
foreach ($items as $it) {
    $pid=(int)$it['product_id']; $qty=(int)$it['qty'];
    if (!isset($map[$pid]) || $qty <= 0) continue;
    $p = $map[$pid];
    $lineUsd = round((float)$p['price_usd'] * $qty, 2);
    $totalUsd += $lineUsd;
    $totalPieces += $qty;
    $totalGrams += $qty * (int)$p['piece_weight'];
    $orderItems[] = ['product_id'=>$pid,'product_name'=>$p['name'],'price_usd'=>(float)$p['price_usd'],'qty'=>$qty];
}
if (empty($orderItems)) json_error('No valid items');

// حسم 2% عند 400$
if ($totalUsd >= 400) $totalUsd = round($totalUsd * 0.98, 2);
$totalWeight = round(($totalGrams + 500) / 1000, 2);
$refClean = $ref ? substr(preg_replace('/[^A-Z0-9]/','',strtoupper($ref)), 0, 4) : null;

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO orders (buyer_name,buyer_phone,buyer_address,buyer_notes,total_usd,total_pieces,total_weight,referred_by) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$name,$phone,$addr,$notes,$totalUsd,$totalPieces,$totalWeight,$refClean]);
    $oid = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,price_usd,qty) VALUES (?,?,?,?,?)');
    foreach ($orderItems as $oi) $stmt->execute([$oid,$oi['product_id'],$oi['product_name'],$oi['price_usd'],$oi['qty']]);
    $pdo->prepare('DELETE FROM carts WHERE visitor_id=?')->execute([$vid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Order error: '.$e->getMessage());
    json_error('Failed to save order', 500);
}

json_response(['ok'=>true,'order_id'=>$oid,'total_usd'=>$totalUsd,'total_pieces'=>$totalPieces,'total_weight'=>$totalWeight]);
