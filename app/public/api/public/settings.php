<?php
require __DIR__ . '/../../../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_error('Method not allowed', 405);
json_response([
    'banner_img'     => get_setting($pdo, 'banner_img', ''),
    'exchange_rate'  => (int) get_setting($pdo, 'exchange_rate', '15000'),
    'store_version'  => (int) get_setting($pdo, 'store_version', '1'),
    'whatsapp'       => get_setting($pdo, 'whatsapp_number', '963996311804'),
]);
