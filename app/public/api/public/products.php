<?php
require __DIR__ . '/../../../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_error('Method not allowed', 405);

$vid = get_visitor_id($pdo, $config);

$products = $pdo->query("
    SELECT id,name,category,price_usd,min_qty,pack,colors,piece_weight,stock_status,
           fomo_left,fomo_booked,admin_desc,has_discount,discount_percent,is_clearance,
           main_image,views,likes,rating_sum,rating_count,created_at
    FROM products ORDER BY created_at DESC
")->fetchAll();

if ($products) {
    $ids = array_column($products, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $imgs = [];
    $stmt = $pdo->prepare("SELECT product_id,image_path FROM product_images WHERE product_id IN ($in) ORDER BY sort_order,id");
    $stmt->execute($ids);
    foreach ($stmt as $r) $imgs[$r['product_id']][] = $r['image_path'];

    $cmts = [];
    $stmt = $pdo->prepare("SELECT id,product_id,author,body,created_at FROM product_comments WHERE product_id IN ($in) ORDER BY id ASC");
    $stmt->execute($ids);
    foreach ($stmt as $r) $cmts[$r['product_id']][] = $r;

    $liked = $wished = $rated = $viewed = [];
    $stmt = $pdo->prepare("SELECT product_id FROM visitor_likes WHERE visitor_id=? AND product_id IN ($in)");
    $stmt->execute(array_merge([$vid], $ids)); foreach ($stmt as $r) $liked[] = (int)$r['product_id'];

    $stmt = $pdo->prepare("SELECT product_id FROM wishlists WHERE visitor_id=? AND product_id IN ($in)");
    $stmt->execute(array_merge([$vid], $ids)); foreach ($stmt as $r) $wished[] = (int)$r['product_id'];

    $stmt = $pdo->prepare("SELECT product_id,stars FROM visitor_ratings WHERE visitor_id=? AND product_id IN ($in)");
    $stmt->execute(array_merge([$vid], $ids)); foreach ($stmt as $r) $rated[$r['product_id']] = (int)$r['stars'];

    $stmt = $pdo->prepare("SELECT product_id FROM visitor_views WHERE visitor_id=? AND product_id IN ($in)");
    $stmt->execute(array_merge([$vid], $ids)); foreach ($stmt as $r) $viewed[] = (int)$r['product_id'];

    foreach ($products as &$p) {
        $pid = (int)$p['id'];
        $p['id']           = $pid;
        $p['price_usd']    = (float)$p['price_usd'];
        $p['min_qty']      = (int)$p['min_qty'];
        $p['piece_weight'] = (int)$p['piece_weight'];
        $p['has_discount'] = (bool)$p['has_discount'];
        $p['is_clearance'] = (bool)$p['is_clearance'];
        $p['views']        = (int)$p['views'];
        $p['likes']        = (int)$p['likes'];
        $p['rating_sum']   = (int)$p['rating_sum'];
        $p['rating_count'] = (int)$p['rating_count'];
        $p['fomo_left']    = (int)$p['fomo_left'];
        $p['discount_percent'] = (int)$p['discount_percent'];
        $p['img']    = $p['main_image'] ?: 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=600&q=80';
        $p['extras'] = $imgs[$pid] ?? [];
        $p['comments'] = $cmts[$pid] ?? [];
        $p['isLiked']  = in_array($pid, $liked, true);
        $p['isWished'] = in_array($pid, $wished, true);
        $p['myRating'] = $rated[$pid] ?? 0;
        $p['isViewed'] = in_array($pid, $viewed, true);
        unset($p['main_image']);
    }
    unset($p);
}

json_response(['products' => $products]);
