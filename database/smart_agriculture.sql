-- =========================================================
-- Smart Agriculture & Marketplace System - Database Schema
-- XAMPP / phpMyAdmin এ ইম্পোর্ট করুন
-- =========================================================

CREATE DATABASE IF NOT EXISTS smart_agriculture CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_agriculture;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    role ENUM('farmer','buyer','admin') NOT NULL DEFAULT 'buyer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    farmer_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    delivery_lat DECIMAL(10,7) DEFAULT NULL,
    delivery_lng DECIMAL(10,7) DEFAULT NULL,
    status ENUM('Pending','Accepted','Processing','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    current_lat DECIMAL(10,7) DEFAULT NULL,
    current_lng DECIMAL(10,7) DEFAULT NULL,
    location_updated_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fertilizer_calculations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    crop_type VARCHAR(100) NOT NULL,
    soil_type VARCHAR(100) NOT NULL,
    field_size DECIMAL(10,2) NOT NULL,
    moisture_level VARCHAR(50) NOT NULL,
    fertilizer_result VARCHAR(255) NOT NULL,
    water_result VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS disease_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    disease_name VARCHAR(150) NOT NULL,
    confidence DECIMAL(5,2) NOT NULL,
    symptoms TEXT,
    treatment TEXT,
    prevention TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ডিফল্ট অ্যাডমিন ইউজার (ইমেইল: admin@agri.com , পাসওয়ার্ড: admin123)
INSERT INTO users (name, email, password, role) VALUES
('অ্যাডমিন', 'admin@agri.com', '$2y$10$k0016Pbyf7lWTPg4sEpzL.AbX1bFPdScqZorQ6vSEQju0uTSpz8lq', 'admin');
