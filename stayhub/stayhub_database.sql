-- ============================================================
--  StayHub -- T-SQL Seed Script for SQL Server / SSMS
--  Run this against your [new_stayhub] database
--  All tables already exist -- this only inserts data
-- ============================================================

USE new_stayhub;
GO

-- ============================================================
--  USERS
-- ============================================================
SET IDENTITY_INSERT dbo.users ON;

INSERT INTO dbo.users (id, name, email, phone, password, is_host, is_admin, is_banned, ban_reason, avatar, created_at)
VALUES
(1, 'Ahmed Bennani', 'ahmed@example.com', '+212612345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 0, 0, NULL, NULL, GETDATE()),
(2, 'Fatima Zahra',  'fatima@example.com', '+212623456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 0, 0, NULL, NULL, GETDATE());

SET IDENTITY_INSERT dbo.users OFF;
GO

-- ============================================================
--  LISTINGS
-- ============================================================
SET IDENTITY_INSERT dbo.listings ON;

INSERT INTO dbo.listings (id, user_id, title, description, location, price, bedrooms, bathrooms, max_guests, bed_count, status, is_flagged, created_at)
VALUES
(1,  1, 'Appartement Moderne a Casablanca',
 'Vue sur mer. Appartement de luxe avec acces direct a la plage.',
 'Casablanca, Morocco', 450.00, 1, 1, 4, 2, 'active', 0, GETDATE()),

(2,  2, 'Villa avec Piscine a Marrakech',
 'Magnifique villa avec piscine privee au coeur de la palmeraie.',
 'Marrakech, Morocco', 1200.00, 3, 2, 8, 4, 'active', 0, GETDATE()),

(3,  1, 'Luxury Riad in the Medina of Fez',
 'Step into centuries of history in this beautifully restored riad. Hand-painted Zellige tiles, lush courtyard garden, rooftop terrace with panoramic views.',
 'Fez, Morocco', 890.00, 2, 1, 4, 2, 'active', 0, GETDATE()),

(4,  2, 'Beachfront Penthouse in Agadir',
 'Wake up to the sound of waves in this sun-soaked penthouse directly on Agadir Beach. Fully equipped modern kitchen, two king bedrooms, 24/7 concierge.',
 'Agadir, Morocco', 1450.00, 2, 2, 5, 3, 'active', 0, GETDATE()),

(5,  1, 'Mountain Chalet near Oukaïmeden',
 'Cozy alpine chalet in the Atlas Mountains. Fireplace, ski access in winter, hiking and stargazing in summer. Sleeps up to 8 guests.',
 'Oukaïmeden, Morocco', 630.00, 3, 2, 8, 4, 'active', 0, GETDATE()),

(6,  2, 'Designer Apartment in Gueliz, Marrakech',
 'Sleek apartment in trendy Gueliz district. Steps from the best restaurants and boutiques. Minimalist interior, fast WiFi, private balcony.',
 'Marrakech, Morocco', 550.00, 1, 1, 3, 2, 'active', 0, GETDATE()),

(7,  1, 'Traditional Kasbah Stay in Ouarzazate',
 'Authentic Moroccan hospitality in a real kasbah. Rooftop terrace with Atlas Mountain views. Solar-powered and eco-friendly.',
 'Ouarzazate, Morocco', 480.00, 2, 1, 5, 3, 'active', 0, GETDATE()),

(8,  2, 'Oceanfront Villa in Essaouira',
 'Whitewashed villa with direct beach access. Surf the Atlantic, explore the UNESCO medina, dine on your private seafront patio.',
 'Essaouira, Morocco', 1100.00, 3, 2, 7, 4, 'active', 0, GETDATE()),

(9,  1, 'Modern Studio near Ain Diab, Casablanca',
 'Stylish studio in upscale Ain Diab. Walking distance to Hassan II Mosque and the Corniche. Perfect for solo travelers or business visitors.',
 'Casablanca, Morocco', 320.00, 1, 1, 2, 1, 'active', 0, GETDATE()),

(10, 2, 'Luxury Desert Camp in Merzouga',
 'Exclusive luxury camp near the golden dunes of Erg Chebbi. Camel treks at sunset, Berber music around the fire, a sky full of stars.',
 'Merzouga, Morocco', 1800.00, 0, 1, 3, 2, 'active', 0, GETDATE());

SET IDENTITY_INSERT dbo.listings OFF;
GO

-- ============================================================
--  IMAGES
-- ============================================================
INSERT INTO dbo.images (listing_id, image_url, is_primary) VALUES
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

-- ============================================================
--  AMENITIES
-- ============================================================
INSERT INTO dbo.amenities (listing_id, name) VALUES
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
GO

-- ============================================================
--  RESERVATIONS
-- ============================================================
SET IDENTITY_INSERT dbo.reservations ON;

INSERT INTO dbo.reservations (id, listing_id, user_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, status, expires_at, created_at)
VALUES
(1, 3, 2, 'Fatima Zahra', 'fatima@example.com', '+212623456789', '2026-07-10', '2026-07-15', 2, 4450.00, 'confirmed', NULL, GETDATE());

SET IDENTITY_INSERT dbo.reservations OFF;
GO

-- ============================================================
--  PAYMENTS
-- ============================================================
SET IDENTITY_INSERT dbo.payments ON;

INSERT INTO dbo.payments (id, reservation_id, amount, payment_method, payment_status, created_at)
VALUES (1, 1, 4450.00, 'Card', 'completed', GETDATE());

SET IDENTITY_INSERT dbo.payments OFF;
GO

-- ============================================================
--  INVOICES
-- ============================================================
SET IDENTITY_INSERT dbo.invoices ON;

INSERT INTO dbo.invoices (id, payment_id, invoice_number, tax_amount, total_amount, issued_at)
VALUES (1, 1, 'INV-2026-0001', 445.00, 4895.00, GETDATE());

SET IDENTITY_INSERT dbo.invoices OFF;
GO

-- ============================================================
--  REVIEWS
-- ============================================================
INSERT INTO dbo.reviews (listing_id, user_id, reservation_id, rating, title, comment, status, is_featured, created_at)
VALUES (3, 2, 1, 5, 'Absolutely magical stay!', 'The riad was breathtaking. Would come back in a heartbeat.', 'approved', 0, GETDATE());
GO

-- ============================================================
--  NOTIFICATIONS
-- ============================================================
INSERT INTO dbo.notifications (user_id, title, message, is_read, created_at) VALUES
(1, 'New Reservation', 'You have a new confirmed reservation for Luxury Riad in the Medina of Fez.', 0, GETDATE()),
(2, 'Booking Confirmed', 'Your reservation at Luxury Riad in the Medina of Fez has been confirmed.', 0, GETDATE());
GO

-- ============================================================
--  Done! All data loaded into new_stayhub.
-- ============================================================
