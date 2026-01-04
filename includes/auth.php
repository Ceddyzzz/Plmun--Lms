<?php
// Include config once
require_once 'config.php';

// Start session if not already started (ONCE HERE)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Demo Users (for testing without database)
$GLOBALS['demo_users'] = [
    'student' => [
        '2023-00123' => [
            'password' => 'student123',
            'name' => 'Juan Dela Cruz',
            'email' => 'bsit@plmun.edu.ph',
            'program' => 'BS Information Technology',
            'year' => '2nd Year',
            'status' => 'active'
        ]
    ],
    'teacher' => [
        'T-0456' => [
            'password' => 'teacher123',
            'name' => 'Dr. Maria Santos',
            'email' => 'prof.bsit@plmun.edu.ph',
            'department' => 'Information Technology',
            'position' => 'Associate Professor',
            'status' => 'active'
        ]
    ]
];

// ========== AUTHENTICATION FUNCTIONS ==========

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
}

if (!function_exists('login')) {
    function login($username, $password, $role) {
        // Try database first
        $db_user = login_from_database($username, $password, $role);
        if ($db_user) {
            return true;
        }
        
        // Fallback to demo users
        return login_from_demo($username, $password, $role);
    }
}

if (!function_exists('login_from_demo')) {
    function login_from_demo($username, $password, $role) {
        $demo_users = $GLOBALS['demo_users'];
        
        if (!isset($demo_users[$role]) || !isset($demo_users[$role][$username])) {
            return false;
        }
        
        $user = $demo_users[$role][$username];
        
        if ($user['password'] !== $password) {
            return false;
        }
        
        $_SESSION['user_id'] = $username;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_data'] = $user;
        $_SESSION['login_time'] = time();
        
        return true;
    }
}

if (!function_exists('login_from_database')) {
    function login_from_database($username, $password, $role) {
        try {
            $conn = get_db_connection();
            if (!$conn) return false;
            
            if ($role == ROLE_STUDENT) {
                $sql = "SELECT * FROM users WHERE (username = :username OR student_id = :username) AND role = :role AND status = 'active'";
            } elseif ($role == ROLE_TEACHER) {
                $sql = "SELECT * FROM users WHERE (username = :username OR employee_id = :username) AND role = :role AND status = 'active'";
            } else {
                $sql = "SELECT * FROM users WHERE username = :username AND role = :role AND status = 'active'";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['username' => $username, 'role' => $role]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_data'] = $user;
                $_SESSION['login_time'] = time();
                
                // Update last login
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $stmt->execute(['id' => $user['id']]);
                
                return true;
            }
        } catch(PDOException $e) {
            error_log("Database login error: " . $e->getMessage());
        }
        
        return false;
    }
}

// ========== REGISTRATION FUNCTIONS ==========

if (!function_exists('register_user')) {
    function register_user($data) {
        if (!ALLOW_REGISTRATION) {
            return ['success' => false, 'message' => 'Registration is currently disabled'];
        }
        
        // Validate required fields
        $required = ['username', 'password', 'name', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Please fill in all required fields"];
            }
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        
        if (username_exists($data['username'])) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        
        // Validate role
        if (!in_array($data['role'], [ROLE_STUDENT, ROLE_TEACHER])) {
            return ['success' => false, 'message' => 'Invalid role selected'];
        }
        
        if ($data['role'] == ROLE_STUDENT && empty($data['program'])) {
            return ['success' => false, 'message' => 'Please select a program'];
        }
        
        if ($data['role'] == ROLE_TEACHER && empty($data['department'])) {
            return ['success' => false, 'message' => 'Please select a department'];
        }
        
        try {
            $conn = get_db_connection();
            if (!$conn) {
                return ['success' => false, 'message' => 'Database connection failed'];
            }
            
            // Generate IDs and email
            $student_id = null;
            $employee_id = null;
            $email = '';
            
            if ($data['role'] == ROLE_STUDENT) {
                $student_id = generate_unique_id(ROLE_STUDENT, $data['program']);
                $email = generate_email($data['username'], ROLE_STUDENT, $data['program']);
            } else {
                $employee_id = generate_unique_id(ROLE_TEACHER, $data['department']);
                $email = generate_email($data['username'], ROLE_TEACHER, $data['department']);
            }
            
            // Check if email exists
            if (email_exists($email)) {
                $email = $data['username'] . '@plmun.edu.ph';
                if (email_exists($email)) {
                    return ['success' => false, 'message' => 'Email already exists. Please try a different username.'];
                }
            }
            
            // Insert basic user data first
            $sql = "INSERT INTO users (username, password, email, name, role, status) 
                    VALUES (:username, :password, :email, :name, :role, :status)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'username' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'email' => $email,
                'name' => $data['name'],
                'role' => $data['role'],
                'status' => ADMIN_APPROVAL_REQUIRED ? 'pending' : 'active'
            ]);
            
            $user_id = $conn->lastInsertId();
            
            // Update with role-specific fields
            if ($data['role'] == ROLE_STUDENT && $student_id) {
                $sql = "UPDATE users SET student_id = :student_id, program = :program, year_level = :year_level WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'student_id' => $student_id,
                    'program' => $data['program'] ?? '',
                    'year_level' => $data['year_level'] ?? '',
                    'id' => $user_id
                ]);
            } elseif ($data['role'] == ROLE_TEACHER && $employee_id) {
                $sql = "UPDATE users SET employee_id = :employee_id, department = :department, position = :position WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'employee_id' => $employee_id,
                    'department' => $data['department'] ?? '',
                    'position' => $data['position'] ?? '',
                    'id' => $user_id
                ]);
            }
            
            // Create profile
            $sql = "INSERT INTO user_profiles (user_id) VALUES (:user_id)";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            // Send email if enabled
            if (defined('SEND_REGISTRATION_EMAIL') && SEND_REGISTRATION_EMAIL) {
                send_registration_email($email, $data['name'], $data['role']);
            }
            
            // Return success
            $response = [
                'success' => true, 
                'message' => ADMIN_APPROVAL_REQUIRED 
                    ? 'Registration successful! Your account is pending admin approval.' 
                    : 'Registration successful! You can now login.',
                'user_id' => $user_id,
                'email' => $email,
                'role' => $data['role']
            ];
            
            if ($data['role'] == ROLE_STUDENT && $student_id) {
                $response['student_id'] = $student_id;
            } elseif ($data['role'] == ROLE_TEACHER && $employee_id) {
                $response['employee_id'] = $employee_id;
            }
            
            return $response;
            
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
}

// ========== HELPER FUNCTIONS ==========

if (!function_exists('generate_unique_id')) {
    function generate_unique_id($role, $program_or_department) {
        try {
            $conn = get_db_connection();
            if (!$conn) return '';
            
            if ($role == ROLE_STUDENT) {
                $current_year = date('Y');
                $prefix = $current_year . '-';
                
                // Get max ID for this year and program
                $stmt = $conn->prepare("
                    SELECT MAX(CAST(SUBSTRING(student_id, 6) AS UNSIGNED)) as max_id 
                    FROM users 
                    WHERE student_id LIKE :prefix 
                    AND role = 'student' 
                    AND program = :program
                ");
                $stmt->execute([
                    'prefix' => $prefix . '%',
                    'program' => $program_or_department
                ]);
                $result = $stmt->fetch();
                
                $next_id = $result['max_id'] ? $result['max_id'] + 1 : 1;
                
                return $prefix . str_pad($next_id, 5, '0', STR_PAD_LEFT);
                
            } elseif ($role == ROLE_TEACHER) {
                // Get max employee ID for this department
                $stmt = $conn->prepare("
                    SELECT MAX(CAST(SUBSTRING(employee_id, 3) AS UNSIGNED)) as max_id 
                    FROM users 
                    WHERE employee_id LIKE 'T-%' 
                    AND role = 'teacher' 
                    AND department = :department
                ");
                $stmt->execute(['department' => $program_or_department]);
                $result = $stmt->fetch();
                
                $next_id = $result['max_id'] ? $result['max_id'] + 1 : 1;
                
                return 'T-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
            }
            
            return '';
            
        } catch(PDOException $e) {
            error_log("ID Generation error: " . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('generate_email')) {
    function generate_email($username, $role, $program_or_department) {
        $email_domains = $GLOBALS['email_domains'];
        
        if ($role == ROLE_STUDENT && isset($email_domains['student'][$program_or_department])) {
            $domain = $email_domains['student'][$program_or_department];
            return $domain . '@plmun.edu.ph';
        } 
        elseif ($role == ROLE_TEACHER && isset($email_domains['teacher'][$program_or_department])) {
            $domain = $email_domains['teacher'][$program_or_department];
            return $domain . '@plmun.edu.ph';
        }
        
        // Fallback
        return $username . '@plmun.edu.ph';
    }
}

if (!function_exists('username_exists')) {
    function username_exists($username) {
        try {
            $conn = get_db_connection();
            if (!$conn) return false;
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            return $stmt->fetch() !== false;
        } catch(PDOException $e) {
            // Check demo users as fallback
            $demo_users = $GLOBALS['demo_users'];
            foreach ($demo_users as $role_users) {
                if (isset($role_users[$username])) {
                    return true;
                }
            }
            return false;
        }
    }
}

if (!function_exists('email_exists')) {
    function email_exists($email) {
        try {
            $conn = get_db_connection();
            if (!$conn) return false;
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch() !== false;
        } catch(PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('send_registration_email')) {
    function send_registration_email($email, $name, $role) {
        $subject = "Welcome to PLMUN LMS";
        $message = "Dear $name,\n\n";
        $message .= "Thank you for registering as a $role in the PLMUN Learning Management System.\n\n";
        
        if (ADMIN_APPROVAL_REQUIRED) {
            $message .= "Your account is pending admin approval. You will be notified once it's activated.\n\n";
        } else {
            $message .= "Your account has been activated. You can now login using your credentials.\n\n";
        }
        
        $message .= "Best regards,\nPLMUN LMS Team";
        
        $headers = "From: no-reply@plmun.edu.ph\r\n";
        $headers .= "Reply-To: no-reply@plmun.edu.ph\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
}

// ========== USER MANAGEMENT FUNCTIONS ==========

if (!function_exists('logout')) {
    function logout() {
        // Clear session
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: index.php');
            exit();
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($allowed_roles) {
        require_login();
        
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        
        if (!in_array($_SESSION['user_role'], $allowed_roles)) {
            header('Location: dashboard.php');
            exit();
        }
    }
}

if (!function_exists('get_current_session_user')) {
    function get_current_session_user() {
        if (is_logged_in()) {
            try {
                $conn = get_db_connection();
                if (!$conn) return null;
                
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user_data = $stmt->fetch();
                
                if ($user_data) {
                    return [
                        'id' => $user_data['id'],
                        'username' => $user_data['username'],
                        'role' => $user_data['role'],
                        'name' => $user_data['name'],
                        'email' => $user_data['email'],
                        'student_id' => $user_data['student_id'] ?? null,
                        'employee_id' => $user_data['employee_id'] ?? null,
                        'program' => $user_data['program'] ?? null,
                        'department' => $user_data['department'] ?? null,
                        'year_level' => $user_data['year_level'] ?? null,
                        'position' => $user_data['position'] ?? null,
                        'status' => $user_data['status']
                    ];
                }
            } catch(PDOException $e) {
                // Fallback to session data
                error_log("Failed to fetch user data: " . $e->getMessage());
            }
            
            return [
                'id' => $_SESSION['user_id'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name'] ?? 'User'
            ];
        }
        return null;
    }
}

if (!function_exists('get_user_role_name')) {
    function get_user_role_name($role) {
        $role_names = [
            ROLE_STUDENT => 'Student',
            ROLE_TEACHER => 'Teacher',
            ROLE_PROGRAM_CHAIR => 'Program Chair',
            ROLE_DEAN => 'Dean',
            ROLE_ADMIN => 'System Administrator'
        ];
        
        return $role_names[$role] ?? 'Unknown Role';
    }
}

if (!function_exists('get_role_color')) {
    function get_role_color($role) {
        $colors = [
            ROLE_STUDENT => 'blue',
            ROLE_TEACHER => 'red',
            ROLE_PROGRAM_CHAIR => 'yellow',
            ROLE_DEAN => 'purple',
            ROLE_ADMIN => 'gray'
        ];
        
        return $colors[$role] ?? 'gray';
    }
}

if (!function_exists('get_role_icon')) {
    function get_role_icon($role) {
        $icons = [
            ROLE_STUDENT => 'fa-user-graduate',
            ROLE_TEACHER => 'fa-chalkboard-teacher',
            ROLE_PROGRAM_CHAIR => 'fa-users',
            ROLE_DEAN => 'fa-user-tie',
            ROLE_ADMIN => 'fa-cogs'
        ];
        
        return $icons[$role] ?? 'fa-user';
    }
}

if (!function_exists('get_user_stats')) {
    function get_user_stats($role = null) {
        try {
            $conn = get_db_connection();
            if (!$conn) return 0;
            
            if ($role) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = :role AND status = 'active'");
                $stmt->execute(['role' => $role]);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
                $stmt->execute();
            }
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            return 0;
        }
    }
}

// ========== CHAT FUNCTIONS ==========

// Create chat tables
if (!function_exists('create_chat_tables')) {
    function create_chat_tables() {
        try {
            $conn = get_db_connection();
            
            // Connections table
            $sql = "CREATE TABLE IF NOT EXISTS user_connections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user1_id INT NOT NULL,
                user2_id INT NOT NULL,
                status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
                requested_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_connection (user1_id, user2_id),
                FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user1 (user1_id),
                INDEX idx_user2 (user2_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->exec($sql);
            
            // Messages table
            $sql = "CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_sender (sender_id),
                INDEX idx_receiver (receiver_id),
                INDEX idx_conversation (sender_id, receiver_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->exec($sql);
            
            return true;
        } catch(PDOException $e) {
            error_log("Chat table creation error: " . $e->getMessage());
            return false;
        }
    }
}

// Send connection request
if (!function_exists('send_connection_request')) {
    function send_connection_request($sender_id, $receiver_id) {
        try {
            $conn = get_db_connection();
            
            // Check if connection already exists
            $sql = "SELECT id FROM user_connections 
                    WHERE (user1_id = :user1 AND user2_id = :user2) 
                    OR (user1_id = :user2 AND user2_id = :user1)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'user1' => $sender_id,
                'user2' => $receiver_id
            ]);
            
            if ($stmt->fetch()) {
                return false;
            }
            
            // Insert connection request
            $sql = "INSERT INTO user_connections (user1_id, user2_id, status, requested_by) 
                    VALUES (:user1, :user2, 'pending', :requested_by)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'user1' => $sender_id,
                'user2' => $receiver_id,
                'requested_by' => $sender_id
            ]);
            
            return true;
        } catch(PDOException $e) {
            error_log("Connection request error: " . $e->getMessage());
            return false;
        }
    }
}

// Accept connection request
if (!function_exists('accept_connection_request')) {
    function accept_connection_request($request_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "UPDATE user_connections SET status = 'accepted' WHERE id = :id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute(['id' => $request_id]);
            
        } catch(PDOException $e) {
            error_log("Accept connection error: " . $e->getMessage());
            return false;
        }
    }
}

// Decline connection request
if (!function_exists('decline_connection_request')) {
    function decline_connection_request($request_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "DELETE FROM user_connections WHERE id = :id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute(['id' => $request_id]);
            
        } catch(PDOException $e) {
            error_log("Decline connection error: " . $e->getMessage());
            return false;
        }
    }
}

// Remove connection
if (!function_exists('remove_connection')) {
    function remove_connection($connection_id, $user_id, $receiver_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "DELETE FROM user_connections WHERE id = :id";
            $stmt = $conn->prepare($sql);
            return $stmt->execute(['id' => $connection_id]);
            
        } catch(PDOException $e) {
            error_log("Remove connection error: " . $e->getMessage());
            return false;
        }
    }
}

// Get user connections
if (!function_exists('get_user_connections')) {
    function get_user_connections($user_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "SELECT c.id as connection_id, 
                           u.id, u.name, u.email, u.role, 
                           u.student_id, u.employee_id
                    FROM user_connections c
                    JOIN users u ON (c.user1_id = u.id OR c.user2_id = u.id)
                    WHERE (c.user1_id = :user_id OR c.user2_id = :user_id)
                    AND c.status = 'accepted'
                    AND u.id != :user_id
                    ORDER BY c.updated_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $results = $stmt->fetchAll();
            
            return $results ?: [];
            
        } catch(PDOException $e) {
            error_log("Get connections error: " . $e->getMessage());
            return [];
        }
    }
}

// Get pending requests
if (!function_exists('get_pending_requests')) {
    function get_pending_requests($user_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "SELECT c.id, u.id as user_id, u.name, u.email, u.role
                    FROM user_connections c
                    JOIN users u ON c.requested_by = u.id
                    WHERE (c.user1_id = :user_id OR c.user2_id = :user_id)
                    AND c.status = 'pending'
                    AND c.requested_by != :user_id
                    ORDER BY c.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $results = $stmt->fetchAll();
            
            return $results ?: [];
            
        } catch(PDOException $e) {
            error_log("Get pending requests error: " . $e->getMessage());
            return [];
        }
    }
}

// Send message
if (!function_exists('send_message')) {
    function send_message($sender_id, $receiver_id, $message) {
        try {
            $conn = get_db_connection();
            
            // Check if users are connected
            $sql = "SELECT id FROM user_connections 
                    WHERE ((user1_id = :user1 AND user2_id = :user2) 
                    OR (user1_id = :user2 AND user2_id = :user1))
                    AND status = 'accepted'";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'user1' => $sender_id,
                'user2' => $receiver_id
            ]);
            
            if (!$stmt->fetch()) {
                return false;
            }
            
            // Insert message
            $sql = "INSERT INTO messages (sender_id, receiver_id, message) 
                    VALUES (:sender, :receiver, :message)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'sender' => $sender_id,
                'receiver' => $receiver_id,
                'message' => $message
            ]);
            
            return true;
        } catch(PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            return false;
        }
    }
}

// Get messages between users
if (!function_exists('get_messages_between')) {
    function get_messages_between($user1_id, $user2_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "SELECT m.*, u.name as sender_name 
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
                       OR (m.sender_id = :user2 AND m.receiver_id = :user1)
                    ORDER BY m.created_at ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'user1' => $user1_id,
                'user2' => $user2_id
            ]);
            
            $results = $stmt->fetchAll();
            return $results ?: [];
            
        } catch(PDOException $e) {
            error_log("Get messages error: " . $e->getMessage());
            return [];
        }
    }
}

// Mark messages as read
if (!function_exists('mark_messages_as_read')) {
    function mark_messages_as_read($receiver_id, $sender_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "UPDATE messages SET is_read = 1 
                    WHERE receiver_id = :receiver_id AND sender_id = :sender_id AND is_read = 0";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute([
                'receiver_id' => $receiver_id,
                'sender_id' => $sender_id
            ]);
            
        } catch(PDOException $e) {
            error_log("Mark messages as read error: " . $e->getMessage());
            return false;
        }
    }
}

// Get unread message count
if (!function_exists('get_unread_message_count')) {
    function get_unread_message_count($user_id) {
        try {
            $conn = get_db_connection();
            
            $sql = "SELECT COUNT(*) as count FROM messages 
                    WHERE receiver_id = :user_id AND is_read = 0";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $result = $stmt->fetch();
            
            return $result['count'] ?? 0;
            
        } catch(PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
}

// Initialize chat tables on first run
create_chat_tables();
?>
