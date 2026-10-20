<?php
require __DIR__ . '/../../../src/bootstrap.php';
require_admin();
require_csrf();
if ($_SERVER['REQUEST_METHOD']!=='POST') json_error('Method not allowed',405);
if (empty($_FILES['file'])) json_error('No file');
$f=$_FILES['file'];
if ($f['size']>$config['app']['max_image_size']) json_error('Too large',413);
$finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($f['tmp_name']);
if (!isset($config['app']['allowed_mime'][$mime])) json_error('Invalid type',415);
$ext=$config['app']['allowed_mime'][$mime];
$name=bin2hex(random_bytes(16)).'.'.$ext;
$dir=$config['app']['uploads_path'].'/banners';
if(!is_dir($dir)) mkdir($dir,0755,true);
if(!move_uploaded_file($f['tmp_name'],"$dir/$name")) json_error('Upload failed',500);
json_response(['ok'=>true,'url'=>"/uploads/banners/$name"]);
