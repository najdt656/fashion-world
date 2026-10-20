<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD']!=='POST') json_error('Method not allowed',405);
require_csrf();
$b=json_decode(file_get_contents('php://input'),true)?:[];

if (isset($b['exchange_rate'])) {
    $rate=(int)$b['exchange_rate'];
    if($rate<100||$rate>1_000_000) json_error('Invalid rate');
    set_setting($pdo,'exchange_rate',(string)$rate);
}
if (isset($b['banner_img'])) {
    set_setting($pdo,'banner_img',(string)$b['banner_img']);
}
bump_store_version($pdo);
json_response(['ok'=>true]);
