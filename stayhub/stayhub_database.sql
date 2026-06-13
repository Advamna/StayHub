-- ============================================================
--  StayHub -- Single Unified MySQL Database
--  MySQL 5.7+ / MariaDB / XAMPP compatible
--  Safe to run multiple times (uses IF NOT EXISTS everywhere)
-- ============================================================

CREATE DATABASE IF NOT EXISTS stayhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stayhub;

-- ============================================================
--  TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(100)  UNIQUE NOT NULL,
    phone       VARCHAR(20),
    password    VARCHAR(255)  NOT NULL,
    is_host     TINYINT(1)    DEFAULT 0,
    is_admin    TINYINT(1)    DEFAULT 0,
    is_banned   TINYINT(1)    DEFAULT 0,
    ban_reason  VARCHAR(255),
    avatar      VARCHAR(255),
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS listings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT           NOT NULL,
    title       VARCHAR(200)  NOT NULL,
    description TEXT,
    location    VARCHAR(100)  NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    bedrooms    INT           DEFAULT 1,
    bathrooms   INT           DEFAULT 1,
    max_guests  INT           DEFAULT 1,
    bed_count   INT           DEFAULT 1,
    status      ENUM('active','inactive','suspended') DEFAULT 'active',
    is_flagged  TINYINT(1)    DEFAULT 0,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT  NOT NULL,
    image_url   TEXT NOT NULL,
    is_primary  TINYINT(1) DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS amenities (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT         NOT NULL,
    name        VARCHAR(50) NOT NULL,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reservations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT           NOT NULL,
    user_id     INT,
    guest_name  VARCHAR(100)  NOT NULL,
    guest_email VARCHAR(100)  NOT NULL,
    guest_phone VARCHAR(20)   NOT NULL,
    check_in    DATE          NOT NULL,
    check_out   DATE          NOT NULL,
    guests      INT           NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    expires_at  DATETIME      NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT           NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50)   NOT NULL,
    payment_status  ENUM('pending','completed','failed','refunded') DEFAULT 'completed',
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS invoices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    payment_id      INT           NOT NULL,
    invoice_number  VARCHAR(100)  UNIQUE NOT NULL,
    tax_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    issued_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    listing_id      INT      NOT NULL,
    user_id         INT      NOT NULL,
    reservation_id  INT      NOT NULL,
    rating          TINYINT  NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title           VARCHAR(255),
    comment         TEXT,
    photos          TEXT,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    is_featured     TINYINT(1) DEFAULT 0,
    host_reply      TEXT,
    host_replied_at DATETIME,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id)     REFERENCES listings(id)     ON DELETE CASCADE,
    FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE NO ACTION,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE NO ACTION
);

CREATE TABLE IF NOT EXISTS wishlists (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    UNIQUE KEY uq_wishlist (user_id, listing_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT  NOT NULL,
    title       VARCHAR(200),
    message     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
--  SEED DATA
-- ============================================================

INSERT IGNORE INTO users (id, name, email, phone, password, is_host) VALUES
(1, 'Ahmed Bennani', 'ahmed@example.com', '+212612345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 'Fatima Zahra',  'fatima@example.com', '+212623456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

INSERT IGNORE INTO listings (id, user_id, title, description, location, price, bedrooms, bathrooms, max_guests, bed_count, status) VALUES
(1,  1, 'Appartement Moderne a Casablanca', 'Vue sur mer. Appartement de luxe avec acces direct a la plage.', 'Casablanca, Morocco', 450, 1, 1, 4, 2, 'active'),
(2,  2, 'Villa avec Piscine a Marrakech', 'Magnifique villa avec piscine privee au coeur de la palmeraie.', 'Marrakech, Morocco', 1200, 3, 2, 8, 4, 'active'),
(3,  1, 'Luxury Riad in the Medina of Fez', 'Step into centuries of history in this beautifully restored riad located in the heart of the ancient medina. Featuring hand-painted Zellige tiles, a lush courtyard garden with a central fountain, and rooftop terrace with panoramic views of the old city.', 'Fez, Morocco', 890, 2, 1, 4, 2, 'active'),
(4,  2, 'Beachfront Penthouse in Agadir', 'Wake up to the sound of waves in this sun-soaked penthouse directly on Agadir Beach. The private terrace overlooks the Atlantic Ocean and the city''s golden coastline. Fully equipped modern kitchen, two king bedrooms, and 24/7 concierge service.', 'Agadir, Morocco', 1450, 2, 2, 5, 3, 'active'),
(5,  1, 'Mountain Chalet near Oukaïmeden', 'Escape the city heat in this cozy alpine chalet nestled in the Atlas Mountains. Ski access in winter, hiking and stargazing in summer. Sleeps up to 8 guests.', 'Oukaïmeden, Morocco', 630, 3, 2, 8, 4, 'active'),
(6,  2, 'Designer Apartment in Gueliz, Marrakech', 'A sleek and stylish apartment in the trendy Gueliz district, just steps from the best restaurants, boutiques, and art galleries. Minimalist interior, fast WiFi, private balcony.', 'Marrakech, Morocco', 550, 1, 1, 3, 2, 'active'),
(7,  1, 'Traditional Kasbah Stay in Ouarzazate', 'Experience authentic Moroccan hospitality in a real kasbah on the edge of the Sahara gateway city. Solar-powered, eco-friendly, and unforgettable.', 'Ouarzazate, Morocco', 480, 2, 1, 5, 3, 'active'),
(8,  2, 'Oceanfront Villa in Essaouira', 'A stunning whitewashed villa with direct beach access. Surf the Atlantic in the morning, explore the UNESCO medina in the afternoon, dine under the stars on your private seafront patio.', 'Essaouira, Morocco', 1100, 3, 2, 7, 4, 'active'),
(9,  1, 'Modern Studio near Ain Diab, Casablanca', 'A compact and stylish studio in the upscale Ain Diab neighborhood. Walking distance to the Hassan II Mosque and the Corniche. Perfect for solo travelers or business visitors.', 'Casablanca, Morocco', 320, 1, 1, 2, 1, 'active'),
(10, 2, 'Luxury Desert Camp in Merzouga', 'Fall asleep to the silence of the Sahara in this exclusive luxury camp near the golden dunes of Erg Chebbi. Camel treks at sunset, Berber music around the fire, and a sky full of stars.', 'Merzouga, Morocco', 1800, 0, 1, 3, 2, 'active');

INSERT IGNORE INTO images (listing_id, image_url, is_primary) VALUES
(1,  'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 1),
(2,  'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800', 1),
(3,  'https://images.unsplash.com/photo-1567521464027-f127ff144326?w=800', 1),
(4,  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', 1),
(5,  'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800', 1),
(6,  'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800', 1),
(7,  'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800', 1),
(8,  'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800', 1),
(9,  'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800', 1),
(10, 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800', 1);

INSERT IGNORE INTO amenities (listing_id, name) VALUES
(1, 'WiFi'), (1, 'Climatisation'),
(2, 'Piscine'), (2, 'WiFi'),
(3, 'WiFi'), (3, 'Traditional Hammam'), (3, 'Rooftop Terrace'), (3, 'Air Conditioning'),
(4, 'WiFi'), (4, 'Private Pool'), (4, 'Ocean View'), (4, 'Concierge'),
(5, 'WiFi'), (5, 'Fireplace'), (5, 'Ski Access'), (5, 'Mountain View'),
(6, 'WiFi'), (6, 'Kitchen'), (6, 'Balcony'), (6, 'Workspace'),
(7, 'WiFi'), (7, 'Rooftop Terrace'), (7, 'Solar Power'), (7, 'Desert View'),
(8, 'WiFi'), (8, 'Beach Access'), (8, 'Private Patio'), (8, 'Surf Boards'),
(9, 'WiFi'), (9, 'Air Conditioning'), (9, 'Kitchen'), (9, 'Fast Internet'),
(10, 'WiFi'), (10, 'Camel Trek'), (10, 'Stargazing'), (10, 'Berber Breakfast');

INSERT IGNORE INTO reservations (id, listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status) VALUES
(1, 3, 2, 'Fatima Zahra', 'fatima@example.com', '+212623456789', '2026-07-10', '2026-07-15', 2, 4450.00, 'confirmed');

INSERT IGNORE INTO payments (id, reservation_id, amount, payment_method, payment_status) VALUES
(1, 1, 4450.00, 'Card', 'completed');

INSERT IGNORE INTO invoices (id, payment_id, invoice_number, tax_amount, total_amount) VALUES
(1, 1, 'INV-2026-0001', 445.00, 4895.00);

INSERT IGNORE INTO reviews (listing_id, user_id, reservation_id, rating, title, comment, status) VALUES
(3, 2, 1, 5, 'Absolutely magical stay!', 'The riad was breathtaking. Would come back in a heartbeat.', 'approved');

INSERT IGNORE INTO notifications (user_id, title, message) VALUES
(1, 'New Reservation', 'You have a new confirmed reservation for Luxury Riad in the Medina of Fez.'),
(2, 'Booking Confirmed', 'Your reservation at Luxury Riad in the Medina of Fez has been confirmed.');

-- ============================================================
--  Done! 10 tables, all data loaded.
-- ============================================================
