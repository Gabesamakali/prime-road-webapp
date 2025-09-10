-- database_setup.sql
-- Complete database setup for Prime Roads application

-- Create database
CREATE DATABASE IF NOT EXISTS prime_roads;
USE prime_roads;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(500) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- User sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    user_id INT PRIMARY KEY,
    reset_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (reset_token),
    INDEX idx_expires (expires_at)
);

-- Login attempts table (for security monitoring)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, created_at),
    INDEX idx_ip_time (ip_address, created_at)
);

-- Road segments table
CREATE TABLE IF NOT EXISTS road_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_name VARCHAR(255) NOT NULL,
    segment_code VARCHAR(50) UNIQUE,
    condition_rating ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    length_km DECIMAL(10,2) NOT NULL,
    surface_type ENUM('asphalt', 'concrete', 'gravel', 'dirt') DEFAULT 'asphalt',
    location VARCHAR(500),
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    last_inspection DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_condition (condition_rating),
    INDEX idx_location (latitude, longitude),
    INDEX idx_inspection (last_inspection)
);

-- Maintenance projects table
CREATE TABLE IF NOT EXISTS maintenance_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(50) UNIQUE,
    road_segment_id INT,
    status ENUM('planning', 'active', 'completed', 'on_hold', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    start_date DATE,
    end_date DATE,
    estimated_cost DECIMAL(15,2),
    actual_cost DECIMAL(15,2) NULL,
    description TEXT,
    contractor VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (road_segment_id) REFERENCES road_segments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_priority (priority)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    security_code VARCHAR(10) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_active (is_active)
);

-- Admin sessions table
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_token (session_token),
    INDEX idx_expires (expires_at)
);

-- Admin login logs table
CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') NOT NULL,
    INDEX idx_username_time (username, login_time),
    INDEX idx_status_time (status, login_time)
);

-- Insert sample road segments data
INSERT INTO road_segments (segment_name, segment_code, condition_rating, length_km, surface_type, location, last_inspection) VALUES
('Main Street Section A', 'MS-001', 'good', 2.5, 'asphalt', 'Downtown Windhoek', '2024-01-15'),
('Independence Avenue', 'IA-001', 'fair', 3.2, 'asphalt', 'Central Business District', '2024-01-20'),
('Hosea Kutako Drive', 'HKD-001', 'poor', 1.8, 'asphalt', 'Airport Road', '2024-01-10'),
('Sam Nujoma Drive', 'SND-001', 'excellent', 4.1, 'asphalt', 'Northern Suburbs', '2024-02-01'),
('Nelson Mandela Avenue', 'NMA-001', 'poor', 2.3, 'asphalt', 'Katutura', '2024-01-05'),
('Okuryangava Road', 'OR-001', 'fair', 1.9, 'gravel', 'Okuryangava', '2024-01-25'),
('Windhoek West Road', 'WWR-001', 'good', 3.7, 'asphalt', 'Western Areas', '2024-02-10'),
('Klein Windhoek Road', 'KWR-001', 'excellent', 2.1, 'concrete', 'Klein Windhoek', '2024-02-05'),
('Olympia Road', 'OLR-001', 'fair', 2.8, 'asphalt', 'Olympia', '2024-01-30'),
('Academia Road', 'AR-001', 'good', 1.5, 'asphalt', 'Academia', '2024-02-12');

-- Insert sample maintenance projects
INSERT INTO maintenance_projects (project_name, project_code, road_segment_id, status, priority, start_date, end_date, estimated_cost, description, contractor) VALUES
('Pothole Repair - Main Street', 'PR-MS-001', 1, 'active', 'high', '2024-03-01', '2024-03-15', 150000.00, 'Emergency pothole repairs on Main Street Section A', 'ABC Construction'),
('Surface Resurfacing - Independence', 'SR-IA-001', 2, 'planning', 'medium', '2024-04-15', '2024-05-30', 450000.00, 'Complete surface resurfacing of Independence Avenue', 'Road Masters Ltd'),
('Complete Reconstruction - Kutako', 'CR-HKD-001', 3, 'active', 'critical', '2024-02-20', '2024-06-30', 800000.00, 'Full reconstruction of Hosea Kutako Drive', 'Prime Construction'),
('Routine Maintenance - Mandela', 'RM-NMA-001', 5, 'completed', 'medium', '2024-01-10', '2024-01-25', 75000.00, 'Routine maintenance and crack sealing', 'Quick Fix Roads'),
('Drainage Improvement - Okuryangava', 'DI-OR-001', 6, 'planning', 'high', '2024-03-20', '2024-04-10', 200000.00, 'Install proper drainage system', 'Water Works Construction');

-- Insert a default admin user (password: admin123)
INSERT INTO admin_users (username, email, password_hash, security_code, role) VALUES
('admin', 'admin@primeroads.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123456', 'super_admin');

-- Create a test user (password: test123)
INSERT INTO users (first_name, last_name, email, password) VALUES
('Test', 'User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create indexes for better performance
ALTER TABLE road_segments ADD INDEX idx_updated (updated_at);
ALTER TABLE maintenance_projects ADD INDEX idx_updated (updated_at);

-- Create views for dashboard statistics
CREATE OR REPLACE VIEW dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM road_segments) as total_segments,
    (SELECT COUNT(*) FROM road_segments WHERE condition_rating = 'poor') as poor_segments,
    (SELECT COUNT(*) FROM maintenance_projects WHERE status = 'active') as active_projects,
    (SELECT MAX(updated_at) FROM road_segments) as last_update;