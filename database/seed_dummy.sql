-- Seed dummy data for dashboard stats
-- Run this in your MySQL for the MieTime database.

-- Users (10 unique contributors)
INSERT INTO users (username, email, password_hash, role, created_at)
VALUES
  ('andi', 'andi@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 180 DAY)),
  ('budi', 'budi@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 165 DAY)),
  ('citra', 'citra@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 150 DAY)),
  ('dimas', 'dimas@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 135 DAY)),
  ('eka', 'eka@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 120 DAY)),
  ('fajar', 'fajar@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 105 DAY)),
  ('gina', 'gina@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 90 DAY)),
  ('hadi', 'hadi@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 75 DAY)),
  ('intan', 'intan@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 60 DAY)),
  ('joni', 'joni@example.com', '$2y$10$wR0t2o5xqF3u8V3FZQj1E.OPmD8W7v3B6lWb3bDq8FJxKZk7G3b1K', 'contributor', DATE_SUB(NOW(), INTERVAL 45 DAY))
ON DUPLICATE KEY UPDATE email=email;

-- Locations (10 unique approved)
INSERT INTO locations (name, address, status, created_at)
VALUES
  ('Mie Ayam Enak', 'Jl. Kenangan No. 1, Jakarta', 'active', DATE_SUB(NOW(), INTERVAL 160 DAY)),
  ('Bakso & Mie Ayam Mantap', 'Jl. Sudirman No. 10, Bandung', 'active', DATE_SUB(NOW(), INTERVAL 145 DAY)),
  ('Warung Mie Ayam Sejahtera', 'Jl. Malioboro No. 5, Yogyakarta', 'active', DATE_SUB(NOW(), INTERVAL 130 DAY)),
  ('Kedai Mie Nusantara', 'Jl. Diponegoro No. 12, Surabaya', 'active', DATE_SUB(NOW(), INTERVAL 115 DAY)),
  ('Mie Ayam Bahagia', 'Jl. Ahmad Yani No. 8, Semarang', 'active', DATE_SUB(NOW(), INTERVAL 100 DAY)),
  ('Warung Mie Maknyus', 'Jl. Gatot Subroto No. 3, Medan', 'active', DATE_SUB(NOW(), INTERVAL 85 DAY)),
  ('Mie Ayam Solo Rasa', 'Jl. Slamet Riyadi No. 20, Solo', 'active', DATE_SUB(NOW(), INTERVAL 70 DAY)),
  ('Mie Ayam Pojok', 'Jl. Pajajaran No. 15, Bogor', 'active', DATE_SUB(NOW(), INTERVAL 55 DAY)),
  ('Mie Ayam Juara', 'Jl. Asia Afrika No. 2, Bandung', 'active', DATE_SUB(NOW(), INTERVAL 40 DAY)),
  ('Mie Ayam Legenda', 'Jl. Veteran No. 7, Malang', 'active', DATE_SUB(NOW(), INTERVAL 25 DAY))
ON DUPLICATE KEY UPDATE name=name;

-- Map emails to IDs
SET @u1 = (SELECT user_id FROM users WHERE email='andi@example.com');
SET @u2 = (SELECT user_id FROM users WHERE email='budi@example.com');
SET @u3 = (SELECT user_id FROM users WHERE email='citra@example.com');
SET @u4 = (SELECT user_id FROM users WHERE email='dimas@example.com');
SET @u5 = (SELECT user_id FROM users WHERE email='eka@example.com');
SET @u6 = (SELECT user_id FROM users WHERE email='fajar@example.com');
SET @u7 = (SELECT user_id FROM users WHERE email='gina@example.com');
SET @u8 = (SELECT user_id FROM users WHERE email='hadi@example.com');
SET @u9 = (SELECT user_id FROM users WHERE email='intan@example.com');
SET @u10 = (SELECT user_id FROM users WHERE email='joni@example.com');

SET @l1 = (SELECT location_id FROM locations WHERE name='Mie Ayam Enak' ORDER BY location_id DESC LIMIT 1);
SET @l2 = (SELECT location_id FROM locations WHERE name='Bakso & Mie Ayam Mantap' ORDER BY location_id DESC LIMIT 1);
SET @l3 = (SELECT location_id FROM locations WHERE name='Warung Mie Ayam Sejahtera' ORDER BY location_id DESC LIMIT 1);
SET @l4 = (SELECT location_id FROM locations WHERE name='Kedai Mie Nusantara' ORDER BY location_id DESC LIMIT 1);
SET @l5 = (SELECT location_id FROM locations WHERE name='Mie Ayam Bahagia' ORDER BY location_id DESC LIMIT 1);
SET @l6 = (SELECT location_id FROM locations WHERE name='Warung Mie Maknyus' ORDER BY location_id DESC LIMIT 1);
SET @l7 = (SELECT location_id FROM locations WHERE name='Mie Ayam Solo Rasa' ORDER BY location_id DESC LIMIT 1);
SET @l8 = (SELECT location_id FROM locations WHERE name='Mie Ayam Pojok' ORDER BY location_id DESC LIMIT 1);
SET @l9 = (SELECT location_id FROM locations WHERE name='Mie Ayam Juara' ORDER BY location_id DESC LIMIT 1);
SET @l10 = (SELECT location_id FROM locations WHERE name='Mie Ayam Legenda' ORDER BY location_id DESC LIMIT 1);

-- Reviews spread over ~6 months (60 rows)
INSERT INTO reviews (user_id, location_id, rating, review_text, upvotes, status, created_at)
VALUES
  (@u1, @l1, 5, 'Review dummy untuk pengujian statistik.', 12, 'approved', DATE_SUB(NOW(), INTERVAL 175 DAY)),
  (@u2, @l2, 4, 'Review dummy untuk pengujian statistik.', 8,  'approved', DATE_SUB(NOW(), INTERVAL 170 DAY)),
  (@u3, @l3, 5, 'Review dummy untuk pengujian statistik.', 20, 'approved', DATE_SUB(NOW(), INTERVAL 165 DAY)),
  (@u4, @l4, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 160 DAY)),
  (@u5, @l5, 5, 'Review dummy untuk pengujian statistik.', 15, 'approved', DATE_SUB(NOW(), INTERVAL 155 DAY)),
  (@u6, @l6, 3, 'Review dummy untuk pengujian statistik.', 3,  'approved', DATE_SUB(NOW(), INTERVAL 150 DAY)),
  (@u7, @l7, 4, 'Review dummy untuk pengujian statistik.', 9,  'approved', DATE_SUB(NOW(), INTERVAL 145 DAY)),
  (@u8, @l8, 5, 'Review dummy untuk pengujian statistik.', 18, 'approved', DATE_SUB(NOW(), INTERVAL 140 DAY)),
  (@u9, @l9, 4, 'Review dummy untuk pengujian statistik.', 7,  'approved', DATE_SUB(NOW(), INTERVAL 135 DAY)),
  (@u10, @l10, 5, 'Review dummy untuk pengujian statistik.', 11, 'approved', DATE_SUB(NOW(), INTERVAL 130 DAY)),
  (@u1, @l2, 4, 'Review dummy untuk pengujian statistik.', 5,  'approved', DATE_SUB(NOW(), INTERVAL 125 DAY)),
  (@u2, @l3, 5, 'Review dummy untuk pengujian statistik.', 14, 'approved', DATE_SUB(NOW(), INTERVAL 120 DAY)),
  (@u3, @l4, 4, 'Review dummy untuk pengujian statistik.', 10, 'approved', DATE_SUB(NOW(), INTERVAL 115 DAY)),
  (@u4, @l5, 3, 'Review dummy untuk pengujian statistik.', 2,  'approved', DATE_SUB(NOW(), INTERVAL 110 DAY)),
  (@u5, @l6, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 105 DAY)),
  (@u6, @l7, 5, 'Review dummy untuk pengujian statistik.', 13, 'approved', DATE_SUB(NOW(), INTERVAL 100 DAY)),
  (@u7, @l8, 4, 'Review dummy untuk pengujian statistik.', 7,  'approved', DATE_SUB(NOW(), INTERVAL 95 DAY)),
  (@u8, @l9, 5, 'Review dummy untuk pengujian statistik.', 17, 'approved', DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (@u9, @l10, 4, 'Review dummy untuk pengujian statistik.', 4,  'approved', DATE_SUB(NOW(), INTERVAL 85 DAY)),
  (@u10, @l1, 5, 'Review dummy untuk pengujian statistik.', 12, 'approved', DATE_SUB(NOW(), INTERVAL 80 DAY)),
  (@u1, @l3, 4, 'Review dummy untuk pengujian statistik.', 5,  'approved', DATE_SUB(NOW(), INTERVAL 75 DAY)),
  (@u2, @l4, 5, 'Review dummy untuk pengujian statistik.', 16, 'approved', DATE_SUB(NOW(), INTERVAL 70 DAY)),
  (@u3, @l5, 4, 'Review dummy untuk pengujian statistik.', 9,  'approved', DATE_SUB(NOW(), INTERVAL 65 DAY)),
  (@u4, @l6, 3, 'Review dummy untuk pengujian statistik.', 2,  'approved', DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (@u5, @l7, 4, 'Review dummy untuk pengujian statistik.', 8,  'approved', DATE_SUB(NOW(), INTERVAL 55 DAY)),
  (@u6, @l8, 5, 'Review dummy untuk pengujian statistik.', 19, 'approved', DATE_SUB(NOW(), INTERVAL 50 DAY)),
  (@u7, @l9, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 45 DAY)),
  (@u8, @l10, 5, 'Review dummy untuk pengujian statistik.', 15, 'approved', DATE_SUB(NOW(), INTERVAL 40 DAY)),
  (@u9, @l1, 4, 'Review dummy untuk pengujian statistik.', 7,  'approved', DATE_SUB(NOW(), INTERVAL 35 DAY)),
  (@u10, @l2, 5, 'Review dummy untuk pengujian statistik.', 10, 'approved', DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (@u1, @l4, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 27 DAY)),
  (@u2, @l5, 5, 'Review dummy untuk pengujian statistik.', 12, 'approved', DATE_SUB(NOW(), INTERVAL 24 DAY)),
  (@u3, @l6, 3, 'Review dummy untuk pengujian statistik.', 1,  'approved', DATE_SUB(NOW(), INTERVAL 21 DAY)),
  (@u4, @l7, 4, 'Review dummy untuk pengujian statistik.', 7,  'approved', DATE_SUB(NOW(), INTERVAL 18 DAY)),
  (@u5, @l8, 5, 'Review dummy untuk pengujian statistik.', 14, 'approved', DATE_SUB(NOW(), INTERVAL 15 DAY)),
  (@u6, @l9, 4, 'Review dummy untuk pengujian statistik.', 5,  'approved', DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (@u7, @l10, 5, 'Review dummy untuk pengujian statistik.', 11, 'approved', DATE_SUB(NOW(), INTERVAL 9 DAY)),
  (@u8, @l1, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (@u9, @l2, 5, 'Review dummy untuk pengujian statistik.', 13, 'approved', DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (@u10, @l3, 4, 'Review dummy untuk pengujian statistik.', 4,  'approved', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (@u1, @l5, 5, 'Review dummy untuk pengujian statistik.', 12, 'approved', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (@u2, @l6, 4, 'Review dummy untuk pengujian statistik.', 6,  'approved', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Optional: badges count context (matches schema columns)
INSERT INTO badges (badge_name, badge_description, badge_icon, badge_type, trigger_condition)
VALUES
  ('Pencari Mie', 'Member aktif mencari kedai mie', 'fa-award', 'achievement', 'review_count >= 1'),
  ('Reviewer Hebat', 'Member rajin memberi ulasan', 'fa-star', 'achievement', 'review_count >= 10')
ON DUPLICATE KEY UPDATE badge_name=badge_name;

-- Note: password hash above corresponds to 'password123' using your HASH_ALGO settings if compatible.