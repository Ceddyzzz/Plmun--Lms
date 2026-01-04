<?php
// install.php - One-click installation
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLMUN LMS - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-database text-3xl text-blue-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">PLMUN LMS Installation</h1>
                <p class="text-gray-600 mt-2">Setting up your database and system</p>
            </div>
            
            <?php
            require_once 'includes/config.php';
            
            try {
                echo '<div class="space-y-4">';
                
                // Step 1: Test connection
                echo '<div class="p-4 bg-blue-50 rounded-lg border border-blue-200">';
                echo '<h3 class="font-bold text-blue-800 mb-2 flex items-center">';
                echo '<i class="fas fa-plug mr-2"></i> Step 1: Testing Database Connection';
                echo '</h3>';
                
                try {
                    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    echo '<p class="text-green-600 flex items-center">';
                    echo '<i class="fas fa-check-circle mr-2"></i>';
                    echo 'Connected to MySQL server successfully';
                    echo '</p>';
                } catch(PDOException $e) {
                    echo '<p class="text-red-600">';
                    echo '<i class="fas fa-times-circle mr-2"></i>';
                    echo 'Connection failed: ' . $e->getMessage();
                    echo '</p>';
                    echo '<p class="text-sm text-gray-600 mt-2">';
                    echo 'Make sure XAMPP is running and MySQL is started.';
                    echo '</p>';
                    exit();
                }
                echo '</div>';
                
                // Step 2: Create database
                echo '<div class="p-4 bg-blue-50 rounded-lg border border-blue-200">';
                echo '<h3 class="font-bold text-blue-800 mb-2 flex items-center">';
                echo '<i class="fas fa-database mr-2"></i> Step 2: Creating Database';
                echo '</h3>';
                
                try {
                    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                    $conn->exec($sql);
                    echo '<p class="text-green-600 flex items-center">';
                    echo '<i class="fas fa-check-circle mr-2"></i>';
                    echo 'Database "' . DB_NAME . '" created successfully';
                    echo '</p>';
                } catch(PDOException $e) {
                    echo '<p class="text-red-600">';
                    echo '<i class="fas fa-times-circle mr-2"></i>';
                    echo 'Failed to create database: ' . $e->getMessage();
                    echo '</p>';
                }
                echo '</div>';
                
                // Step 3: Create tables
                echo '<div class="p-4 bg-blue-50 rounded-lg border border-blue-200">';
                echo '<h3 class="font-bold text-blue-800 mb-2 flex items-center">';
                echo '<i class="fas fa-table mr-2"></i> Step 3: Creating Tables';
                echo '</h3>';
                
                // Connect to the database
                $conn = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                    DB_USER,
                    DB_PASS
                );
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Users table
                $sql = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    role ENUM('student', 'teacher', 'program-chair', 'dean', 'admin') NOT NULL,
                    student_id VARCHAR(20) NULL,
                    employee_id VARCHAR(20) NULL,
                    program VARCHAR(100) NULL,
                    year_level VARCHAR(20) NULL,
                    section VARCHAR(20) NULL,
                    department VARCHAR(100) NULL,
                    position VARCHAR(100) NULL,
                    status ENUM('pending', 'active', 'suspended', 'graduated') DEFAULT 'pending',
                    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $conn->exec($sql);
                echo '<p class="text-green-600 flex items-center">';
                echo '<i class="fas fa-check-circle mr-2"></i>';
                echo 'Users table created';
                echo '</p>';
                
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
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $conn->exec($sql);
                echo '<p class="text-green-600 flex items-center">';
                echo '<i class="fas fa-check-circle mr-2"></i>';
                echo 'User profiles table created';
                echo '</p>';
                
                // Create admin user
                $sql = "INSERT IGNORE INTO users (username, password, email, name, role, status) 
                        VALUES ('admin', :password, 'admin@plmun.edu.ph', 'System Administrator', 'admin', 'active')";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['password' => password_hash('admin123', PASSWORD_DEFAULT)]);
                
                echo '<p class="text-green-600 flex items-center">';
                echo '<i class="fas fa-check-circle mr-2"></i>';
                echo 'Admin user created (username: admin, password: admin123)';
                echo '</p>';
                
                echo '</div>';
                
                // Step 4: Test system
                echo '<div class="p-4 bg-green-50 rounded-lg border border-green-200">';
                echo '<h3 class="font-bold text-green-800 mb-2 flex items-center">';
                echo '<i class="fas fa-check-circle mr-2"></i> Step 4: Installation Complete';
                echo '</h3>';
                
                echo '<p class="text-gray-700 mb-4">';
                echo 'PLMUN LMS has been successfully installed!';
                echo '</p>';
                
                echo '<div class="bg-gray-800 text-white p-4 rounded-lg font-mono text-sm">';
                echo '<p><span class="text-green-400">✓</span> Database: ' . DB_NAME . '</p>';
                echo '<p><span class="text-green-400">✓</span> Tables: users, user_profiles</p>';
                echo '<p><span class="text-green-400">✓</span> Admin User: admin / admin123</p>';
                echo '</div>';
                
                echo '</div>';
                
                echo '</div>';
                
                // Success message and navigation
                echo '<div class="mt-8 p-6 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl border border-green-200">';
                echo '<h3 class="text-xl font-bold text-gray-800 mb-4">Ready to Go!</h3>';
                
                echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">';
                echo '<a href="index.php" class="p-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-center transition flex flex-col items-center justify-center">';
                echo '<i class="fas fa-sign-in-alt text-2xl mb-2"></i>';
                echo '<span class="font-bold">Go to Login</span>';
                echo '</a>';
                
                echo '<a href="register.php" class="p-4 bg-green-600 hover:bg-green-700 text-white rounded-lg text-center transition flex flex-col items-center justify-center">';
                echo '<i class="fas fa-user-plus text-2xl mb-2"></i>';
                echo '<span class="font-bold">Register Now</span>';
                echo '</a>';
                
                echo '<a href="dashboard.php" class="p-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-center transition flex flex-col items-center justify-center">';
                echo '<i class="fas fa-tachometer-alt text-2xl mb-2"></i>';
                echo '<span class="font-bold">Dashboard</span>';
                echo '</a>';
                echo '</div>';
                
                echo '<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">';
                echo '<h4 class="font-bold text-yellow-800 mb-2">Demo Credentials:</h4>';
                echo '<div class="space-y-2 text-sm">';
                echo '<p><span class="font-medium">Admin:</span> username: <code>admin</code> | password: <code>admin123</code></p>';
                echo '<p><span class="font-medium">Student:</span> username: <code>2023-00123</code> | password: <code>student123</code></p>';
                echo '<p><span class="font-medium">Teacher:</span> username: <code>T-0456</code> | password: <code>teacher123</code></p>';
                echo '</div>';
                echo '</div>';
                
                echo '</div>';
                
            } catch(PDOException $e) {
                echo '<div class="p-4 bg-red-50 rounded-lg border border-red-200">';
                echo '<h3 class="font-bold text-red-800 mb-2">Installation Error</h3>';
                echo '<p class="text-red-600">' . $e->getMessage() . '</p>';
                echo '</div>';
            }
            ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Delete this file (<code>install.php</code>) after installation for security
                </p>
            </div>
        </div>
    </div>
</body>
</html>
