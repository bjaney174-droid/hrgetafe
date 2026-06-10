-- ============================================
-- HRGetafe Database Schema
-- Human Resources Information System
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS hrgetafe_db;
USE hrgetafe_db;

-- ============================================
-- 1. USERS TABLE (Login Credentials)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role INT NOT NULL DEFAULT 3,
    status INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ============================================
-- 2. EMPLOYEES TABLE (Employee Information)
-- ============================================
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('Male', 'Female', 'Other'),
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced'),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    zip_code VARCHAR(10),
    position VARCHAR(150) NOT NULL,
    department VARCHAR(150) NOT NULL,
    employment_type ENUM('Permanent', 'Contractual', 'Temporary', 'Casual'),
    hire_date DATE NOT NULL,
    salary DECIMAL(12, 2),
    profile_image VARCHAR(255),
    status INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department),
    INDEX idx_status (status)
);

-- ============================================
-- 3. ATTENDANCE TABLE (Attendance Records)
-- ============================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date_attendance DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('Present', 'Absent', 'Late', 'Early Leave', 'On Leave') DEFAULT 'Absent',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date_attendance),
    INDEX idx_date (date_attendance)
);

-- ============================================
-- 4. LEAVE TYPES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    leave_name VARCHAR(100) NOT NULL,
    description TEXT,
    days_allowed INT NOT NULL,
    is_paid INT DEFAULT 1,
    status INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 5. LEAVE REQUESTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    number_of_days INT NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT,
    approved_date DATETIME,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_employee_id (employee_id)
);

-- ============================================
-- 6. PAYROLL TABLE (Salary Records)
-- ============================================
CREATE TABLE IF NOT EXISTS payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    payroll_period DATE NOT NULL,
    basic_salary DECIMAL(12, 2) NOT NULL,
    allowances DECIMAL(12, 2) DEFAULT 0,
    deductions DECIMAL(12, 2) DEFAULT 0,
    net_salary DECIMAL(12, 2),
    status ENUM('Draft', 'Finalized', 'Paid') DEFAULT 'Draft',
    payment_date DATE,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_payroll_period (payroll_period)
);

-- ============================================
-- 7. DEDUCTIONS TABLE (Payroll Deductions)
-- ============================================
CREATE TABLE IF NOT EXISTS deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_id INT NOT NULL,
    deduction_type VARCHAR(100) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payroll(id) ON DELETE CASCADE
);

-- ============================================
-- 8. ALLOWANCES TABLE (Payroll Allowances)
-- ============================================
CREATE TABLE IF NOT EXISTS allowances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_id INT NOT NULL,
    allowance_type VARCHAR(100) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payroll(id) ON DELETE CASCADE
);

-- ============================================
-- 9. AUDIT LOG TABLE (System Logging)
-- ============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100),
    record_id INT,
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_created_at (created_at),
    INDEX idx_module (module)
);

-- ============================================
-- 10. SYSTEM SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Insert Default Leave Types
INSERT INTO leave_types (leave_name, description, days_allowed, is_paid) VALUES
('Sick Leave', 'Leave for medical/health reasons', 10, 1),
('Vacation Leave', 'Annual vacation leave', 15, 1),
('Bereavement Leave', 'Leave for death in family', 5, 1),
('Maternity Leave', 'Leave for childbirth', 60, 1),
('Paternity Leave', 'Leave for father after childbirth', 7, 1),
('Special Leave', 'Other approved leaves', 5, 1);

-- Insert Default System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('organization_name', 'Getafe Local Government Unit', 'Organization full name'),
('payroll_day', '25', 'Day of month for payroll processing'),
('working_hours', '8', 'Standard working hours per day'),
('late_grace_period', '15', 'Grace period for late arrival in minutes');

-- Insert Demo Users
INSERT INTO users (username, email, password, role, status) VALUES
('admin', 'admin@hrgetafe.com', '$2y$10$zYZzMKwgI5X5QhzKvQqG0uYWN.p0ePhHqBEL9xc5aRdqw7K2VHLlu', 1, 1),
('hrmanager', 'hrmanager@hrgetafe.com', '$2y$10$bKZZEn1sE4gF2mKfVn1mKO1i5RZL5F5VvmXsI5g9B5K2Y3D8H7H5G', 2, 1),
('hrstaff', 'hrstaff@hrgetafe.com', '$2y$10$dN9pKqB3Vf8mL6jR2sT5mO2wP3q4X5Y6Z7aB8cD9eF0gH1iJ2kL3m', 3, 1);

?>
sss