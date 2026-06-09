-- ============================================================
-- StayHub — Add More Listings Script
-- Run this in SQL Server Management Studio or phpMyAdmin
-- against the [stayhub] database.
-- ============================================================
USE stayhub;

-- ── 1. Ensure seed hosts exist (skip if already inserted) ──
-- The two original hosts (id=1, id=2) are assumed to exist already.

-- ── 2. INSERT LISTINGS ──
INSERT INTO listings (user_id, title, description, location, price, bedrooms, bathrooms, guests, voyageur_count, bed_count, rating, reviews)
VALUES
-- Listing 3
(1,
 'Luxury Riad in the Medina of Fez',
 'Step into centuries of history in this beautifully restored riad located in the heart of the ancient medina. Featuring hand-painted Zellige tiles, a lush courtyard garden with a central fountain, and rooftop terrace with panoramic views of the old city. Ideal for couples and culture lovers.',
 'Fez, Morocco',
 890,  2, 1, 4, 4, 2, 4.9, 128),

-- Listing 4
(2,
 'Beachfront Penthouse in Agadir',
 'Wake up to the sound of waves in this sun-soaked penthouse directly on Agadir Beach. The private terrace overlooks the Atlantic Ocean and the city''s golden coastline. Fully equipped modern kitchen, two king bedrooms, and 24/7 concierge service make this the ultimate beach escape.',
 'Agadir, Morocco',
 1450, 2, 2, 5, 5, 3, 4.8, 204),

-- Listing 5
(1,
 'Mountain Chalet near Oukaïmeden',
 'Escape the city heat in this cozy alpine chalet nestled in the Atlas Mountains. The fireplace, wood-panelled walls, and stunning mountain views create the perfect retreat year-round. Ski access in winter; hiking and stargazing in summer. Sleeps up to 8 guests.',
 'Oukaïmeden, Morocco',
 630,  3, 2, 8, 8, 4, 4.7, 87),

-- Listing 6
(2,
 'Designer Apartment in Gueliz, Marrakech',
 'A sleek and stylish apartment in the trendy Gueliz district of Marrakech, just steps from the best restaurants, boutiques, and art galleries. Minimalist interior design, fast WiFi, a fully-fitted kitchen, and a private balcony overlooking the palm-lined avenue.',
 'Marrakech, Morocco',
 550,  1, 1, 3, 3, 2, 4.6, 310),

-- Listing 7
(1,
 'Traditional Kasbah Stay in Ouarzazate',
 'Experience authentic Moroccan hospitality in a real kasbah on the edge of the Sahara gateway city. The rooftop terrace offers breathtaking views of the Atlas Mountains and the famous film studios. Solar-powered, eco-friendly, and unforgettable.',
 'Ouarzazate, Morocco',
 480,  2, 1, 5, 5, 3, 4.8, 155),

-- Listing 8
(2,
 'Oceanfront Villa in Essaouira',
 'A stunning whitewashed villa with direct beach access in the windy, artistic city of Essaouira. Surf the Atlantic waves in the morning, explore the UNESCO-listed medina in the afternoon, and dine under the stars on your private seafront patio.',
 'Essaouira, Morocco',
 1100, 3, 2, 7, 7, 4, 4.9, 91),

-- Listing 9
(1,
 'Modern Studio near Ain Diab, Casablanca',
 'A compact and stylish studio apartment in the upscale Ain Diab neighborhood of Casablanca. Walking distance to the Hassan II Mosque and the Corniche. Perfect for solo travelers or business visitors looking for comfort, speed, and great connectivity.',
 'Casablanca, Morocco',
 320,  1, 1, 2, 2, 1, 4.5, 278),

-- Listing 10
(2,
 'Luxury Desert Camp in Merzouga',
 'Fall asleep to the silence of the Sahara in this exclusive luxury desert camp near the golden dunes of Erg Chebbi. Camel treks at sunset, traditional Berber music around the fire, and a sky full of stars. A once-in-a-lifetime experience that stays with you forever.',
 'Merzouga, Morocco',
 1800, 0, 1, 3, 3, 2, 5.0, 62);

-- ── 3. INSERT IMAGES ──
DECLARE @l3 INT = (SELECT id FROM listings WHERE title = 'Luxury Riad in the Medina of Fez');
DECLARE @l4 INT = (SELECT id FROM listings WHERE title = 'Beachfront Penthouse in Agadir');
DECLARE @l5 INT = (SELECT id FROM listings WHERE title = 'Mountain Chalet near Oukaïmeden');
DECLARE @l6 INT = (SELECT id FROM listings WHERE title = 'Designer Apartment in Gueliz, Marrakech');
DECLARE @l7 INT = (SELECT id FROM listings WHERE title = 'Traditional Kasbah Stay in Ouarzazate');
DECLARE @l8 INT = (SELECT id FROM listings WHERE title = 'Oceanfront Villa in Essaouira');
DECLARE @l9 INT = (SELECT id FROM listings WHERE title = 'Modern Studio near Ain Diab, Casablanca');
DECLARE @l10 INT= (SELECT id FROM listings WHERE title = 'Luxury Desert Camp in Merzouga');

INSERT INTO images (listing_id, image_url, is_primary) VALUES
(@l3,  'https://images.unsplash.com/photo-1567521464027-f127ff144326?w=800', 1),
(@l4,  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', 1),
(@l5,  'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800', 1),
(@l6,  'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800', 1),
(@l7,  'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=800', 1),
(@l8,  'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800', 1),
(@l9,  'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800', 1),
(@l10, 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800', 1);

-- ── 4. INSERT AMENITIES ──
INSERT INTO amenities (listing_id, name) VALUES
(@l3,  'WiFi'), (@l3,  'Traditional Hammam'), (@l3,  'Rooftop Terrace'), (@l3,  'Air Conditioning'),
(@l4,  'WiFi'), (@l4,  'Private Pool'),       (@l4,  'Ocean View'),      (@l4,  'Concierge'),
(@l5,  'WiFi'), (@l5,  'Fireplace'),           (@l5,  'Ski Access'),      (@l5,  'Mountain View'),
(@l6,  'WiFi'), (@l6,  'Kitchen'),             (@l6,  'Balcony'),         (@l6,  'Workspace'),
(@l7,  'WiFi'), (@l7,  'Rooftop Terrace'),     (@l7,  'Solar Power'),     (@l7,  'Desert View'),
(@l8,  'WiFi'), (@l8,  'Beach Access'),        (@l8,  'Private Patio'),   (@l8,  'Surf Boards'),
(@l9,  'WiFi'), (@l9,  'Air Conditioning'),    (@l9,  'Kitchen'),         (@l9,  'Fast Internet'),
(@l10, 'WiFi'), (@l10, 'Camel Trek'),          (@l10, 'Stargazing'),      (@l10, 'Berber Breakfast');
