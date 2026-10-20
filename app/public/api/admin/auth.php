<?php
require __DIR__ . '/../../../src/bootstrap.php';
$action = $_GET['action'] ?? '';

if ($action === 'status') {
    json_response(['authenticated'=>!empty($_SESSION['admin_id']),'csrf'=>issue_csrf_token()]);
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed',405);
    $b=json_decode(file_get_contents('php://input'),true)?:[];
    $email=trim((string)($b['email']??'')); $pass=(string)($b['password']??'');
    if ($email===''||$pass==='') json_error('Missing credentials');

    // rate limit
    $key='login_'.md5($email.'|'.($_SERVER['REMOTE_ADDR']??''));
    $_SESSION[$key]=$_SESSION[$key]??[]; $now=time();
    $_SESSION[$key]=array_values(array_filter($_SESSION[$key],fn($t)=>$t>$now-60));
    if (count($_SESSION[$key])>=5) json_error('Too many attempts',429);
    $_SESSION[$key][]=$now;

    $stmt=$pdo->prepare('SELECT id,password_hash FROM admins WHERE email=?'); $stmt->execute([$email]);
    $a=$stmt->fetch();
    if (!$a || !password_verify($pass,$a['password_hash'])) json_error('Invalid credentials',401);
    $_SESSION['admin_id']=(int)$a['id'];
    session_regenerate_id(true);
    json_response(['ok'=>true,'csrf'=>issue_csrf_token()]);
}

if ($action === 'logout') { unset($_SESSION['admin_id']); session_regenerate_id(true); json_response(['ok'=>true]); }
json_error('Unknown action');
