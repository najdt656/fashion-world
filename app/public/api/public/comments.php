<?php
require __DIR__ . '/../../../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed', 405);

$vid = get_visitor_id($pdo, $config);
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$pid = (int)($body['product_id'] ?? 0);
$author = trim((string)($body['author'] ?? 'تاجر معتمد'));
$text   = trim((string)($body['text'] ?? ''));
if ($pid<=0 || $text==='') json_error('Missing fields');
if (mb_strlen($text) > 1000) json_error('Comment too long');
if ($author==='') $author='تاجر معتمد';

// حد بسيط: 5 تعليقات/دقيقة/زائر
$stmt=$pdo->prepare("SELECT COUNT(*) FROM product_comments c JOIN visitors v ON v.id=? WHERE c.product_id=? AND c.created_at > NOW()-INTERVAL 1 MINUTE");
$stmt->execute([$vid,$pid]);
if ((int)$stmt->fetchColumn() >= 5) json_error('Too many comments, slow down', 429);

$pdo->prepare('INSERT INTO product_comments (product_id,author,body) VALUES (?,?,?)')->execute([$pid,$author,$text]);
json_response(['ok'=>true,'id'=>(int)$pdo->lastInsertId()]);
