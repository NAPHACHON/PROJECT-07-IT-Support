-- IT Support Ticket System Database
-- ระบบแจ้งปัญหา IT

CREATE DATABASE IF NOT EXISTS it_support CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_support;

-- ตารางผู้ใช้งาน
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'technician', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตารางช่างเทคนิค
CREATE TABLE technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department VARCHAR(100),
    specialization VARCHAR(200),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ตาราง Tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    technician_id INT NULL,
    requested_technician_id INT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    status ENUM('open', 'in-progress', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_technician_id) REFERENCES technicians(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ตารางคอมเมนต์
CREATE TABLE ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- เพิ่ม Admin เริ่มต้น (password: admin123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin', 'admin@itsupport.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '0800000000', 'admin');

-- เพิ่ม Technician ตัวอย่าง (password: tech123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('tech1', 'tech1@itsupport.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมชาย ช่างไอที', '0811111111', 'technician'),
('tech2', 'tech2@itsupport.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมหญิง เทคนิค', '0822222222', 'technician');

-- เพิ่มข้อมูล Technicians
INSERT INTO technicians (user_id, department, specialization, is_available) VALUES
(2, 'ฝ่ายซ่อมบำรุง', 'คอมพิวเตอร์, เครือข่าย', TRUE),
(3, 'ฝ่ายซ่อมบำรุง', 'ซอฟต์แวร์, ระบบปฏิบัติการ', TRUE);

-- เพิ่ม User ตัวอย่าง (password: user123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'นักศึกษา ทดสอบ', '0833333333', 'user');

-- สร้าง Index เพิ่มความเร็ว
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_user ON tickets(user_id);
CREATE INDEX idx_tickets_technician ON tickets(technician_id);
CREATE INDEX idx_comments_ticket ON ticket_comments(ticket_id);
