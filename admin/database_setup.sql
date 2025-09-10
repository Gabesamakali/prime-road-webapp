-- Database setup for road maintenance system
-- Run this in phpMyAdmin or MySQL command line

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS road_maintenance;
USE road_maintenance;

-- Create road_segments table
CREATE TABLE IF NOT EXISTS road_segments (
    segment_id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(255) NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe') NOT NULL,
    defect_type ENUM('pothole', 'crack', 'rutting', 'patching') NOT NULL,
    indicator ENUM('yellow', 'red') DEFAULT 'yellow',
    status ENUM('Planned', 'Assigned', 'In Progress', 'Complete') DEFAULT 'Planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create contractors table
CREATE TABLE IF NOT EXISTS contractors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    specialization VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create maintenance_assignments table (without end_date)
CREATE TABLE IF NOT EXISTS maintenance_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    contractor_id INT NOT NULL,
    start_date DATE NOT NULL,
    estimated_cost DECIMAL(10,2) NOT NULL,
    status ENUM('Planned', 'In Progress', 'Complete', 'Cancelled') DEFAULT 'Planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES road_segments(segment_id) ON DELETE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES contractors(id) ON DELETE CASCADE
);

-- Insert sample contractors if they don't exist
INSERT IGNORE INTO contractors (id, name, contact_phone, contact_email, specialization) VALUES
(1, 'Windhoek Road Services', '+264-61-123456', 'info@windhoekroads.com', 'General Road Maintenance'),
(2, 'Namibian Construction Ltd', '+264-61-234567', 'contracts@namcon.na', 'Heavy Construction'),
(3, 'Quick Fix Maintenance', '+264-61-345678', 'service@quickfix.na', 'Emergency Repairs'),
(4, 'Elite Infrastructure', '+264-61-456789', 'projects@elite.na', 'Infrastructure Development');

-- Insert sample road segments if they don't exist
INSERT IGNORE INTO road_segments (segment_id, location, severity, defect_type, indicator, status) VALUES
(1, '23 Independence Ave, Windhoek', 'Severe', 'pothole', 'red', 'Planned'),
(2, 'Sam Nujoma Drive, Windhoek', 'Moderate', 'crack', 'yellow', 'Planned'),
(3, 'Hosea Kutako Drive, Windhoek', 'Minor', 'rutting', 'yellow', 'Planned'),
(4, 'Robert Mugabe Avenue, Windhoek', 'Severe', 'patching', 'red', 'Planned');

-- Show table structures
DESCRIBE road_segments;
DESCRIBE contractors;
DESCRIBE maintenance_assignments;