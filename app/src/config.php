<?php
return [
    'db' => [
        'host'    => getenv('DB_HOST') ?: 'db',
        'name'    => getenv('DB_NAME') ?: 'fashion_world',
        'user'    => getenv('DB_USER') ?: 'fw_user',
        'pass'    => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'env'                 => getenv('APP_ENV') ?: 'production',
        'timezone'            => getenv('TZ') ?: 'Asia/Damascus',
        'session_name'        => 'FW_ADMIN_SESS',
        'visitor_cookie'      => 'fw_vid',
        'visitor_cookie_days' => 365,
        'csrf_header'         => 'X-CSRF-Token',
        'uploads_path'        => __DIR__ . '/../public/uploads',
        'uploads_url'         => '/uploads',
        'max_image_size'      => 3 * 1024 * 1024,
        'allowed_mime'        => ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'],
        'login_max_attempts_per_min' => 5,
        'whatsapp'            => '963996311804',
    ],
];
