<?php
declare(strict_types=1);
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'bootstrap.php') { http_response_code(404); exit; }

$config = require __DIR__ . '/config.php';

if ($config['app']['env'] === 'production') {
    ini_set('display_errors','0'); ini_set('log_errors','1');
} else {
    ini_set('display_errors','1'); error_reporting(E_ALL);
}
date_default_timezone_set($config['app']['timezone']);
mb_internal_encoding('UTF-8');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db']['host'], $config['db']['name'], $config['db']['charset']),
        $config['db']['user'], $config['db']['pass'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
    );
} catch (PDOException $e) {
    error_log('DB: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'DB connection failed']); exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['app']['session_name']);
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>($_SERVER['HTTPS']??'')==='on','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

function json_response(array $d, int $s=200): void { http_response_code($s); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function json_error(string $m, int $s=400): void { json_response(['error'=>$m], $s); }
function require_admin(): void { if (empty($_SESSION['admin_id'])) json_error('Unauthorized', 401); }
function require_csrf(): void { $t=$_SERVER['HTTP_X_CSRF_TOKEN']??''; if (!$t || !hash_equals($_SESSION['csrf_token']??'', $t)) json_error('Invalid CSRF', 403); }
function issue_csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }

function get_visitor_id(PDO $pdo, array $cfg): string {
    $name = $cfg['app']['visitor_cookie'];
    $vid  = $_COOKIE[$name] ?? '';
    if (is_string($vid) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $vid)) return $vid;
    $b = random_bytes(16);
    $vid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b),4));
    setcookie($name, $vid, ['expires'=>time()+86400*$cfg['app']['visitor_cookie_days'],'path'=>'/','secure'=>($_SERVER['HTTPS']??'')==='on','httponly'=>true,'samesite'=>'Lax']);
    $pdo->prepare('INSERT IGNORE INTO visitors (id) VALUES (?)')->execute([$vid]);
    return $vid;
}

function bump_store_version(PDO $pdo): void {
    $pdo->exec("UPDATE settings SET setting_value = CAST(setting_value AS UNSIGNED)+1 WHERE setting_key='store_version'");
}
function get_setting(PDO $pdo, string $k, ?string $d=null): ?string {
    $stmt=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=?'); $stmt->execute([$k]);
    $v=$stmt->fetchColumn(); return $v===false ? $d : $v;
}
function set_setting(PDO $pdo, string $k, string $v): void {
    $pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$k,$v]);
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
