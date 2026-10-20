<?php
require __DIR__ . '/../../../src/bootstrap.php';
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed',405);
    $b = json_decode(file_get_contents('php://input'), true) ?: [];
    $code = strtoupper(trim((string)($b['code'] ?? '')));
    if (!preg_match('/^[A-Z0-9]{8}$/', $code)) json_error('Invalid code');
    $stmt = $pdo->prepare('SELECT id,name,code,short_code,created_at FROM marketers WHERE code=?');
    $stmt->execute([$code]);
    $m = $stmt->fetch();
    if (!$m) json_error('Code not found', 404);
    $_SESSION['marketer_id'] = (int)$m['id'];
    session_regenerate_id(true);
    json_response(['ok'=>true,'marketer'=>$m]);
}

if ($action === 'logout') { unset($_SESSION['marketer_id']); json_response(['ok'=>true]); }

if ($action === 'dashboard') {
    if (empty($_SESSION['marketer_id'])) json_error('Unauthorized',401);
    $mid = (int)$_SESSION['marketer_id'];
    $stmt=$pdo->prepare('SELECT id,name,code,short_code FROM marketers WHERE id=?'); $stmt->execute([$mid]);
    $m=$stmt->fetch();
    if (!$m) json_error('Not found',404);
    $stmt=$pdo->prepare("SELECT id,order_date,total_usd,commission_usd,pieces,status FROM marketer_orders WHERE marketer_id=? ORDER BY id DESC");
    $stmt->execute([$mid]);
    $orders=$stmt->fetchAll();
    $sales=0; $comm=0;
    foreach ($orders as &$o) { if ($o['status']!=='CANCELLED') { $sales+=(float)$o['total_usd']; $comm+=(float)$o['commission_usd']; } $o['total_usd']=(float)$o['total_usd']; $o['commission_usd']=(float)$o['commission_usd']; }
    json_response(['marketer'=>$m,'orders'=>$orders,'total_sales'=>round($sales,2),'confirmed_commission'=>round($comm,2)]);
}

json_error('Unknown action');
