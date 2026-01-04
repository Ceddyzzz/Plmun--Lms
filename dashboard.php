<?php
// Configure session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Now start the session
session_start();
require 'includes/config.php';
require 'includes/auth.php';
require_login();

// Get database connection
$conn = get_db_connection();

// CHANGED: Use the updated function name
$user = get_current_session_user(); // Changed from get_current_user()
$role = $user['role'];
$role_name = get_user_role_name($role);
$role_color = get_role_color($role);
$role_icon = get_role_icon($role);

// Navigation items based on role
$navigation = get_navigation_items($role);

// Get current section
$section = $_GET['section'] ?? 'dashboard';

// Validate the section is allowed for this role
$allowed_sections = array_column($navigation, 'id');
if (!in_array($section, $allowed_sections)) {
    $section = 'dashboard';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLMUN LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        .plmun-blue { background-color: #003366; }
        .text-plmun-blue { color: #003366; }
        .border-plmun-blue { border-color: #003366; }
        .sidebar-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .sidebar-item:hover { background-color: rgba(0, 51, 102, 0.05); border-left: 3px solid #4A90E2; }
        .sidebar-item.active { background-color: rgba(0, 51, 102, 0.1); border-left: 3px solid #003366; font-weight: 600; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background-color: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .chat-message-left { margin-right: auto; max-width: 70%; }
        .chat-message-right { margin-left: auto; max-width: 70%; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation -->
    <header class="plmun-blue text-white sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <div class="flex items-center mr-8">
                        <i class="fas fa-university text-2xl mr-3"></i>
                        <div>
                            <h1 class="font-bold text-lg">PLMUN LMS</h1>
                            <p class="text-xs opacity-80">Pamantasan ng Lungsod ng Muntinlupa</p>
                        </div>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <nav class="hidden md:flex space-x-1">
                        <?php foreach ($navigation as $nav_item): ?>
                            <?php if ($nav_item['id'] !== 'logout'): ?>
                                <a href="dashboard.php?section=<?php echo $nav_item['id']; ?>" 
                                   class="px-4 py-2 rounded-lg hover:bg-blue-800 transition <?php echo ($section === $nav_item['id']) ? 'bg-blue-800' : ''; ?>">
                                    <i class="fas <?php echo $nav_item['icon']; ?> mr-2"></i> 
                                    <?php echo $nav_item['name']; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Role Badge -->
                    <div class="hidden md:block px-3 py-1 bg-blue-800 rounded-full text-sm">
                        <?php echo $role_name; ?>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" class="w-10 h-10 rounded-full bg-blue-800 flex items-center justify-center hover:bg-blue-700">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        
                        <!-- Notifications Dropdown -->
                        <div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border">
                            <div class="p-4 border-b">
                                <h3 class="font-bold text-gray-800">Notifications</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div class="p-4 border-b hover:bg-gray-50 cursor-pointer">
                                    <div class="flex">
                                        <div class="mr-3">
                                            <i class="fas fa-book text-blue-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">New assignment posted</p>
                                            <p class="text-sm text-gray-600">CS101 - Programming Fundamentals</p>
                                            <p class="text-xs text-gray-500">2 hours ago</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 border-b hover:bg-gray-50 cursor-pointer">
                                    <div class="flex">
                                        <div class="mr-3">
                                            <i class="fas fa-bullhorn text-yellow-500"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">New announcement</p>
                                            <p class="text-sm text-gray-600">Midterm schedule changes</p>
                                            <p class="text-xs text-gray-500">1 day ago</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 border-t text-center">
                                <a href="#" class="text-plmun-blue text-sm font-medium">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-blue-800">
                            <div class="w-8 h-8 rounded-full bg-white text-plmun-blue flex items-center justify-center font-bold">
                                <?php echo substr($user['name'], 0, 1); ?>
                            </div>
                            <div class="text-left hidden md:block">
                                <p class="font-medium text-sm"><?php echo $user['name']; ?></p>
                                <p class="text-xs opacity-80"><?php echo $role_name; ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- User Menu Dropdown -->
                        <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50 border">
                            <div class="p-4 border-b">
                                <p class="font-bold text-gray-800"><?php echo $user['name']; ?></p>
                                <p class="text-sm text-gray-600"><?php echo $role_name; ?></p>
                                <p class="text-xs text-gray-500">ID: <?php echo $user['id']; ?></p>
                            </div>
                            <div class="py-2">
                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-3"></i>My Profile
                                </a>
                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-3"></i>Settings
                                </a>
                                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-question-circle mr-3"></i>Help & Support
                                </a>
                            </div>
                            <div class="py-2 border-t">
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t z-40">
        <div class="flex justify-around p-3">
            <?php 
            // Show first 4 items + logout on mobile
            $mobile_items = array_slice($navigation, 0, 4);
            if (!in_array('logout', array_column($mobile_items, 'id'))) {
                $mobile_items[] = ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt'];
            }
            
            foreach ($mobile_items as $nav_item): 
            ?>
                <a href="<?php echo ($nav_item['id'] === 'logout') ? 'logout.php' : 'dashboard.php?section=' . $nav_item['id']; ?>" 
                   class="flex flex-col items-center p-2 <?php echo ($section === $nav_item['id']) ? 'text-plmun-blue' : 'text-gray-600'; ?>">
                    <i class="fas <?php echo $nav_item['icon']; ?> text-lg mb-1"></i>
                    <span class="text-xs"><?php echo $nav_item['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 pb-20 md:pb-6">
        <div class="flex">
            <!-- Sidebar (Desktop) -->
            <aside class="hidden md:block w-64 mr-6">
                <div class="bg-white rounded-xl shadow-sm p-4 sticky top-24">
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800 mb-4 text-lg">Navigation</h3>
                        <ul class="space-y-1">
                            <?php foreach ($navigation as $nav_item): ?>
                                <li>
                                    <a href="<?php echo ($nav_item['id'] === 'logout') ? 'logout.php' : 'dashboard.php?section=' . $nav_item['id']; ?>" 
                                       class="sidebar-item p-3 rounded-lg cursor-pointer block <?php echo ($section === $nav_item['id']) ? 'active' : ''; ?>">
                                        <div class="flex items-center">
                                            <i class="fas <?php echo $nav_item['icon']; ?> text-<?php echo $role_color; ?>-500 mr-3"></i>
                                            <span><?php echo $nav_item['name']; ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="mt-8 pt-6 border-t">
                        <h4 class="font-bold text-gray-800 mb-3">Quick Actions</h4>
                        <div id="quick-actions">
                            <?php echo get_quick_actions($role); ?>
                        </div>
                    </div>
                </div>
            </aside>
            
            <!-- Content Area -->
            <div class="flex-1">
                <!-- Breadcrumb -->
                <div class="mb-6">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="dashboard.php?section=dashboard" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-plmun-blue">
                                    <i class="fas fa-home mr-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <?php if ($section !== 'dashboard'): ?>
                                <li aria-current="page">
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                        <span class="ml-1 text-sm font-medium text-plmun-blue md:ml-2">
                                            <?php echo ucfirst(str_replace('-', ' ', $section)); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
                
                <!-- Content Title -->
                <div class="mb-6">
                    <h2 id="content-title" class="text-2xl font-bold text-gray-800">
                        <?php echo ($section === 'dashboard') ? $role_name . ' Dashboard' : ucfirst(str_replace('-', ' ', $section)); ?>
                    </h2>
                    <p id="content-description" class="text-gray-600">
                        <?php echo ($section === 'dashboard') ? 'Welcome back, ' . $user['name'] . '!' : get_section_description($section, $role); ?>
                    </p>
                </div>
                
                <!-- Dynamic Content -->
                <div class="fade-in">
                    <?php 
                    // Pass the database connection to the content loader
                    echo load_section_content($section, $role, $user, $conn); 
                    ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Toggle notifications dropdown
        function toggleNotifications() {
            const dropdown = document.getElementById('notifications-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Toggle user menu dropdown
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-menu-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const notificationsBtn = document.querySelector('[onclick="toggleNotifications()"]');
            const notificationsDropdown = document.getElementById('notifications-dropdown');
            const userMenuBtn = document.querySelector('[onclick="toggleUserMenu()"]');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            
            if (notificationsBtn && !notificationsBtn.contains(event.target) && notificationsDropdown && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
            }
            
            if (userMenuBtn && !userMenuBtn.contains(event.target) && userMenuDropdown && !userMenuDropdown.contains(event.target)) {
                userMenuDropdown.classList.add('hidden');
            }
        });

        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Refresh chat every 5 seconds if in chat section
        <?php if ($section === 'chat' && isset($_GET['contact'])): ?>
        setInterval(function() {
            location.reload();
        }, 5000);
        
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Helper functions for this page
function get_navigation_items($role) {
    $navigation = [
        'student' => [
            ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
            ['id' => 'chat', 'name' => 'Chat', 'icon' => 'fa-comments'],
            ['id' => 'announcement', 'name' => 'Announcement', 'icon' => 'fa-bullhorn'],
            ['id' => 'quiz', 'name' => 'Quiz & Assignment', 'icon' => 'fa-tasks'],
            ['id' => 'enrollment', 'name' => 'Enrollment', 'icon' => 'fa-clipboard-list'],
            ['id' => 'ebooks', 'name' => 'E-Books', 'icon' => 'fa-book-open'],
            ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt']
        ],
        'teacher' => [
            ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
            ['id' => 'chat', 'name' => 'Chat', 'icon' => 'fa-comments'],
            ['id' => 'announcement', 'name' => 'Announcement', 'icon' => 'fa-bullhorn'],
            ['id' => 'quiz', 'name' => 'Quiz & Assignment', 'icon' => 'fa-tasks'],
            ['id' => 'sections', 'name' => 'Sections', 'icon' => 'fa-users'],
            ['id' => 'ebooks', 'name' => 'E-Books', 'icon' => 'fa-book-open'],
            ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt']
        ],
        'program-chair' => [
            ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
            ['id' => 'chat', 'name' => 'Chat', 'icon' => 'fa-comments'],
            ['id' => 'announcement', 'name' => 'Announcement', 'icon' => 'fa-bullhorn'],
            ['id' => 'assigned', 'name' => 'Assigned Subjects', 'icon' => 'fa-chalkboard-teacher'],
            ['id' => 'ebooks', 'name' => 'E-Books', 'icon' => 'fa-book-open'],
            ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt']
        ],
        'dean' => [
            ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
            ['id' => 'chat', 'name' => 'Chat', 'icon' => 'fa-comments'],
            ['id' => 'announcement', 'name' => 'Announcement', 'icon' => 'fa-bullhorn'],
            ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt']
        ],
        'admin' => [
            ['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
            ['id' => 'user-management', 'name' => 'User Management', 'icon' => 'fa-users-cog'],
            ['id' => 'system-settings', 'name' => 'System Settings', 'icon' => 'fa-sliders-h'],
            ['id' => 'reports', 'name' => 'Reports', 'icon' => 'fa-chart-bar'],
            ['id' => 'logout', 'name' => 'Logout', 'icon' => 'fa-sign-out-alt']
        ]
    ];
    
    return $navigation[$role] ?? $navigation['student'];
}

function get_quick_actions($role) {
    $actions = [
        'student' => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-upload text-green-500 mr-3"></i>
                <span class="font-medium text-gray-800">Submit Assignment</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-chart-line text-blue-500 mr-3"></i>
                <span class="font-medium text-gray-800">View Grades</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-folder-open text-purple-500 mr-3"></i>
                <span class="font-medium text-gray-800">Course Materials</span>
            </button>
        ',
        'teacher' => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-plus-circle text-green-500 mr-3"></i>
                <span class="font-medium text-gray-800">Create Quiz</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-chart-bar text-yellow-500 mr-3"></i>
                <span class="font-medium text-gray-800">Grade Reports</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-calendar-alt text-red-500 mr-3"></i>
                <span class="font-medium text-gray-800">Attendance</span>
            </button>
        ',
        'program-chair' => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-chalkboard-teacher text-blue-500 mr-3"></i>
                <span class="font-medium text-gray-800">Assign Subjects</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-clipboard-list text-green-500 mr-3"></i>
                <span class="font-medium text-gray-800">Review Curriculum</span>
            </button>
        ',
        'dean' => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-university text-purple-500 mr-3"></i>
                <span class="font-medium text-gray-800">Program Reports</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-user-plus text-blue-500 mr-3"></i>
                <span class="font-medium text-gray-800">Faculty Management</span>
            </button>
        ',
        'admin' => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-database text-green-500 mr-3"></i>
                <span class="font-medium text-gray-800">Database Backup</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-shield-alt text-yellow-500 mr-3"></i>
                <span class="font-medium text-gray-800">Security Logs</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-cogs text-gray-500 mr-3"></i>
                <span class="font-medium text-gray-800">System Maintenance</span>
            </button>
        '
    ];
    
    return $actions[$role] ?? $actions['student'];
}

function get_role_color($role) {
    $colors = [
        'student' => 'blue',
        'teacher' => 'green',
        'program-chair' => 'purple',
        'dean' => 'yellow',
        'admin' => 'red'
    ];
    
    return $colors[$role] ?? 'gray';
}

function get_role_icon($role) {
    $icons = [
        'student' => 'fa-user-graduate',
        'teacher' => 'fa-chalkboard-teacher',
        'program-chair' => 'fa-user-tie',
        'dean' => 'fa-user-md',
        'admin' => 'fa-user-cog'
    ];
    
    return $icons[$role] ?? 'fa-user';
}

function get_section_description($section, $role) {
    $descriptions = [
        'dashboard' => [
            'student' => 'Track your courses, assignments, and academic progress.',
            'teacher' => 'Manage your classes, create assignments, and view student progress.',
            'program-chair' => 'Oversee program curriculum, faculty assignments, and student performance.',
            'dean' => 'Monitor college programs, faculty performance, and institutional metrics.',
            'admin' => 'System administration, user management, and configuration.'
        ],
        'chat' => [
            'student' => 'Communicate with teachers and classmates.',
            'teacher' => 'Message students and colleagues.',
            'program-chair' => 'Connect with faculty and administration.',
            'dean' => 'Communicate with program chairs and faculty.',
            'admin' => 'System-wide communication.'
        ],
        'announcement' => [
            'student' => 'View important announcements from your teachers and the administration.',
            'teacher' => 'Create and manage class announcements.',
            'program-chair' => 'Post program-wide announcements.',
            'dean' => 'Share college-level announcements.',
            'admin' => 'System announcements and updates.'
        ],
        'quiz' => [
            'student' => 'Take quizzes and submit assignments.',
            'teacher' => 'Create and grade quizzes and assignments.',
            'program-chair' => 'Review quiz results and assignment statistics.'
        ],
        'enrollment' => [
            'student' => 'Enroll in courses for the upcoming semester.'
        ],
        'sections' => [
            'teacher' => 'Manage your class sections and students.'
        ],
        'assigned' => [
            'program-chair' => 'View and manage assigned subjects and faculty.'
        ],
        'ebooks' => [
            'student' => 'Access digital textbooks and learning materials.',
            'teacher' => 'Upload and manage e-books for your courses.',
            'program-chair' => 'Manage program e-book library.'
        ],
        'user-management' => [
            'admin' => 'Add, edit, and manage system users and their permissions.'
        ],
        'system-settings' => [
            'admin' => 'Configure system preferences, modules, and features.'
        ],
        'reports' => [
            'admin' => 'Generate and view system reports and analytics.'
        ]
    ];
    
    return $descriptions[$section][$role] ?? 'Manage your ' . str_replace('-', ' ', $section);
}

function load_section_content($section, $role, $user, $conn = null) {
    // Default dashboard content
    if ($section === 'dashboard') {
        return load_dashboard_content($role, $user);
    }
    
    // Load role-specific content
    $function_name = 'load_' . str_replace('-', '_', $section) . '_content';
    if (function_exists($function_name)) {
        return $function_name($role, $user, $conn);
    }
    
    // Fallback to default section content
    return load_default_section_content($section, $role, $user);
}

function load_dashboard_content($role, $user) {
    $content = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
    
    if ($role === 'student') {
        $content .= '
            <!-- Student Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-book-open text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Enrolled Courses</h3>
                        <p class="text-2xl font-bold text-blue-600">5</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Active courses this semester</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Pending Assignments</h3>
                        <p class="text-2xl font-bold text-green-600">3</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Due within 7 days</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Overall Average</h3>
                        <p class="text-2xl font-bold text-purple-600">89.5%</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Current semester performance</p>
            </div>
            
            <!-- Recent Activity -->
            <div class="md:col-span-2 lg:col-span-3 bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h3>
                <div class="space-y-4">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="mr-4">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">Assignment submitted: CS101 Programming Exercise</p>
                            <p class="text-sm text-gray-600">Submitted 2 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="mr-4">
                            <i class="fas fa-book text-blue-500"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">New material uploaded: Database Systems</p>
                            <p class="text-sm text-gray-600">Posted 1 day ago</p>
                        </div>
                    </div>
                </div>
            </div>
        ';
    } elseif ($role === 'teacher') {
        $content .= '
            <!-- Teacher Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Total Students</h3>
                        <p class="text-2xl font-bold text-blue-600">127</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Across all sections</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">To Grade</h3>
                        <p class="text-2xl font-bold text-yellow-600">42</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Pending submissions</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-chalkboard text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Active Classes</h3>
                        <p class="text-2xl font-bold text-green-600">6</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">This semester</p>
            </div>
        ';
    } elseif ($role === 'admin') {
        $content .= '
            <!-- Admin Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Total Users</h3>
                        <p class="text-2xl font-bold text-blue-600">1,842</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">Active users in system</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-server text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">System Status</h3>
                        <p class="text-2xl font-bold text-green-600">Online</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">All systems operational</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-database text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Storage Used</h3>
                        <p class="text-2xl font-bold text-purple-600">64%</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">2.1 GB of 3.3 GB</p>
            </div>
        ';
    }
    
    $content .= '</div>';
    return $content;
}

// ============================
// CHAT SYSTEM FUNCTIONS
// ============================

function load_chat_content($role, $user, $conn = null) {
    // Check if database connection exists
    if (!$conn) {
        return '<div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="text-center py-12">
                        <div class="inline-block p-4 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-database text-3xl text-red-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-2">Database Error</h3>
                        <p class="text-gray-600 mb-6">Unable to connect to database. Please try again later.</p>
                    </div>
                </div>';
    }
    
    // Handle form submissions
    $message = '';
    $message_type = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_friend'])) {
            $contact_identifier = trim($_POST['contact_identifier']);
            $result = add_contact_request($user['id'], $contact_identifier, $conn);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        elseif (isset($_POST['accept_request'])) {
            $request_id = $_POST['request_id'];
            $result = accept_contact_request($request_id, $user['id'], $conn);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        elseif (isset($_POST['reject_request'])) {
            $request_id = $_POST['request_id'];
            $result = reject_contact_request($request_id, $conn);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
        elseif (isset($_POST['send_message']) && isset($_POST['contact_id'])) {
            $contact_id = $_POST['contact_id'];
            $message_text = trim($_POST['message']);
            if (!empty($message_text)) {
                $result = send_message($user['id'], $contact_id, $message_text, $conn);
                if ($result) {
                    // Redirect to avoid form resubmission
                    header("Location: dashboard.php?section=chat&contact=" . $contact_id);
                    exit();
                }
            }
        }
    }
    
    // Handle clear chat
    if (isset($_GET['clear']) && isset($_GET['contact'])) {
        clear_chat_history($user['id'], $_GET['contact'], $conn);
        header("Location: dashboard.php?section=chat&contact=" . $_GET['contact']);
        exit();
    }
    
    // Get user's contacts
    $contacts = get_user_contacts($user['id'], $conn);
    $pending_requests = get_pending_requests($user['id'], $conn);
    
    // Get chat messages if a contact is selected
    $selected_contact_id = $_GET['contact'] ?? null;
    $selected_contact = null;
    $messages = [];
    
    if ($selected_contact_id) {
        $selected_contact = get_user_by_id($selected_contact_id, $conn);
        if ($selected_contact && is_contact($user['id'], $selected_contact_id, $conn)) {
            $messages = get_chat_messages($user['id'], $selected_contact_id, $conn);
            // Mark messages as read
            mark_messages_as_read($user['id'], $selected_contact_id, $conn);
        } else {
            $selected_contact_id = null;
        }
    }
    
    ob_start(); // Start output buffering
    ?>
    <div class="chat-container flex flex-col md:flex-row gap-6">
        <!-- Left Sidebar - Contacts -->
        <div class="md:w-1/3 bg-white rounded-xl shadow-sm p-4">
            <!-- Add Friend/Contact -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-bold text-gray-800 mb-3">Add Contact</h4>
                <form method="POST" class="space-y-3">
                    <div>
                        <input type="text" 
                               name="contact_identifier" 
                               placeholder="Student ID, Teacher ID, or Email"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    <button type="submit" 
                            name="add_friend"
                            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-user-plus mr-2"></i>Send Request
                    </button>
                </form>
                
                <?php if (!empty($message)): ?>
                    <div class="mt-3 p-2 bg-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-100 text-<?php echo $message_type === 'success' ? 'green' : 'red'; ?>-800 rounded text-sm">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pending Requests -->
            <?php if (!empty($pending_requests)): ?>
                <div class="mb-6">
                    <h4 class="font-bold text-gray-800 mb-3">Pending Requests (<?php echo count($pending_requests); ?>)</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center font-bold mr-3">
                                        <?php echo strtoupper(substr($request['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-sm"><?php echo htmlspecialchars($request['name']); ?></p>
                                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($request['role_name']); ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="accept_request" 
                                                class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="reject_request" 
                                                class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Contact List -->
            <div>
                <h4 class="font-bold text-gray-800 mb-3">Contacts (<?php echo count($contacts); ?>)</h4>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php if (empty($contacts)): ?>
                        <p class="text-gray-500 text-center py-4">No contacts yet. Add someone to chat!</p>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <a href="dashboard.php?section=chat&contact=<?php echo $contact['id']; ?>" 
                               class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition <?php echo $selected_contact_id == $contact['id'] ? 'bg-blue-50 border border-blue-200' : ''; ?>">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-3">
                                    <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium"><?php echo htmlspecialchars($contact['name']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($contact['role_name']); ?></p>
                                </div>
                                <?php if ($contact['unread_count'] > 0): ?>
                                    <span class="bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center">
                                        <?php echo $contact['unread_count']; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="md:w-2/3 bg-white rounded-xl shadow-sm flex flex-col" style="height: 600px;">
            <?php if ($selected_contact_id && $selected_contact): ?>
                <!-- Chat Header -->
                <div class="p-4 border-b flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-3">
                            <?php echo strtoupper(substr($selected_contact['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($selected_contact['name']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo get_user_role_name($selected_contact['role']); ?></p>
                        </div>
                    </div>
                    <button onclick="if(confirm('Are you sure you want to clear all messages?')) location.href='dashboard.php?section=chat&contact=<?php echo $selected_contact_id; ?>&clear=true'" 
                            class="text-red-500 hover:text-red-700 text-sm">
                        <i class="fas fa-trash-alt mr-1"></i>Clear Chat
                    </button>
                </div>
                
                <!-- Messages Area -->
                <div id="chat-messages" class="flex-1 p-4 overflow-y-auto">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-comments text-3xl mb-3 opacity-50"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="mb-3 <?php echo $msg['sender_id'] == $user['id'] ? 'chat-message-right' : 'chat-message-left'; ?>">
                                <div class="px-4 py-2 rounded-lg <?php echo $msg['sender_id'] == $user['id'] ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800'; ?>">
                                    <p class="break-words"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    <p class="text-xs opacity-75 mt-1 <?php echo $msg['sender_id'] == $user['id'] ? 'text-blue-200' : 'text-gray-500'; ?>">
                                        <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Message Input -->
                <div class="p-4 border-t">
                    <form method="POST" id="message-form" class="flex space-x-2">
                        <input type="hidden" name="contact_id" value="<?php echo $selected_contact_id; ?>">
                        <input type="text" 
                               name="message" 
                               placeholder="Type your message..."
                               class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required
                               autocomplete="off">
                        <button type="submit" 
                                name="send_message"
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="flex-1 flex flex-col items-center justify-center p-8">
                    <div class="text-center mb-6">
                        <div class="inline-block p-4 rounded-full bg-blue-100 mb-4">
                            <i class="fas fa-comments text-3xl text-blue-500"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-700 mb-2">Welcome to Chat</h3>
                        <p class="text-gray-600 mb-6">Select a contact to start messaging or add new contacts.</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-search text-blue-500 text-xl mb-2"></i>
                                <h4 class="font-bold">Find Contacts</h4>
                                <p class="text-sm text-gray-600">Search by ID or email</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-comment text-green-500 text-xl mb-2"></i>
                                <h4 class="font-bold">Instant Messaging</h4>
                                <p class="text-sm text-gray-600">Real-time communication</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-user-friends text-purple-500 text-xl mb-2"></i>
                                <h4 class="font-bold">Connect</h4>
                                <p class="text-sm text-gray-600">With classmates & teachers</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered content
}

// ============================
// CHAT DATABASE FUNCTIONS
// ============================

function add_contact_request($user_id, $identifier, $conn) {
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    try {
        // Search for user by student_id, teacher_id, or email
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE 
                              student_id = ? OR teacher_id = ? OR email = ?");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if ($user['id'] == $user_id) {
            return ['success' => false, 'message' => 'You cannot add yourself'];
        }
        
        // Check if request already exists
        $stmt = $conn->prepare("SELECT id FROM contact_requests WHERE 
                              (requester_id = ? AND receiver_id = ?) OR 
                              (requester_id = ? AND receiver_id = ?)");
        $stmt->execute([$user_id, $user['id'], $user['id'], $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Request already exists'];
        }
        
        // Check if already contacts
        $stmt = $conn->prepare("SELECT id FROM user_contacts WHERE 
                              (user_id = ? AND contact_id = ?) OR 
                              (user_id = ? AND contact_id = ?)");
        $stmt->execute([$user_id, $user['id'], $user['id'], $user_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Already contacts'];
        }
        
        // Create request
        $stmt = $conn->prepare("INSERT INTO contact_requests (requester_id, receiver_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$user_id, $user['id']]);
        return ['success' => true, 'message' => 'Friend request sent successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error sending request: ' . $e->getMessage()];
    }
}

function accept_contact_request($request_id, $user_id, $conn) {
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Get request details
        $stmt = $conn->prepare("SELECT requester_id, receiver_id FROM contact_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$request_id, $user_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            return ['success' => false, 'message' => 'Request not found'];
        }
        
        // Update request status
        $stmt = $conn->prepare("UPDATE contact_requests SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Add to contacts for both users
        $stmt = $conn->prepare("INSERT IGNORE INTO user_contacts (user_id, contact_id) VALUES (?, ?), (?, ?)");
        $stmt->execute([
            $request['requester_id'], $request['receiver_id'],
            $request['receiver_id'], $request['requester_id']
        ]);
        
        $conn->commit();
        return ['success' => true, 'message' => 'Contact added successfully'];
    } catch (PDOException $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Error accepting request'];
    }
}

function reject_contact_request($request_id, $conn) {
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    try {
        $stmt = $conn->prepare("UPDATE contact_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$request_id]);
        return ['success' => true, 'message' => 'Request rejected'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error rejecting request'];
    }
}

function get_user_contacts($user_id, $conn) {
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.role, 
                   (SELECT COUNT(*) FROM chat_messages 
                    WHERE sender_id = u.id AND receiver_id = ? AND read_status = 0
                   ) as unread_count
            FROM user_contacts uc
            JOIN users u ON uc.contact_id = u.id
            WHERE uc.user_id = ?
            ORDER BY u.name ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add role names
        foreach ($contacts as &$contact) {
            $contact['role_name'] = get_user_role_name($contact['role']);
        }
        
        return $contacts;
    } catch (PDOException $e) {
        return [];
    }
}

function get_pending_requests($user_id, $conn) {
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT cr.id, u.name, u.role, cr.created_at
            FROM contact_requests cr
            JOIN users u ON cr.requester_id = u.id
            WHERE cr.receiver_id = ? AND cr.status = 'pending'
            ORDER BY cr.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($requests as &$request) {
            $request['role_name'] = get_user_role_name($request['role']);
        }
        
        return $requests;
    } catch (PDOException $e) {
        return [];
    }
}

function get_user_by_id($user_id, $conn) {
    if (!$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function is_contact($user_id, $contact_id, $conn) {
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT id FROM user_contacts WHERE user_id = ? AND contact_id = ?");
        $stmt->execute([$user_id, $contact_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

function get_chat_messages($user_id, $contact_id, $conn) {
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT cm.*
            FROM chat_messages cm
            WHERE (sender_id = ? AND receiver_id = ?) OR 
                  (sender_id = ? AND receiver_id = ?)
            ORDER BY cm.created_at ASC
            LIMIT 100
        ");
        $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function send_message($sender_id, $receiver_id, $message, $conn) {
    if (!$conn) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO chat_messages (sender_id, receiver_id, message) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$sender_id, $receiver_id, trim($message)]);
    } catch (PDOException $e) {
        return false;
    }
}

function mark_messages_as_read($user_id, $contact_id, $conn) {
    if (!$conn) {
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE chat_messages 
            SET read_status = 1, read_at = CURRENT_TIMESTAMP
            WHERE receiver_id = ? AND sender_id = ? AND read_status = 0
        ");
        $stmt->execute([$user_id, $contact_id]);
    } catch (PDOException $e) {
        // Silent fail
    }
}

function clear_chat_history($user_id, $contact_id, $conn) {
    if (!$conn) {
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM chat_messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR 
                  (sender_id = ? AND receiver_id = ?)
        ");
        $stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);
    } catch (PDOException $e) {
        // Silent fail
    }
}

function load_default_section_content($section, $role, $user) {
    $section_title = ucfirst(str_replace('-', ' ', $section));
    
    return '
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="text-center py-12">
                <div class="inline-block p-4 rounded-full bg-gray-100 mb-4">
                    <i class="fas fa-cogs text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-700 mb-2">' . $section_title . ' Section</h3>
                <p class="text-gray-600 mb-6">This section is currently under development.</p>
                <p class="text-gray-500 text-sm">Content for ' . $role . ' role will be implemented here.</p>
            </div>
        </div>
    ';
}
?>
