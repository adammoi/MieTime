-- Database Schema untuk Mie Time
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS mie_time CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mie_time;

-- Tabel Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator', 'contributor', 'verified_owner') DEFAULT 'contributor',
    review_count INT DEFAULT 0,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_points (points DESC),
    INDEX idx_review_count (review_count DESC)
) ENGINE=InnoDB;

-- Tabel Locations (Kedai Mie)
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    owner_user_id INT NULL,
    average_rating DECIMAL(3, 2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    status ENUM('active', 'pending_approval') DEFAULT 'pending_approval',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_rating (average_rating DESC),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB;

-- Tabel Reviews
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    status ENUM('approved', 'pending', 'rejected') DEFAULT 'pending',
    moderation_reason VARCHAR(255) NULL,
    upvotes INT DEFAULT 0,
    downvotes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_location (location_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- Tabel Review Images
CREATE TABLE review_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    INDEX idx_review (review_id)
) ENGINE=InnoDB;

-- Tabel Review Votes (Upvote/Downvote)
CREATE TABLE review_votes (
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type TINYINT(1) NOT NULL COMMENT '1 = upvote, -1 = downvote',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Badges
CREATE TABLE badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    badge_name VARCHAR(100) NOT NULL,
    badge_description TEXT,
    badge_icon VARCHAR(255),
    badge_type ENUM('participation', 'achievement') NOT NULL,
    trigger_condition VARCHAR(255) NOT NULL COMMENT 'e.g., review_count >= 10',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel User Badges (Many-to-Many)
CREATE TABLE user_badges (
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB;

-- Tabel Bookmarks
CREATE TABLE bookmarks (
    user_id INT NOT NULL,
    location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, location_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Claim Requests (untuk verified owner)
CREATE TABLE claim_requests (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    location_id INT NOT NULL,
    proof_document VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Insert Default Badges
INSERT INTO badges (badge_name, badge_description, badge_icon, badge_type, trigger_condition) VALUES
('Cicipan Pertama', 'Menulis review pertama', 'first-review.png', 'participation', 'review_count >= 1'),
('Fotografer Mie', 'Mengunggah foto pertama dengan review', 'photographer.png', 'participation', 'has_photo = 1'),
('Juru Cicip', 'Menulis 10 review', 'taster.png', 'participation', 'review_count >= 10'),
('Pakar Mie', 'Menulis 50 review', 'expert.png', 'participation', 'review_count >= 50'),
('Ahli Pangsit', 'Mendapat 10 upvotes di satu review', 'pangsit-expert.png', 'achievement', 'single_review_upvotes >= 10'),
('Kritikus Terpercaya', 'Total 100 upvotes di semua review', 'trusted-critic.png', 'achievement', 'total_upvotes >= 100'),
('Penjelajah', 'Review di 3 kota berbeda', 'explorer.png', 'participation', 'unique_cities >= 3'),
('Pendiri', 'Pengguna awal platform', 'founder.png', 'participation', 'user_id <= 100');

-- Trigger: Update denormalized fields pada tabel locations setelah review baru
DELIMITER //
CREATE TRIGGER after_review_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE locations 
        SET total_reviews = total_reviews + 1,
            average_rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE location_id = NEW.location_id AND status = 'approved'
            )
        WHERE location_id = NEW.location_id;
    END IF;
END//

-- Trigger: Update denormalized fields ketika review status berubah
CREATE TRIGGER after_review_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        UPDATE locations 
        SET total_reviews = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE location_id = NEW.location_id AND status = 'approved'
            ),
            average_rating = (
                SELECT COALESCE(AVG(rating), 0) 
                FROM reviews 
                WHERE location_id = NEW.location_id AND status = 'approved'
            )
        WHERE location_id = NEW.location_id;
    END IF;
END//

-- Trigger: Update vote counts
CREATE TRIGGER after_vote_insert
AFTER INSERT ON review_votes
FOR EACH ROW
BEGIN
    IF NEW.vote_type = 1 THEN
        UPDATE reviews SET upvotes = upvotes + 1 WHERE review_id = NEW.review_id;
    ELSE
        UPDATE reviews SET downvotes = downvotes + 1 WHERE review_id = NEW.review_id;
    END IF;
END//

CREATE TRIGGER after_vote_update
AFTER UPDATE ON review_votes
FOR EACH ROW
BEGIN
    IF OLD.vote_type = 1 AND NEW.vote_type = -1 THEN
        UPDATE reviews SET upvotes = upvotes - 1, downvotes = downvotes + 1 WHERE review_id = NEW.review_id;
    ELSEIF OLD.vote_type = -1 AND NEW.vote_type = 1 THEN
        UPDATE reviews SET downvotes = downvotes - 1, upvotes = upvotes + 1 WHERE review_id = NEW.review_id;
    END IF;
END//

DELIMITER ;

-- Insert Admin Default (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@mietime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');