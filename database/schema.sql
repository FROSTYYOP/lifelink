-- ==============================================================================
-- LifeLink: Smart Blood Donor & Emergency Assistance System
-- Database Schema & Comprehensive Demo Seed Data
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `lifelink` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lifelink`;

-- ------------------------------------------------------------------------------
-- 1. Table: users
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `donor_responses`;
DROP TABLE IF EXISTS `blood_requests`;
DROP TABLE IF EXISTS `donors`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'donor', 'user') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table: donors
-- ------------------------------------------------------------------------------
CREATE TABLE `donors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `address` TEXT,
  `age` INT NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `last_donation_date` DATE DEFAULT NULL,
  `availability_status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
  `verified_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_donor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table: blood_requests
-- ------------------------------------------------------------------------------
CREATE TABLE `blood_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requester_name` VARCHAR(100) NOT NULL,
  `requester_contact` VARCHAR(20) NOT NULL,
  `blood_group` ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `hospital_name` VARCHAR(150) NOT NULL,
  `units_needed` INT NOT NULL DEFAULT 1,
  `urgency` ENUM('normal', 'critical') NOT NULL DEFAULT 'normal',
  `status` ENUM('open', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'open',
  `user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Table: donor_responses (Responses/Pledges to Blood Requests)
-- ------------------------------------------------------------------------------
CREATE TABLE `donor_responses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `donor_id` INT NOT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pledged', 'contacted', 'completed', 'declined') NOT NULL DEFAULT 'pledged',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_response_request` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_response_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Table: notifications
-- ------------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- SEED DATA
-- Default Passwords:
-- Admin:   Admin@123   (Hash: $2y$10$ZCpyNHugf4LL334EbibIDeNqdcuPstZu4vH5Ykau/Enet2jRf4nN6)
-- Donors:  Donor@123   (Hash: $2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq)
-- Users:   User@123    (Hash: $2y$10$l8vN2QVD1PMRQi6NJfD64e/oiGFKhlTVGO8rOhPOR8xdBdiANNor2)
-- ==============================================================================

-- 1. Insert Users
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin@lifelink.com', '+91 98765 43210', '$2y$10$ZCpyNHugf4LL334EbibIDeNqdcuPstZu4vH5Ykau/Enet2jRf4nN6', 'admin', NOW()),
(2, 'John Doe (Verified Donor)', 'john@lifelink.com', '+91 98111 22334', '$2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq', 'donor', NOW()),
(3, 'Sarah Jenkins', 'sarah@lifelink.com', '+91 98222 33445', '$2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq', 'donor', NOW()),
(4, 'David Wilson', 'david@lifelink.com', '+91 98333 44556', '$2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq', 'donor', NOW()),
(5, 'Priya Sharma', 'priya@lifelink.com', '+91 98444 55667', '$2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq', 'donor', NOW()),
(6, 'Michael Chen (Pending)', 'mike@lifelink.com', '+91 98555 66778', '$2y$10$aSB6Xf2CJDH8MGp//bkuV.ZqYWlms1txqbCfOlUUiGRH0EBciuUAq', 'donor', NOW()),
(7, 'Alice Brown (Regular User)', 'user@lifelink.com', '+91 98666 77889', '$2y$10$l8vN2QVD1PMRQi6NJfD64e/oiGFKhlTVGO8rOhPOR8xdBdiANNor2', 'user', NOW());

-- 2. Insert Donors
INSERT INTO `donors` (`id`, `user_id`, `blood_group`, `city`, `address`, `age`, `gender`, `last_donation_date`, `availability_status`, `verified_status`, `created_at`) VALUES
(1, 2, 'A+', 'Mumbai', 'Flat 402, Sea Breeze Apts, Bandra West', 28, 'Male', '2026-05-15', 'available', 'verified', NOW()),
(2, 3, 'O+', 'Pune', '12 Sunshine Park, Kothrud', 24, 'Female', '2026-06-10', 'available', 'verified', NOW()),
(3, 4, 'B-', 'Bengaluru', '45 Green Glen Layout, Bellandur', 32, 'Male', '2026-03-20', 'available', 'verified', NOW()),
(4, 5, 'AB+', 'Delhi', 'B-14 Hauz Khas Enclave', 27, 'Female', '2026-07-01', 'available', 'verified', NOW()),
(5, 6, 'O-', 'Mumbai', 'Plot 88, Central Avenue, Powai', 29, 'Male', NULL, 'available', 'pending', NOW());

-- 3. Insert Blood Requests
INSERT INTO `blood_requests` (`id`, `requester_name`, `requester_contact`, `blood_group`, `city`, `hospital_name`, `units_needed`, `urgency`, `status`, `user_id`, `created_at`) VALUES
(1, 'Ramesh Gupta', '+91 91234 56789', 'O-', 'Mumbai', 'Lilavati Hospital & Research Centre', 2, 'critical', 'open', 7, NOW()),
(2, 'Anita Desai', '+91 92345 67890', 'A+', 'Mumbai', 'Kokilaben Dhirubhai Ambani Hospital', 1, 'normal', 'open', NULL, NOW()),
(3, 'Rajesh Patel', '+91 93456 78901', 'B-', 'Bengaluru', 'Manipal Hospital, Old Airport Rd', 3, 'critical', 'open', NULL, NOW()),
(4, 'Sunita Roy', '+91 94567 89012', 'O+', 'Pune', 'Ruby Hall Clinic, Sassoon Road', 1, 'normal', 'fulfilled', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- 4. Insert Donor Responses
INSERT INTO `donor_responses` (`id`, `request_id`, `donor_id`, `message`, `status`, `created_at`) VALUES
(1, 1, 1, 'I am in Mumbai and ready to donate at Lilavati Hospital immediately.', 'pledged', NOW());

-- 5. Insert Notifications
INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Urgent: A critical request for O- blood was posted near your city (Lilavati Hospital).', 'emergency', 0, NOW()),
(2, 2, 'Welcome to LifeLink! Your donor profile has been verified by the administrator.', 'verification', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 6, 'Your donor profile is under review by the LifeLink Medical Board.', 'verification', 0, NOW()),
(4, 1, 'New donor Michael Chen registered and is awaiting verification.', 'admin', 0, NOW());
