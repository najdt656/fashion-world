<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
require __DIR__ . '/../src/bootstrap.php';

$email = getenv('ADMIN_EMAIL') ?: '';
$pass  = getenv('ADMIN_PASS')  ?: '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Invalid ADMIN_EMAIL\n");
if (strlen($pass) < 10) exit("ADMIN_PASS too short (min 10)\n");

$hash = password_hash($pass, PASSWORD_BCRYPT);
try {
    $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)')->execute([$email, $hash]);
    echo "✅ Admin created: $email\n";
} catch (PDOException $e) {
    echo $e->getCode()==='23000' ? "Admin exists.\n" : "Error: ".$e->getMessage()."\n";
}
