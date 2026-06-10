-- StayHub MySQL Database Schema (XAMPP compatible)
CREATE DATABASE IF NOT EXISTS stayhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stayhub;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    is_host TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    ban_reason VARCHAR(255),
    avatar LONGBLOB,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    bedrooms INT DEFAULT 1,
    bathrooms INT DEFAULT 1,
    guests INT DEFAULT 1,
    voyageur_count INT DEFAULT 1,
    bed_count INT DEFAULT 1,
    rating DECIMAL(2,1) DEFAULT 0,
    reviews INT DEFAULT 0,
    is_flagged TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_url TEXT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100) NOT NULL,
    guest_phone VARCHAR(20) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);


-- ── listings.status column (active/inactive) ──────────────────────
-- (Add this if you already ran database.sql without it)
-- ALTER TABLE listings ADD status VARCHAR(20) DEFAULT 'active';

CREATE TABLE IF NOT EXISTS reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    listing_id      INT NOT NULL,
    user_id         INT NOT NULL,
    reservation_id  INT NOT NULL,
    rating          TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment         TEXT,
    host_reply      TEXT,
    host_replied_at DATETIME,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id)     REFERENCES listings(id)     ON DELETE CASCADE,
    FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE NO ACTION,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS wishlists (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    listing_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    UNIQUE KEY uq_wishlist (user_id, listing_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(200),
    message    TEXT NOT NULL,
    is_read    TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (name, email, phone, password, is_host) VALUES
('Ahmed Bennani', 'ahmed@example.com', '+212612345678', '\\\.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Fatima Zahra', 'fatima@example.com', '+212623456789', '\\\.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

INSERT INTO listings (user_id, title, description, location, price, voyageur_count, bed_count) VALUES
(1, 'Appartement Moderne a Casablanca', 'Vue sur mer. Appartement de luxe avec acces direct a la plage.', 'Casablanca, Morocco', 450, 4, 2),
(2, 'Villa avec Piscine a Marrakech', 'Magnifique villa avec piscine privee au coeur de la palmeraie.', 'Marrakech, Morocco', 1200, 8, 4);

INSERT INTO images (listing_id, image_url, is_primary) VALUES
(1, 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 1),
(2, 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800', 1);

INSERT INTO amenities (listing_id, name) VALUES
(1, 'WiFi'), (1, 'Climatisation'),
(2, 'Piscine'), (2, 'WiFi');
