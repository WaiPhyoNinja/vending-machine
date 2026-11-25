CREATE DATABASE IF NOT EXISTS vending_testing
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vending_testing;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Admin', 'User') NOT NULL DEFAULT 'User',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  price DECIMAL(10, 3) NOT NULL,
  quantity_available INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_products_positive_price CHECK (price > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(10, 3) NOT NULL,
  total_amount DECIMAL(12, 3) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_transactions_created_at (created_at),
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_transactions_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT chk_transactions_quantity CHECK (quantity > 0)
) ENGINE=InnoDB;

INSERT INTO products (name, slug, price, quantity_available)
SELECT 'Coke', 'coke', 3.990, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Coke');

INSERT INTO products (name, slug, price, quantity_available)
SELECT 'Pepsi', 'pepsi', 6.885, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Pepsi');

INSERT INTO products (name, slug, price, quantity_available)
SELECT 'Water', 'water', 0.500, 10
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Water');
