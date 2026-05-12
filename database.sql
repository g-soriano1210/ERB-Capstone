-- CvSU ERB Submission System Database
-- Run this SQL in your MySQL/MariaDB server

CREATE DATABASE IF NOT EXISTS cvsu_erb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cvsu_erb;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('researcher', 'reviewer', 'admin') NOT NULL DEFAULT 'researcher',
    college VARCHAR(255),
    designation VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Submissions table
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tracking_number VARCHAR(32) NOT NULL UNIQUE,
    proponent_name VARCHAR(255) NOT NULL,
    college VARCHAR(255) NOT NULL,
    designation VARCHAR(255) NOT NULL,
    submission_type ENUM('initial','review_student','review_funding','resubmission') NOT NULL,
    status ENUM('pending','under_review','approved','rejected','revision_needed') DEFAULT 'pending',
    privacy_agreed TINYINT(1) NOT NULL DEFAULT 0,
    assigned_reviewer_id INT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewer_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Uploaded files for submissions
CREATE TABLE IF NOT EXISTS submission_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    file_field VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
);

-- Notifications / email log
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    submission_id INT,
    subject VARCHAR(255),
    message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (submission_id) REFERENCES submissions(id)
);

-- Default admin user (password: Admin@CvSU2024)
INSERT IGNORE INTO users (email, password_hash, full_name, role, is_verified)
VALUES (
    'admin@cvsu.edu.ph',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System Administrator',
    'admin',
    1
);

-- Migration: add assigned_reviewer_id if upgrading from old schema
-- ALTER TABLE submissions ADD COLUMN IF NOT EXISTS assigned_reviewer_id INT NULL;
-- ALTER TABLE submissions ADD FOREIGN KEY (assigned_reviewer_id) REFERENCES users(id) ON DELETE SET NULL;
