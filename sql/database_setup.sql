-- versogym_full_schema.sql
-- Complete schema for VersoGym (includes updates and improvements)
-- Run this entire file on your MySQL/MariaDB server.
-- Adjust DEFAULT CHARSET/collation if needed.

-- =====================================================
-- DATABASE SETUP
-- =====================================================
CREATE DATABASE IF NOT EXISTS versogym 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

USE versogym;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oauth_provider VARCHAR(32) NOT NULL DEFAULT 'local',
    oauth_uid VARCHAR(255) DEFAULT NULL,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    picture VARCHAR(512) DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    age SMALLINT UNSIGNED DEFAULT NULL,
    gender VARCHAR(32) DEFAULT NULL,
    fitness_goals TEXT DEFAULT NULL,
    membership_status VARCHAR(32) DEFAULT 'none',
    role VARCHAR(32) DEFAULT 'customer',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ux_users_email (email),
    INDEX idx_oauth (oauth_provider, oauth_uid),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- TRAINERS TABLE (New: For booking trainer sessions)
-- =====================================================
CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    picture VARCHAR(512) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Trainers
INSERT IGNORE INTO trainers (name, specialty, bio, picture) VALUES
('Allynah Mendoza', 'Strength Training', 'Expert in weightlifting with 10 years experience.', 'img/trainer1.jpg'),
('Shin Jiro Tenebro', 'Yoga & Flexibility', 'Certified yoga instructor focusing on mind-body balance.', 'img/trainer2.jpg');

-- =====================================================
-- WORKOUTS TABLE (Appointments / Bookings) - Updated with trainer_id
-- =====================================================
CREATE TABLE IF NOT EXISTS workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trainer_id INT DEFAULT NULL,  -- New: Link to trainer for sessions
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    type VARCHAR(64) DEFAULT 'Other',
    recurring TINYINT DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'Pending',  -- Changed default to Pending for new bookings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- MEMBERSHIPS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(128) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(128) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    first_name VARCHAR(128) DEFAULT NULL,
    last_name VARCHAR(128) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    payment_method VARCHAR(64) DEFAULT NULL,
    payment_status VARCHAR(64) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  icon VARCHAR(50) DEFAULT 'notifications',
  category VARCHAR(50) DEFAULT '',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FEED POSTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS feed_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(512) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- CHAT MESSAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (from_user_id, to_user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- CONTACT MESSAGES TABLE (From contact form)
-- =====================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- LOGIN HISTORY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SAMPLE WORKOUT ENTRY (Updated to use trainer_id)
-- =====================================================

-- =====================================================
-- END OF SCHEMA
-- =====================================================

