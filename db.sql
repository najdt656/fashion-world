-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS fashion_world_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fashion_world_db;

-- جدول إعدادات المتجر العامة (سعر الصرف والغلاف)
CREATE TABLE IF NOT EXISTS store_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO store_settings (setting_key, setting_value) VALUES 
('exchange_rate', '15000'),
('banner_img', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=80')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- جدول الموديلات والقطع
CREATE TABLE IF NOT EXISTS products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price_usd DECIMAL(10, 2) NOT NULL,
    min_qty INT DEFAULT 10,
    pack_details VARCHAR(255) NOT NULL,
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    rating_sum INT DEFAULT 20,
    rating_count INT DEFAULT 4,
    has_discount TINYINT(1) DEFAULT 0,
    main_img LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الصور الإضافية لكل موديل
CREATE TABLE IF NOT EXISTS product_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT,
    extra_img LONGTEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- جدول التعليقات الحية للتجار
CREATE TABLE IF NOT EXISTS comments (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    author VARCHAR(100) NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- جدول الطلبيات المرسلة
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_name VARCHAR(255) NOT NULL,
    total_usd DECIMAL(10, 2) NOT NULL,
    order_details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
