CREATE DATABASE prime_roads;
USE prime_roads;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Road segments table
CREATE TABLE road_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    length DECIMAL(10,2) NOT NULL,
    width DECIMAL(5,2) NOT NULL,
    surface_type ENUM('Asphalt', 'Concrete', 'Gravel', 'Other') NOT NULL,
    sidewalks ENUM('Yes', 'No', 'Partial') NOT NULL,
    last_scanned DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Road conditions table (for GIS map)
CREATE TABLE road_conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road_segment_id INT NOT NULL,
    condition ENUM('good', 'fair', 'poor', 'maintenance', 'unscanned') NOT NULL,
    pci_score INT,
    notes TEXT,
    last_updated DATE NOT NULL,
    latlngs TEXT, -- Store JSON coordinates
    FOREIGN KEY (road_segment_id) REFERENCES road_segments(id) ON DELETE CASCADE
);

-- Defects table
CREATE TABLE defects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road_segment_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    severity ENUM('minor', 'moderate', 'severe') NOT NULL,
    position VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (road_segment_id) REFERENCES road_segments(id) ON DELETE CASCADE
);

-- Maintenance records table
CREATE TABLE maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road_segment_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE,
    notes TEXT,
    status ENUM('scheduled', 'in-progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (road_segment_id) REFERENCES road_segments(id) ON DELETE CASCADE
);

-- Notes table
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road_segment_id INT NOT NULL,
    author VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (road_segment_id) REFERENCES road_segments(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (username, password, email, full_name) VALUES 
('admin', 'qwerty', 'admin@primeroads.com', 'Administrator');

INSERT INTO road_segments (name, length, width, surface_type, sidewalks, last_scanned) VALUES
('Main Street', 2.5, 12.0, 'Asphalt', 'Yes', '2023-10-15'),
('Oak Avenue', 3.2, 10.0, 'Concrete', 'No', '2023-09-22'),
('Pine Road', 1.8, 8.0, 'Gravel', 'No', '2023-11-05');

INSERT INTO road_conditions (road_segment_id, condition, pci_score, notes, last_updated, latlngs) VALUES
(1, 'good', 87, '', '2023-10-15', '[[-22.56, 17.07], [-22.57, 17.09], [-22.58, 17.11]]'),
(2, 'fair', 72, '', '2023-09-22', '[[-22.55, 17.06], [-22.56, 17.08], [-22.57, 17.10]]'),
(3, 'poor', 45, 'Needs urgent repair', '2023-11-05', '[[-22.59, 17.08], [-22.58, 17.10], [-22.57, 17.12]]');

INSERT INTO defects (road_segment_id, type, severity, position, description) VALUES
(1, 'Crack', 'moderate', '23m from start', 'Sample crack description'),
(1, 'Particle', 'severe', '73m center', 'Sample particle description'),
(2, 'Edge break', 'minor', '200m right', 'Sample edge break description');

INSERT INTO maintenance_records (road_segment_id, type, scheduled_date, notes, status) VALUES
(1, 'Crack Sealing', '2023-10-20', 'Sealed major cracks along the segment', 'completed'),
(1, 'Surface Patching', '2023-11-15', 'Patched surface damage at multiple locations', 'scheduled');

INSERT INTO notes (road_segment_id, author, content) VALUES
(1, 'John Doe', 'Scheduled inspection for next week. Need to prioritize edge break repairs.'),
(1, 'Jane Smith', 'Previous maintenance completed. Crack sealing performed on sections with moderate damage.');