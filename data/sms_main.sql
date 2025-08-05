DROP DATABASE IF EXISTS sms_main;
CREATE DATABASE sms_main;
USE sms_main;

-- Stores all departments managed by the administration.
CREATE TABLE `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stores all users for the entire system: administrators, HODs, faculty, etc.
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_id` INT(11) NULL, -- NULL for administrators, set for all department users
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('administrator', 'hod', 'faculty', 'staff', 'student') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `password_updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(), -- For session invalidation on password change
  `failed_login_attempts` TINYINT(1) NOT NULL DEFAULT 0, -- For brute-force protection
  `lockout_until` TIMESTAMP NULL DEFAULT NULL, -- For brute-force protection
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default administrator
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Tezpur University', 'admin@sms.com', '$2y$10$HYMSe9vfs6kutWeJtgTYE.FIQYp/ydFqOThuHkwvamO.jQKnw3m5q', 'administrator');

