-- ============================================================
--  Beyond The Style - database
--  Import with phpMyAdmin (Import tab) or:  mysql -u root < database.sql
--  Database name: beyond_the_style
-- ============================================================

CREATE DATABASE IF NOT EXISTS `beyond_the_style`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `beyond_the_style`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `contact_messages`, `wishlist`, `reviews`, `order_items`, `orders`,
  `cart`, `product_images`, `products`, `categories`, `admins`, `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Customers ----------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(20)  NOT NULL DEFAULT '',
  `address`       VARCHAR(255) NOT NULL DEFAULT '',
  `city`          VARCHAR(100) NOT NULL DEFAULT '',
  `state`         VARCHAR(100) NOT NULL DEFAULT '',
  `postal_code`   VARCHAR(12)  NOT NULL DEFAULT '',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Admins ----------
CREATE TABLE `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Catalogue ----------
CREATE TABLE `categories` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80)  NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`    INT UNSIGNED NOT NULL,
  `name`           VARCHAR(150) NOT NULL,
  `gender`         ENUM('men','women','unisex') NOT NULL DEFAULT 'unisex',
  `description`    TEXT NOT NULL,
  `price`          DECIMAL(10,2) NOT NULL,
  `discount_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `image`          VARCHAR(255) NOT NULL DEFAULT '',
  `sizes`          VARCHAR(100) NOT NULL DEFAULT 'One Size',
  `colors`         VARCHAR(150) NOT NULL DEFAULT 'Standard',
  `stock`          INT UNSIGNED NOT NULL DEFAULT 0,
  `sales_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_gender` (`gender`),
  KEY `idx_products_created` (`created_at`),
  KEY `idx_products_name` (`name`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image`      VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Cart (logged-in customers; guests use the PHP session) ----------
CREATE TABLE `cart` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `size`       VARCHAR(30) NOT NULL DEFAULT '',
  `color`      VARCHAR(40) NOT NULL DEFAULT '',
  `quantity`   INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_line` (`user_id`,`product_id`,`size`,`color`),
  KEY `idx_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Orders ----------
CREATE TABLE `orders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NULL,
  `full_name`      VARCHAR(100) NOT NULL,
  `email`          VARCHAR(150) NOT NULL,
  `phone`          VARCHAR(20)  NOT NULL,
  `address`        VARCHAR(255) NOT NULL,
  `city`           VARCHAR(100) NOT NULL,
  `state`          VARCHAR(100) NOT NULL,
  `postal_code`    VARCHAR(12)  NOT NULL,
  `total`          DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(40) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `status`         ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED NOT NULL,
  `product_id`   INT UNSIGNED NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `image`        VARCHAR(255) NOT NULL DEFAULT '',
  `price`        DECIMAL(10,2) NOT NULL,
  `size`         VARCHAR(30) NOT NULL DEFAULT '',
  `color`        VARCHAR(40) NOT NULL DEFAULT '',
  `quantity`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Reviews / wishlist / contact ----------
CREATE TABLE `reviews` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,
  `comment`    VARCHAR(1000) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reviews_user_product` (`user_id`,`product_id`),
  KEY `idx_reviews_product` (`product_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wishlist` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist` (`user_id`,`product_id`),
  KEY `idx_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `subject`    VARCHAR(150) NOT NULL,
  `message`    TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Sample data
-- ============================================================

-- Admin login:    username  admin        password  Admin@123   (change after first login!)
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$bY81d6DIZzLxJuW.GlnzS.rfXW6t/PF7gJnsKfmrNF3sDpFEdey0W');

-- Demo customer:  demo@beyondthestyle.com   password  Demo@1234
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `address`, `city`, `state`, `postal_code`) VALUES
('Demo Customer', 'demo@beyondthestyle.com', '$2y$10$K5bA9a3OZqaKD8S0FFl8Uu7vKWV4QnhfakHS0APIVIpY0yIAmTVPq',
 '9876543210', '12 MG Road', 'Bengaluru', 'Karnataka', '560001');

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
(1, 'Jackets',      'jackets'),
(2, 'T-Shirts',     't-shirts'),
(3, 'Tops',         'tops'),
(4, 'Sweatshirts',  'sweatshirts'),
(5, 'Skirts',       'skirts'),
(6, 'Dresses',      'dresses'),
(7, 'Sets',         'sets'),
(8, 'Footwear',     'footwear');

INSERT INTO `products`
(`id`,`category_id`,`name`,`gender`,`description`,`price`,`discount_price`,`image`,`sizes`,`colors`,`stock`,`sales_count`) VALUES
(1, 1, 'Olive Utility Bomber Jacket', 'men',
 'A relaxed, heavyweight bomber jacket with a contrast hooded layer and a sturdy zip front. Roomy pockets and a washed olive finish make it an easy everyday layer.',
 4999.00, 3999.00, 'images/m bwn jacket.jpg', 'M,L,XL,XXL', 'Olive', 15, 24),
(2, 2, 'Striped Crew-Neck T-Shirt', 'men',
 'Soft cotton tee with a classic fine stripe and ribbed crew neck. Pick your colour and wear it with anything.',
 1299.00, NULL, 'images/M tshirts.jpg', 'S,M,L,XL', 'Green,Red,Brown,Blue', 40, 58),
(3, 8, 'Glow-in-the-Dark High-Top Sneakers', 'unisex',
 'Black and white high-top sneakers with a luminous glow accent that lights up after dark. Cushioned sole, padded ankle, true-to-size fit.',
 7499.00, 6499.00, 'images/light shoes.jpg', '6,7,8,9,10,11', 'Black/White', 12, 41),
(4, 3, 'Honey Gathered Blouse', 'women',
 'A flowing blouse in a warm honey tone with a gathered neckline and balloon sleeves. Light, breathable and effortlessly elegant.',
 1899.00, NULL, 'images/l tops.jpeg', 'XS,S,M,L', 'Honey', 25, 17),
(5, 4, 'Embroidered Pocket Sweatshirt', 'women',
 'A layered-look sweatshirt with a soft black body, floral embroidery, a patch pocket and striped balloon sleeves.',
 2799.00, 2299.00, 'images/l sweatshirt.jpeg', 'S,M,L,XL', 'Black/Brown', 18, 22),
(6, 5, 'Layered Blossom Skirt', 'women',
 'A statement skirt with an asymmetric camel overlay and a pleated ivory underlayer, finished with delicate blossom embroidery.',
 3499.00, NULL, 'images/l skirt.jpeg', 'S,M,L', 'Camel/Ivory', 10, 12),
(7, 6, 'Trendy Pleated Maxi Dress', 'women',
 'Everyday elegance in a long pleated chocolate-brown maxi, styled with a cream button-up. Comfortable high waist with a flattering flare.',
 2999.00, 2499.00, 'images/L trd dress.jpeg', 'XS,S,M,L,XL', 'Brown', 20, 33),
(8, 7, 'Bow Co-ord Set', 'women',
 'Playful pink co-ord set: an oversized bow-print tee with drawstring trousers finished with side bows.',
 2499.00, NULL, 'images/l set.jpeg', 'S,M,L', 'Pink', 30, 29),
(9, 6, 'Floral Evening Gown', 'women',
 'A show-stopping gown with a navy bodice and a full tulle skirt covered in large hand-placed floral appliqués.',
 8999.00, 7499.00, 'images/l floral.jpeg', 'S,M,L', 'Navy Floral', 6, 9);

INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `comment`) VALUES
(1, 1, 5, 'Great fit and the fabric feels really sturdy.'),
(7, 1, 4, 'Lovely drape. Runs slightly long, which I liked.');
