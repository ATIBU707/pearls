-- Online Hostel Management System Database Schema
-- Pearls of Wisdom Hostel
-- Created: April 22, 2026

-- =====================================================
-- DROP EXISTING TABLES (if you want to reset)
-- =====================================================
-- Uncomment to reset database
-- DROP TABLE IF EXISTS notifications;
-- DROP TABLE IF EXISTS maintenance_requests;
-- DROP TABLE IF EXISTS receipts;
-- DROP TABLE IF EXISTS payments;
-- DROP TABLE IF EXISTS bookings;
-- DROP TABLE IF EXISTS room_facilities;
-- DROP TABLE IF EXISTS facilities;
-- DROP TABLE IF EXISTS rooms;
-- DROP TABLE IF EXISTS room_types;
-- DROP TABLE IF EXISTS users;


-- =====================================================
-- 1. USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    identification_type ENUM('national_id', 'passport', 'student_id') DEFAULT 'student_id',
    identification_number VARCHAR(50),
    student_id VARCHAR(50) UNIQUE,
    profile_photo VARCHAR(255),
    role ENUM('student', 'admin') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_phone (phone_number),
    INDEX idx_student_id (student_id),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 2. ROOM TYPES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 3. FACILITIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS facilities (
    facility_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 4. ROOMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    room_type_id INT NOT NULL,
    capacity INT DEFAULT 1,
    price_per_semester DECIMAL(10, 2) NOT NULL,
    description TEXT,
    photo_url VARCHAR(255),
    status ENUM('available', 'booked', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_type_id) REFERENCES room_types(type_id),
    INDEX idx_room_number (room_number),
    INDEX idx_status (status),
    INDEX idx_room_type (room_type_id),
    INDEX idx_price (price_per_semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 5. ROOM FACILITIES JOIN TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS room_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    facility_id INT NOT NULL,
    
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_facility (room_id, facility_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 6. BOOKINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_code VARCHAR(50) UNIQUE NOT NULL,
    qr_code LONGTEXT,
    check_in_date DATE NOT NULL,
    check_out_date DATE,
    semester VARCHAR(50) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    INDEX idx_user (user_id),
    INDEX idx_room (room_id),
    INDEX idx_booking_code (booking_code),
    INDEX idx_status (status),
    INDEX idx_check_in_date (check_in_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 7. PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'mtn_momo', 'airtel_money', 'pesapal') DEFAULT 'cash',
    transaction_reference VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    INDEX idx_booking (booking_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 8. RECEIPTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_id INT,
    receipt_code VARCHAR(50) UNIQUE NOT NULL,
    receipt_html LONGTEXT,
    pdf_path VARCHAR(255),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
    INDEX idx_receipt_code (receipt_code),
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 9. MAINTENANCE REQUESTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    INDEX idx_room (room_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 10. NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'payment', 'maintenance', 'general', 'alert') DEFAULT 'general',
    related_booking_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (related_booking_id) REFERENCES bookings(booking_id),
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert Room Types
INSERT INTO room_types (type_name, description) VALUES
('Single Room', 'Single occupancy room with private bathroom'),
('Double Room', 'Double bed room suitable for 2 occupants'),
('Self-contained', 'Fully self-contained unit with kitchenette and private bathroom');

-- Insert Facilities
INSERT INTO facilities (facility_name, description) VALUES
('Wi-Fi', 'High-speed wireless internet'),
('Water Supply', 'Hot and cold water supply'),
('Study Space', 'Dedicated study area'),
('Laundry', 'Laundry room with washing machines'),
('Kitchen', 'Shared kitchen facilities'),
('Parking', 'Parking space available'),
('Security', '24/7 security'),
('Balcony', 'Private balcony');

-- Insert Sample Rooms
INSERT INTO rooms (room_number, room_type_id, capacity, price_per_semester, description, status) VALUES
('A-101', 1, 1, 250000, 'Single room on ground floor', 'available'),
('A-102', 1, 1, 250000, 'Single room with balcony', 'available'),
('A-201', 2, 2, 350000, 'Double room with mountain view', 'available'),
('A-202', 2, 2, 350000, 'Double room standard', 'available'),
('B-301', 3, 1, 450000, 'Self-contained unit with kitchenette', 'available'),
('B-302', 3, 1, 450000, 'Self-contained deluxe', 'available');

-- Insert Sample Admin User
INSERT INTO users (email, phone_number, password_hash, first_name, last_name, student_id, role, is_active, email_verified) VALUES
('admin@pearlswisdom.com', '0765536881', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DvDeljU', 'Admin', 'User', 'ADMIN001', 'admin', TRUE, TRUE);

-- Note: Password hash above is for 'password' - change in production
-- To create a new admin, use: password_hash('your_password', PASSWORD_BCRYPT)

-- =====================================================
-- END OF SCHEMA
-- =====================================================
