<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';
$id=(int)($_GET['id']??0);

if ($method==='GET') {
    $marketers=$pdo->query("SELECT * FROM marketers ORDER BY created_at DESC")->fetchAll();
    foreach ($marketers as &$m) {
        $s=$pdo->prepare("SELECT id,order_date,total_usd,commission_usd,pieces,status FROM marketer_orders WHERE marketer_id=? ORDER BY id DESC");
        $s->execute([$m['id']]);
        $orders=$s->fetchAll();
        $sales=0;$comm=0;
        foreach($orders as $o){ if($o['status']!=='CANCELLED'){ $sales+=(float)$o['total_usd']; $comm+=(float)$o['commission_usd']; } }
        $m['orders']=$orders;
        $m['total_sales']=round($sales,2);
        $m['confirmed_commission']=round($comm,2);
    }
    json_response(['marketers'=>$marketers]);
}

require_csrf();

if ($method==='POST' && $action==='') {
    $b=json_decode(file_get_contents('php://input'),true)?:[];
    $name=trim((string)($b['name']??''));
    if($name==='') json_error('Name required');
    $chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code=''; for($i=0;$i<8;$i++) $code.=$chars[random_int(0,strlen($chars)-1)];
        $s=$pdo->prepare('SELECT 1 FROM marketers WHERE code=?'); $s->execute([$code]);
    } while ($s->fetchColumn());
    $pdo->prepare('INSERT INTO marketers (name,code,short_code) VALUES (?,?,?)')->execute([$name,$code,substr($code,0,4)]);
    json_response(['ok'=>true,'code'=>$code]);
}

if ($method==='DELETE' && $id>0) {
    $pdo->prepare('DELETE FROM marketers WHERE id=?')->execute([$id]);
    json_response(['ok'=>true]);
}

if ($method==='POST' && $action==='add-order') {
    $b=json_decode(file_get_contents('php://input'),true)?:[];
    $mid=(int)($b['marketer_id']??0);
    $usd=(float)($b['total_usd']??0);
    $rate=(float)($b['rate']??0.03);
    if($mid<=0||$usd<=0||$rate<0||$rate>0.2) json_error('Invalid data');
    $comm=round($usd*$rate,2);
    $pdo->prepare("INSERT INTO marketer_orders (marketer_id,order_date,total_usd,commission_usd,pieces,status) VALUES (?,CURDATE(),?,?,?, 'CONFIRMED')")
        ->execute([$mid,$usd,$comm,(int)round($usd/5)]);
    json_response(['ok'=>true,'commission'=>$comm]);
}

if ($method==='DELETE' && $action==='delete-order') {
    $oid=(int)($_GET['order_id']??0);
    if($oid<=0) json_error('Missing order_id');
    $pdo->prepare('DELETE FROM marketer_orders WHERE id=?')->execute([$oid]);
    json_response(['ok'=>true]);
}

json_error('Unknown action');
