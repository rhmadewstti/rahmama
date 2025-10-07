-- POS ATK MySQL schema & seed
CREATE DATABASE IF NOT EXISTS pos_atk CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE pos_atk;

CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  price INT NOT NULL,
  stock INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS customers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),
  phone VARCHAR(30)
);

CREATE TABLE IF NOT EXISTS orders (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_code VARCHAR(20) UNIQUE,
  customer_id INT NULL,
  cashier_id INT NOT NULL,
  subtotal INT NOT NULL,
  discount INT NOT NULL DEFAULT 0,
  total INT NOT NULL,
  payment_method ENUM('tunai','transfer','qris') NOT NULL,
  payment_ref VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL,
  price INT NOT NULL,
  line_total INT NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  whatsapp_phone VARCHAR(20),
  qris_label VARCHAR(100),
  qris_image_path VARCHAR(255)
);

-- Seed admin
INSERT INTO users (name, username, password_hash) VALUES
('Administrator','admin','$2b$10$jNGCmNlaHdpZYq3RIc/73.XAg.SMlLO5i5c2vYpk/syuAq7g4NbJG');

-- Seed products (ATK)
INSERT INTO products (name, price, stock) VALUES
('Buku Tulis (38 lembar)',33600,50),
('Pensil 2B',5000,200),
('Pensil Mekanik',3500,120),
('Pulpen Gel',5000,200),
('Penggaris 30cm',5000,100),
('Penghapus',5000,150),
('Map Plastik Kancing',3000,150),
('Gunting Joyko',7000,80);
