<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD']==='GET') {
    $id=(int)($_GET['id']??0);
    if ($id>0) {
        $s=$pdo->prepare('SELECT * FROM orders WHERE id=?'); $s->execute([$id]);
        $o=$s->fetch(); if(!$o) json_error('Not found',404);
        $s=$pdo->prepare('SELECT product_name,price_usd,qty FROM order_items WHERE order_id=?'); $s->execute([$id]);
        $o['items']=$s->fetchAll();
        json_response(['order'=>$o]);
    }
    $rows=$pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 200")->fetchAll();
    json_response(['orders'=>$rows]);
}

require_csrf();
if ($_SERVER['REQUEST_METHOD']==='PATCH') {
    $b=json_decode(file_get_contents('php://input'),true)?:[];
    $id=(int)($b['id']??0);
    $status=(string)($b['status']??'');
    if($id<=0 || !in_array($status,['PENDING','CONFIRMED','SHIPPED','CANCELLED'],true)) json_error('Invalid');
    $pdo->prepare('UPDATE orders SET status=?, whatsapp_sent=? WHERE id=?')->execute([$status, !empty($b['whatsapp_sent'])?1:0, $id]);
    json_response(['ok'=>true]);
}
json_error('Method not allowed',405);
