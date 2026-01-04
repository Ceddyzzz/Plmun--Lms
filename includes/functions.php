<?php
require_once 'config.php';
require_once 'auth.php';

/**
 * Get quick actions based on user role
 */
function get_quick_actions($role) {
    $actions = [
        ROLE_STUDENT => '
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
        ROLE_TEACHER => '
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
        ROLE_PROGRAM_CHAIR => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-chalkboard-teacher text-blue-500 mr-3"></i>
                <span class="font-medium text-gray-800">Assign Subjects</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-clipboard-list text-green-500 mr-3"></i>
                <span class="font-medium text-gray-800">Review Curriculum</span>
            </button>
        ',
        ROLE_DEAN => '
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-university text-purple-500 mr-3"></i>
                <span class="font-medium text-gray-800">Program Reports</span>
            </button>
            <button class="w-full flex items-center p-3 mb-2 bg-gray-50 hover:bg-gray-100 rounded-lg transition text-left">
                <i class="fas fa-user-plus text-blue-500 mr-3"></i>
                <span class="font-medium text-gray-800">Faculty Management</span>
            </button>
        ',
        ROLE_ADMIN => '
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
    
    return $actions[$role] ?? $actions[ROLE_STUDENT];
}

/**
 * Get section description based on role
 */
function get_section_description($section, $role) {
    $descriptions = [
        'dashboard' => [
            ROLE_STUDENT => 'Track your courses, assignments, and academic progress.',
            ROLE_TEACHER => 'Manage your classes, create assignments, and view student progress.',
            ROLE_PROGRAM_CHAIR => 'Oversee program curriculum, faculty assignments, and student performance.',
            ROLE_DEAN => 'Monitor college programs, faculty performance, and institutional metrics.',
            ROLE_ADMIN => 'System administration, user management, and configuration.'
        ],
        'chat' => [
            ROLE_STUDENT => 'Communicate with teachers and classmates.',
            ROLE_TEACHER => 'Message students and colleagues.',
            ROLE_PROGRAM_CHAIR => 'Connect with faculty and administration.',
            ROLE_DEAN => 'Communicate with program chairs and faculty.',
            ROLE_ADMIN => 'System-wide communication.'
        ],
        'announcement' => [
            ROLE_STUDENT => 'View important announcements from your teachers and the administration.',
            ROLE_TEACHER => 'Create and manage class announcements.',
            ROLE_PROGRAM_CHAIR => 'Post program-wide announcements.',
            ROLE_DEAN => 'Share college-level announcements.',
            ROLE_ADMIN => 'System announcements and updates.'
        ],
        'quiz' => [
            ROLE_STUDENT => 'Take quizzes and submit assignments.',
            ROLE_TEACHER => 'Create and grade quizzes and assignments.',
            ROLE_PROGRAM_CHAIR => 'Review quiz results and assignment statistics.'
        ],
        'enrollment' => [
            ROLE_STUDENT => 'Enroll in courses for the upcoming semester.'
        ],
        'sections' => [
            ROLE_TEACHER => 'Manage your class sections and students.'
        ],
        'assigned' => [
            ROLE_PROGRAM_CHAIR => 'View and manage assigned subjects and faculty.'
        ],
        'ebooks' => [
            ROLE_STUDENT => 'Access digital textbooks and learning materials.',
            ROLE_TEACHER => 'Upload and manage e-books for your courses.',
            ROLE_PROGRAM_CHAIR => 'Manage program e-book library.'
        ],
        'user-management' => [
            ROLE_ADMIN => 'Add, edit, and manage system users and their permissions.'
        ],
        'system-settings' => [
            ROLE_ADMIN => 'Configure system preferences, modules, and features.'
        ],
        'reports' => [
            ROLE_ADMIN => 'Generate and view system reports and analytics.'
        ]
    ];
    
    return $descriptions[$section][$role] ?? 'Manage your ' . str_replace('-', ' ', $section);
}

/**
 * Load dynamic content based on section and role
 */
function load_section_content($section, $role, $user) {
    // Default dashboard content
    if ($section === 'dashboard') {
        return load_dashboard_content($role, $user);
    }
    
    // Load role-specific content
    $function_name = 'load_' . str_replace('-', '_', $section) . '_content';
    if (function_exists($function_name)) {
        return $function_name($role, $user);
    }
    
    // Fallback to default section content
    return load_default_section_content($section, $role, $user);
}

/**
 * Load dashboard content based on role
 */
function load_dashboard_content($role, $user) {
    $content = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">';
    
    if ($role === ROLE_STUDENT) {
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
    } elseif ($role === ROLE_TEACHER) {
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
    } elseif ($role === ROLE_ADMIN) {
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
    } else {
        // Default for other roles
        $content .= '
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-center py-8">
                    <div class="inline-block p-4 rounded-full bg-blue-100 mb-4">
                        <i class="fas fa-user-tie text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Welcome, ' . $user['name'] . '!</h3>
                    <p class="text-gray-600">Your dashboard is ready. Start managing your tasks.</p>
                </div>
            </div>
        ';
    }
    
    $content .= '</div>';
    return $content;
}

/**
 * Default section content
 */
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
                <p class="text-gray-500 text-sm">Content for ' . get_user_role_name($role) . ' role will be implemented here.</p>
            </div>
        </div>
    ';
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format date for display
 */
function format_date($date) {
    return date('F j, Y g:i A', strtotime($date));
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message_type, $message) {
    $_SESSION['message_type'] = $message_type;
    $_SESSION['message'] = $message;
    header('Location: ' . $url);
    exit();
}

/**
 * Display flash message if exists
 */
function display_flash_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        $classes = [
            'success' => 'bg-green-100 text-green-700',
            'error' => 'bg-red-100 text-red-700',
            'warning' => 'bg-yellow-100 text-yellow-700',
            'info' => 'bg-blue-100 text-blue-700'
        ];
        
        $class = $classes[$type] ?? $classes['info'];
        
        echo '<div class="mb-4 p-3 rounded-lg ' . $class . '">' . htmlspecialchars($message) . '</div>';
        
        unset($_SESSION['message_type']);
        unset($_SESSION['message']);
    }
}
?>
