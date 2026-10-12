-- =====================================================
-- Custom Case Co. - Complete Database
-- Import via phpMyAdmin (Import tab)
-- WARNING: drops and recreates the database
-- =====================================================

DROP DATABASE IF EXISTS custom_case_co;
CREATE DATABASE custom_case_co
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE custom_case_co;

-- =====================================================
-- 1. ROLES & USERS
-- =====================================================
CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE,          -- 'customer', 'admin'
  description VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id INT UNSIGNED NOT NULL DEFAULT 1,   -- 1 = customer
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(30) NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  first_discount_used TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id)
    REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 2. CATALOG: categories, brands, models
-- =====================================================
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE phone_brands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE phone_models (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_brand_model (brand_id, name),
  CONSTRAINT fk_models_brand FOREIGN KEY (brand_id)
    REFERENCES phone_brands(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 3. PRODUCTS (ready-made cases)
-- =====================================================
CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  phone_model_id INT UNSIGNED NULL,          -- NULL = universal / not model-specific
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,                   -- file name only
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_product_price CHECK (price >= 0),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_products_model FOREIGN KEY (phone_model_id)
    REFERENCES phone_models(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_products_active (is_active),
  INDEX idx_products_name (name)
) ENGINE=InnoDB;

-- =====================================================
-- 4. CUSTOMIZATION
-- =====================================================
-- Base colors / styles offered in the designer
CREATE TABLE case_designs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  color_hex VARCHAR(7) NOT NULL,
  extra_price DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- A customer's saved custom case
CREATE TABLE custom_designs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  phone_model_id INT UNSIGNED NOT NULL,
  case_design_id INT UNSIGNED NOT NULL,
  uploaded_image VARCHAR(255) NULL,          -- file name only, stored in /uploads/custom/
  image_x DECIMAL(5,2) NOT NULL DEFAULT 50.00,      -- % position in preview
  image_y DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  image_scale DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  custom_text VARCHAR(60) NULL,
  text_color VARCHAR(7) NOT NULL DEFAULT '#ffffff',
  text_font VARCHAR(50) NOT NULL DEFAULT 'Poppins',
  text_x DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  text_y DECIMAL(5,2) NOT NULL DEFAULT 80.00,
  price DECIMAL(10,2) NOT NULL,              -- price at the time of design
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cd_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cd_model FOREIGN KEY (phone_model_id)
    REFERENCES phone_models(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_cd_style FOREIGN KEY (case_design_id)
    REFERENCES case_designs(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 5. CART
-- =====================================================
CREATE TABLE carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,      -- one cart per user
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Each row is EITHER a product OR a custom design (enforced in PHP)
CREATE TABLE cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  custom_design_id INT UNSIGNED NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  UNIQUE KEY uq_cart_design (cart_id, custom_design_id),
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id)
    REFERENCES carts(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ci_design FOREIGN KEY (custom_design_id)
    REFERENCES custom_designs(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 6. ORDERS, ITEMS, PAYMENTS, TRACKING
-- =====================================================
CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(20) NOT NULL UNIQUE,  -- e.g. CCC-20260921-0001
  user_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('pending','processing','shipped','delivered','cancelled')
    NOT NULL DEFAULT 'pending',
  shipping_name VARCHAR(100) NOT NULL,
  shipping_phone VARCHAR(30) NOT NULL,
  shipping_address VARCHAR(255) NOT NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_orders_status (status),
  INDEX idx_orders_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  custom_design_id INT UNSIGNED NULL,
  item_name VARCHAR(200) NOT NULL,           -- snapshot at purchase time
  unit_price DECIMAL(10,2) NOT NULL,         -- snapshot at purchase time
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  line_total DECIMAL(10,2) NOT NULL,         -- unit_price * quantity
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id)
    REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_oi_design FOREIGN KEY (custom_design_id)
    REFERENCES custom_designs(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  method ENUM('cod','card','gcash','bank_transfer') NOT NULL DEFAULT 'cod',
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  transaction_ref VARCHAR(100) NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_payments_status (status)
) ENGINE=InnoDB;

-- Log of every status change (powers the tracking timeline)
CREATE TABLE order_status_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL,
  note VARCHAR(255) NULL,
  changed_by INT UNSIGNED NULL,              -- admin who made the change
  changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_osh_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_osh_user FOREIGN KEY (changed_by)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 7. SEED DATA
-- =====================================================
INSERT INTO roles (id, name, description) VALUES
(1, 'customer', 'Regular shopper'),
(2, 'admin',    'Store administrator');

INSERT INTO categories (name, description) VALUES
('Solid Colors',    'Clean single-color cases'),
('Patterns',        'Marble, floral, and graphic prints'),
('Protective',      'Heavy-duty drop protection'),
('Clear Cases',     'Transparent cases that show off your phone'),
('Limited Edition', 'Seasonal and special drops');

INSERT INTO phone_brands (name) VALUES
('Apple'), ('Samsung'), ('Google'), ('Xiaomi');

INSERT INTO phone_models (brand_id, name) VALUES
(1,'iPhone 15'), (1,'iPhone 15 Pro'), (1,'iPhone 14'),
(2,'Galaxy S24'), (2,'Galaxy S23'), (2,'Galaxy A54'),
(3,'Pixel 8'), (3,'Pixel 7'),
(4,'Xiaomi 13'), (4,'Redmi Note 12');

INSERT INTO case_designs (name, color_hex, extra_price) VALUES
('Lavender',        '#C4B5FD', 0.00),
('Blush Pink',      '#F9A8D4', 0.00),
('Classic White',   '#FFFFFF', 0.00),
('Midnight Purple', '#3B1F6B', 2.00),
('Clear Glow',      '#EDE9FE', 1.00);

INSERT INTO products
(category_id, phone_model_id, name, description, price, stock, is_featured) VALUES
(1, 1, 'Lavender Soft Case',   'Slim matte case in pastel lavender.',            14.99, 50, 1),
(1, 4, 'Blush Pink Case',      'Soft-touch pink case with raised edges.',        14.99, 40, 1),
(2, 2, 'Purple Marble Case',   'Glossy marble pattern in purple and white.',     17.99, 30, 1),
(3, 7, 'Midnight Shield Case', 'Dark purple heavy-duty protection.',             19.99, 25, 0),
(4, 3, 'Crystal Clear Case',   'Ultra-thin transparent case, yellowing-resistant.', 12.99, 60, 0),
(5, 5, 'Pastel Sunset Case',   'Limited gradient of pink and lavender.',         21.99, 15, 1);

-- Admin account is created by setup-admin.php (Module 2)
-- so the password is hashed securely.