-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `student_monitoring_db`;
-- CREATE DATABASE IF NOT EXISTS `if0_39456570_student_monitoring_db`;

-- Use the database
USE `student_monitoring_db`;
-- USE `if0_39456570_student_monitoring_db`;

-- Drop tables in correct order to avoid foreign key constraints during re-creation
DROP TABLE IF EXISTS `faculty_student_associations`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `user_types`;

DROP TABLE IF EXISTS `batches`;
DROP TABLE IF EXISTS `programmes`;
DROP TABLE IF EXISTS `degree_levels`; -- New table to drop
DROP TABLE IF EXISTS `association_types`;


-- Table for user_types
CREATE TABLE IF NOT EXISTS `user_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for degree levels
CREATE TABLE IF NOT EXISTS `degree_levels` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for programmes
CREATE TABLE IF NOT EXISTS `programmes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `credit_required` INT NOT NULL,
    `minimum_year` INT NOT NULL,
    `maximum_year` INT NOT NULL,
    `degree_levels_id` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`degree_levels_id`) REFERENCES `degree_levels`(`id`)
);

-- Table for batches
CREATE TABLE IF NOT EXISTS `batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `programme_id` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`)
);

-- Table for user authentication
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `user_type_id` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_type_id`) REFERENCES `user_types`(`id`)
);

-- Table for student-specific details
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE NOT NULL,
    `roll_number` VARCHAR(100) UNIQUE NULL,
    `date_of_birth` DATE NULL,
    `current_address` VARCHAR(500) NULL,
    `permanent_address` VARCHAR(500) NULL,
    `phone_number_self` VARCHAR(20) NULL,
    `phone_number_guardian` VARCHAR(20) NULL,
    `programme_id` INT NULL,
    `batch_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`programme_id`) REFERENCES `programmes`(`id`),
    FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`)
);

-- Table for faculty-specific details
CREATE TABLE IF NOT EXISTS `faculty` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNIQUE NOT NULL,
    `phone_number` VARCHAR(20) UNIQUE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Table for types of faculty-student associations
CREATE TABLE IF NOT EXISTS `association_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for faculty-student associations
CREATE TABLE IF NOT EXISTS `faculty_student_associations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `faculty_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `association_type_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`association_type_id`) REFERENCES `association_types`(`id`),
    UNIQUE (`faculty_id`, `student_id`, `association_type_id`)
);

-- Initial Data Inserts
INSERT INTO `user_types` (`name`, `description`) VALUES
('admin', 'Head of Department or System Administrator'),
('staff', 'Temporary Administrator with limited access'),
('faculty', 'Faculty member with student association'),
('student', 'Enrolled student');

-- Insert initial degree levels
INSERT INTO `degree_levels` (`name`) VALUES
('UG'),
('PG'),
('PhD');
