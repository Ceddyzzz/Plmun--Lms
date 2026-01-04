<?php
// Application Configuration
define('APP_NAME', 'PLMUN LMS');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'plmun_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// User Roles
define('ROLE_STUDENT', 'student');
define('ROLE_TEACHER', 'teacher');
define('ROLE_PROGRAM_CHAIR', 'program-chair');
define('ROLE_DEAN', 'dean');
define('ROLE_ADMIN', 'admin');

// Registration Settings
define('ALLOW_REGISTRATION', true);
define('DEFAULT_ROLE', ROLE_STUDENT);
define('ADMIN_APPROVAL_REQUIRED', false);
define('MIN_STUDENT_ID', 202300001);
define('MIN_EMPLOYEE_ID', 1001);

// Email Settings
define('SEND_REGISTRATION_EMAIL', false);

// Email Domains by Program/Department
$GLOBALS['email_domains'] = [
    'student' => [
        'BS Computer Science' => 'bscs',
        'BS Information Technology' => 'bsit',
        'BS Business Administration' => 'bsba',
        'BS Education' => 'bsed',
        'BS Psychology' => 'bspsych'
    ],
    'teacher' => [
        'Computer Science' => 'prof.bscs',
        'Information Technology' => 'prof.bsit',
        'Business Administration' => 'prof.bsba',
        'Education' => 'prof.bsed',
        'Psychology' => 'prof.bspsych'
    ]
];

// Database connection with auto-creation (with function_exists check)
if (!function_exists('get_db_connection')) {
    function get_db_connection($create_db = false) {
        try {
            if ($create_db) {
                // Connect without database first
                $conn = new PDO(
                    "mysql:host=" . DB_HOST,
                    DB_USER,
                    DB_PASS
                );
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if it doesn't exist
                $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                $conn->exec($sql);
                
                // Now connect with database
                $conn = null;
            }
            
            // Connect to the database
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $conn;
        } catch(PDOException $e) {
            // Try to create database automatically
            if (!$create_db) {
                return get_db_connection(true);
            }
            error_log("Database connection failed: " . $e->getMessage());
            return false;
        }
    }
}

// Create tables if they don't exist
if (!function_exists('create_tables_if_not_exists')) {
    function create_tables_if_not_exists() {
        try {
            $conn = get_db_connection();
            if (!$conn) return false;
            
            // Users table
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('student', 'teacher', 'program-chair', 'dean', 'admin') NOT NULL,
                student_id VARCHAR(20) UNIQUE NULL,
                employee_id VARCHAR(20) UNIQUE NULL,
                program VARCHAR(100) NULL,
                year_level VARCHAR(20) NULL,
                department VARCHAR(100) NULL,
                position VARCHAR(100) NULL,
                status ENUM('pending', 'active', 'suspended', 'graduated') DEFAULT 'pending',
                registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student_id (student_id),
                INDEX idx_employee_id (employee_id),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->exec($sql);
            
            // User profiles table
            $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                phone VARCHAR(20) NULL,
                address TEXT NULL,
                birthdate DATE NULL,
                gender ENUM('male', 'female', 'other') NULL,
                profile_picture VARCHAR(255) NULL,
                bio TEXT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->exec($sql);
            
            return true;
        } catch(PDOException $e) {
            error_log("Table creation error: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize database (NO SESSION START HERE)
create_tables_if_not_exists();
?>
