<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD']==='GET') {
    $rows=$pdo->query("SELECT c.id,c.product_id,p.name product_name,c.author,c.body,c.created_at FROM product_comments c JOIN products p ON p.id=c.product_id ORDER BY c.id DESC LIMIT 200")->fetchAll();
    json_response(['comments'=>$rows]);
}
if ($_SERVER['REQUEST_METHOD']==='DELETE') {
    require_csrf();
    $id=(int)($_GET['id']??0); if($id<=0) json_error('Missing id');
    $pdo->prepare('DELETE FROM product_comments WHERE id=?')->execute([$id]);
    json_response(['ok'=>true]);
}
json_error('Method not allowed',405);
