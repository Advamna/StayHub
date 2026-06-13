-- ============================================================
--  StayHub -- Single Unified SQL Server Database
--  T-SQL / SSMS compatible
--  Drop & recreate everything -- one file, one run
-- ============================================================

IF DB_ID('new_stayhub') IS NOT NULL
BEGIN
    ALTER DATABASE new_stayhub SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE new_stayhub;
END
GO

CREATE DATABASE new_stayhub;
GO

USE new_stayhub;
GO

-- ============================================================
--  TABLES
-- ============================================================

-- users
CREATE TABLE users (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(100)  UNIQUE NOT NULL,
    phone       VARCHAR(20),
    password    VARCHAR(255)  NOT NULL,
    is_host     BIT           DEFAULT 0,
    is_admin    BIT           DEFAULT 0,
    is_banned   BIT           DEFAULT 0,
    ban_reason  VARCHAR(255),
    avatar      VARCHAR(255),                          -- store file path/URL, not BLOB
    created_at  DATETIME      DEFAULT GETDATE(),
    updated_at  DATETIME      DEFAULT GETDATE()
);
GO

-- listings
CREATE TABLE listings (
    id              INT IDENTITY(1,1) PRIMARY KEY,
    user_id         INT           NOT NULL,
    title           VARCHAR(200)  NOT NULL,
    description     TEXT,
    location        VARCHAR(100)  NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    bedrooms        INT           DEFAULT 1,
    bathrooms       INT           DEFAULT 1,
    max_guests      INT           DEFAULT 1,           -- merged guests + voyageur_count
    bed_count       INT           DEFAULT 1,
    status          VARCHAR(10)   DEFAULT 'active' CHECK (status IN ('active','inactive','suspended')),
    is_flagged      BIT           DEFAULT 0,
    created_at      DATETIME      DEFAULT GETDATE(),
    updated_at      DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_listings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_listings_user   ON listings(user_id);
CREATE INDEX idx_listings_status ON listings(status);
GO

-- images
CREATE TABLE images (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    listing_id  INT           NOT NULL,
    image_url   TEXT          NOT NULL,
    is_primary  BIT           DEFAULT 0,
    CONSTRAINT FK_images_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_images_listing ON images(listing_id);
GO

-- amenities
CREATE TABLE amenities (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    listing_id  INT          NOT NULL,
    name        VARCHAR(50)  NOT NULL,
    CONSTRAINT FK_amenities_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_amenities_listing ON amenities(listing_id);
GO

-- reservations
CREATE TABLE reservations (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    listing_id  INT           NOT NULL,
    user_id     INT,                                   -- NULL = anonymous booking
    guest_name  VARCHAR(100)  NOT NULL,
    guest_email VARCHAR(100)  NOT NULL,
    guest_phone VARCHAR(20)   NOT NULL,
    check_in    DATE          NOT NULL,
    check_out   DATE          NOT NULL,
    guests      INT           NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status      VARCHAR(10)   DEFAULT 'pending' CHECK (status IN ('pending','confirmed','cancelled')),
    expires_at  DATETIME      NULL,                    -- pending reservation expiry (48h)
    created_at  DATETIME      DEFAULT GETDATE(),
    updated_at  DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_res_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT FK_res_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE NO ACTION
);
GO

CREATE INDEX idx_res_listing ON reservations(listing_id);
CREATE INDEX idx_res_user    ON reservations(user_id);
CREATE INDEX idx_res_expires ON reservations(status, expires_at);
GO

-- payments
CREATE TABLE payments (
    id              INT IDENTITY(1,1) PRIMARY KEY,
    reservation_id  INT           NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50)   NOT NULL,
    payment_status  VARCHAR(10)   DEFAULT 'completed' CHECK (payment_status IN ('pending','completed','failed','refunded')),
    created_at      DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_pay_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_pay_reservation ON payments(reservation_id);
GO

-- invoices
CREATE TABLE invoices (
    id              INT IDENTITY(1,1) PRIMARY KEY,
    payment_id      INT           NOT NULL,
    invoice_number  VARCHAR(100)  UNIQUE NOT NULL,
    tax_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    issued_at       DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_inv_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_inv_payment ON invoices(payment_id);
GO

-- reviews
CREATE TABLE reviews (
    id              INT IDENTITY(1,1) PRIMARY KEY,
    listing_id      INT           NOT NULL,
    user_id         INT           NOT NULL,
    reservation_id  INT           NOT NULL,
    rating          TINYINT       NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title           VARCHAR(255),
    comment         TEXT,
    photos          TEXT,                              -- JSON array of image URLs
    status          VARCHAR(10)   DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    is_featured     BIT           DEFAULT 0,
    host_reply      TEXT,
    host_replied_at DATETIME,
    created_at      DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_reviews_listing     FOREIGN KEY (listing_id)     REFERENCES listings(id)     ON DELETE CASCADE,
    CONSTRAINT FK_reviews_user        FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE NO ACTION,
    CONSTRAINT FK_reviews_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE NO ACTION
);
GO

CREATE INDEX idx_reviews_listing ON reviews(listing_id);
CREATE INDEX idx_reviews_user    ON reviews(user_id);
GO

-- wishlists
CREATE TABLE wishlists (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    user_id     INT  NOT NULL,
    listing_id  INT  NOT NULL,
    created_at  DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_wish_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT FK_wish_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE NO ACTION,
    CONSTRAINT uq_wishlist UNIQUE (user_id, listing_id)
);
GO

-- notifications
CREATE TABLE notifications (
    id          INT IDENTITY(1,1) PRIMARY KEY,
    user_id     INT           NOT NULL,
    title       VARCHAR(200),
    message     TEXT          NOT NULL,
    is_read     BIT           DEFAULT 0,
    created_at  DATETIME      DEFAULT GETDATE(),
    CONSTRAINT FK_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
GO

CREATE INDEX idx_notif_user ON notifications(user_id);
GO

-- ============================================================
--  TRIGGERS for updated_at (replicates ON UPDATE CURRENT_TIMESTAMP)
-- ============================================================

CREATE TRIGGER trg_users_updated_at ON users
AFTER UPDATE AS
BEGIN
    UPDATE users SET updated_at = GETDATE()
    WHERE id IN (SELECT id FROM inserted);
END
GO

CREATE TRIGGER trg_listings_updated_at ON listings
AFTER UPDATE AS
BEGIN
    UPDATE listings SET updated_at = GETDATE()
    WHERE id IN (SELECT id FROM inserted);
END
GO

CREATE TRIGGER trg_reservations_updated_at ON reservations
AFTER UPDATE AS
BEGIN
    UPDATE reservations SET updated_at = GETDATE()
    WHERE id IN (SELECT id FROM inserted);
END
GO

-- ============================================================
--  SEED DATA
-- ============================================================

-- users
INSERT INTO users (name, email, phone, password, is_host) VALUES
('Ahmed Bennani', 'ahmed@example.com', '+212612345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Fatima Zahra',  'fatima@example.com', '+212623456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
GO

-- listings
INSERT INTO listings (user_id, title, description, location, price, bedrooms, bathrooms, max_guests, bed_count, status) VALUES
(1, 'Appartement Moderne a Casablanca',
 'Vue sur mer. Appartement de luxe avec acces direct a la plage.',
 'Casablanca, Morocco', 450, 1, 1, 4, 2, 'active'),

(2, 'Villa avec Piscine a Marrakech',
 'Magnifique villa avec piscine privee au coeur de la palmeraie.',
 'Marrakech, Morocco', 1200, 3, 2, 8, 4, 'active'),

(1, 'Luxury Riad in the Medina of Fez',
 'Step into centuries of history in this beautifully restored riad located in the heart of the ancient medina. Featuring hand-painted Zellige tiles, a lush courtyard garden with a central fountain, and rooftop terrace with panoramic views of the old city. Ideal for couples and culture lovers.',
 'Fez, Morocco', 890, 2, 1, 4, 2, 'active'),

(2, 'Beachfront Penthouse in Agadir',
 'Wake up to the sound of waves in this sun-soaked penthouse directly on Agadir Beach. The private terrace overlooks the Atlantic Ocean and the city''s golden coastline. Fully equipped modern kitchen, two king bedrooms, and 24/7 concierge service make this the ultimate beach escape.',
 'Agadir, Morocco', 1450, 2, 2, 5, 3, 'active'),

(1, 'Mountain Chalet near Oukaimeden',
 'Escape the city heat in this cozy alpine chalet nestled in the Atlas Mountains. The fireplace, wood-panelled walls, and stunning mountain views create the perfect retreat year-round. Ski access in winter; hiking and stargazing in summer. Sleeps up to 8 guests.',
 'Oukaimeden, Morocco', 630, 3, 2, 8, 4, 'active'),

(2, 'Designer Apartment in Gueliz, Marrakech',
 'A sleek and stylish apartment in the trendy Gueliz district of Marrakech, just steps from the best restaurants, boutiques, and art galleries. Minimalist interior design, fast WiFi, a fully-fitted kitchen, and a private balcony overlooking the palm-lined avenue.',
 'Marrakech, Morocco', 550, 1, 1, 3, 2, 'active'),

(1, 'Traditional Kasbah Stay in Ouarzazate',
 'Experience authentic Moroccan hospitality in a real kasbah on the edge of the Sahara gateway city. The rooftop terrace offers breathtaking views of the Atlas Mountains and the famous film studios. Solar-powered, eco-friendly, and unforgettable.',
 'Ouarzazate, Morocco', 480, 2, 1, 5, 3, 'active'),

(2, 'Oceanfront Villa in Essaouira',
 'A stunning whitewashed villa with direct beach access in the windy, artistic city of Essaouira. Surf the Atlantic waves in the morning, explore the UNESCO-listed medina in the afternoon, and dine under the stars on your private seafront patio.',
 'Essaouira, Morocco', 1100, 3, 2, 7, 4, 'active'),

(1, 'Modern Studio near Ain Diab, Casablanca',
 'A compact and stylish studio apartment in the upscale Ain Diab neighborhood of Casablanca. Walking distance to the Hassan II Mosque and the Corniche. Perfect for solo travelers or business visitors looking for comfort, speed, and great connectivity.',
 'Casablanca, Morocco', 320, 1, 1, 2, 1, 'active'),

(2, 'Luxury Desert Camp in Merzouga',
 'Fall asleep to the silence of the Sahara in this exclusive luxury desert camp near the golden dunes of Erg Chebbi. Camel treks at sunset, traditional Berber music around the fire, and a sky full of stars. A once-in-a-lifetime experience that stays with you forever.',
 'Merzouga, Morocco', 1800, 0, 1, 3, 2, 'active');
GO

-- images
INSERT INTO images (listing_id, image_url, is_primary) VALUES
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
GO

-- amenities
INSERT INTO amenities (listing_id, name) VALUES
-- Listing 1 -- Casablanca Appartement
(1, 'WiFi'), (1, 'Climatisation'),
-- Listing 2 -- Villa Marrakech
(2, 'Piscine'), (2, 'WiFi'),
-- Listing 3 -- Riad Fez
(3, 'WiFi'), (3, 'Traditional Hammam'), (3, 'Rooftop Terrace'), (3, 'Air Conditioning'),
-- Listing 4 -- Penthouse Agadir
(4, 'WiFi'), (4, 'Private Pool'), (4, 'Ocean View'), (4, 'Concierge'),
-- Listing 5 -- Chalet Oukameden
(5, 'WiFi'), (5, 'Fireplace'), (5, 'Ski Access'), (5, 'Mountain View'),
-- Listing 6 -- Apartment Gueliz
(6, 'WiFi'), (6, 'Kitchen'), (6, 'Balcony'), (6, 'Workspace'),
-- Listing 7 -- Kasbah Ouarzazate
(7, 'WiFi'), (7, 'Rooftop Terrace'), (7, 'Solar Power'), (7, 'Desert View'),
-- Listing 8 -- Villa Essaouira
(8, 'WiFi'), (8, 'Beach Access'), (8, 'Private Patio'), (8, 'Surf Boards'),
-- Listing 9 -- Studio Casablanca
(9, 'WiFi'), (9, 'Air Conditioning'), (9, 'Kitchen'), (9, 'Fast Internet'),
-- Listing 10 -- Desert Camp Merzouga
(10, 'WiFi'), (10, 'Camel Trek'), (10, 'Stargazing'), (10, 'Berber Breakfast');
GO

-- sample reservation
INSERT INTO reservations (listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, expires_at) VALUES
(3, 2, 'Fatima Zahra', 'fatima@example.com', '+212623456789', '2026-07-10', '2026-07-15', 2, 4450.00, 'confirmed', NULL);
GO

-- sample payment
INSERT INTO payments (reservation_id, amount, payment_method, payment_status) VALUES
(1, 4450.00, 'Card', 'completed');
GO

-- sample invoice
INSERT INTO invoices (payment_id, invoice_number, tax_amount, total_amount) VALUES
(1, 'INV-2026-0001', 445.00, 4895.00);
GO

-- sample review
INSERT INTO reviews (listing_id, user_id, reservation_id, rating, title, comment, status) VALUES
(3, 2, 1, 5, 'Absolutely magical stay!', 'The riad was breathtaking -- the courtyard, the tiles, the rooftop view. Would come back in a heartbeat.', 'approved');
GO

-- sample notifications
INSERT INTO notifications (user_id, title, message) VALUES
(1, 'New Reservation', 'You have a new confirmed reservation for "Luxury Riad in the Medina of Fez".'),
(2, 'Booking Confirmed', 'Your reservation at Luxury Riad in the Medina of Fez has been confirmed.');
GO

-- ============================================================
--  Done! All tables created, all data loaded.
-- ============================================================