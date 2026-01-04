<?php
// Configure session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Now start the session
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (login($username, $password, $role)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid credentials or role mismatch';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLMUN Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        /* Background with overlay */
        .bg-plmun-campus {
            background-image: linear-gradient(rgba(0, 51, 102, 0.85), rgba(0, 51, 102, 0.9)), url('campus.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Role selection animation */
        .role-option {
            transition: all 0.3s ease;
        }
        
        .role-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="min-h-screen bg-plmun-campus text-white">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Left Banner - PLMUN Info -->
        <div class="lg:w-2/5 p-8 lg:p-12 flex flex-col justify-center bg-gradient-to-r from-blue-900/90 to-blue-800/70">
            <div class="animate-fade-in-up">
                <!-- PLMUN Logo/Icon -->
                <div class="flex items-center mb-8">
                    <div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center mr-6 border-4 border-white/20">
                        <i class="fas fa-university text-4xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl lg:text-5xl font-bold mb-2">PLMUN</h1>
                        <p class="text-xl lg:text-2xl font-light opacity-90">Pamantasan ng Lungsod ng Muntinlupa</p>
                    </div>
                </div>
                
                <!-- Tagline -->
                <div class="mb-10">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4">Learning Management System</h2>
                    <p class="text-lg opacity-90 leading-relaxed">
                        Empowering the future through innovative education. 
                        Access your courses, resources, and academic tools all in one place.
                    </p>
                </div>
                
                <!-- Features -->
                <div class="space-y-6">
                    <div class="flex items-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
                        <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center mr-4">
                            <i class="fas fa-laptop-house text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Virtual Classroom</h3>
                            <p class="opacity-80">Attend classes from anywhere</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center mr-4">
                            <i class="fas fa-books text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Digital Library</h3>
                            <p class="opacity-80">Access e-books and resources</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
                        <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Progress Tracking</h3>
                            <p class="opacity-80">Monitor your academic journey</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Note -->
                <div class="mt-12 pt-6 border-t border-white/20">
                    <p class="text-sm opacity-80">
                        <i class="fas fa-info-circle mr-2"></i>
                        Need help? Contact the IT Department at it-support@plmun.edu.ph
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="lg:w-3/5 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md animate-fade-in-up" style="animation-delay: 0.2s">
                <!-- Login Card -->
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20">
                    <!-- Login Header -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-500 mb-4">
                            <i class="fas fa-user-lock text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold">Sign In to Your Account</h3>
                        <p class="opacity-80 mt-2">Enter your credentials to continue</p>
                    </div>
                    
                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-red-500/20 backdrop-blur-sm border border-red-500/30 rounded-xl">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-300 mr-3"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" class="space-y-6">
                        <!-- Role Selection -->
                        <div>
                            <label class="block text-sm font-medium mb-3">Select Your Role</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Student -->
                                <label class="role-option">
                                    <input type="radio" name="role" value="student" class="hidden peer" required>
                                    <div class="p-4 rounded-xl bg-white/5 hover:bg-white/10 border-2 border-transparent peer-checked:border-blue-400 peer-checked:bg-blue-500/20 transition-all cursor-pointer text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center mb-2">
                                                <i class="fas fa-user-graduate text-xl"></i>
                                            </div>
                                            <span class="font-medium">Student</span>
                                        </div>
                                    </div>
                                </label>
                                
                                <!-- Teacher -->
                                <label class="role-option">
                                    <input type="radio" name="role" value="teacher" class="hidden peer">
                                    <div class="p-4 rounded-xl bg-white/5 hover:bg-white/10 border-2 border-transparent peer-checked:border-red-400 peer-checked:bg-red-500/20 transition-all cursor-pointer text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center mb-2">
                                                <i class="fas fa-chalkboard-teacher text-xl"></i>
                                            </div>
                                            <span class="font-medium">Teacher</span>
                                        </div>
                                    </div>
                                </label>
                                
                                <!-- Program Chair -->
                                <label class="role-option">
                                    <input type="radio" name="role" value="program-chair" class="hidden peer">
                                    <div class="p-4 rounded-xl bg-white/5 hover:bg-white/10 border-2 border-transparent peer-checked:border-yellow-400 peer-checked:bg-yellow-500/20 transition-all cursor-pointer text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-full bg-yellow-500 flex items-center justify-center mb-2">
                                                <i class="fas fa-users text-xl"></i>
                                            </div>
                                            <span class="font-medium">Program Chair</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Username Field -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Username / ID Number</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    name="username" 
                                    class="w-full pl-10 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
                                    placeholder="Enter your username"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Password Field -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password"
                                    class="w-full pl-10 pr-12 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition"
                                    placeholder="Enter your password"
                                    required
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition"
                                >
                                    <i id="password-toggle-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Remember Me & Show Password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="show-password" 
                                    class="w-4 h-4 text-blue-500 bg-white/10 border-white/20 rounded focus:ring-blue-400 focus:ring-2"
                                    onchange="toggleAllPasswords()"
                                >
                                <span class="ml-2 text-sm">Show password</span>
                            </label>
                            
                            <a href="#" class="text-sm text-blue-300 hover:text-blue-200 transition">
                                Forgot password?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign In
                        </button>
                        
                        <!-- Demo Credentials -->
                        <div class="mt-6 p-4 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                            <h4 class="font-bold mb-2 flex items-center">
                                <i class="fas fa-vial mr-2"></i>
                                Demo Credentials
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between items-center p-2 bg-black/20 rounded">
                                    <span class="font-medium">Student:</span>
                                    <span class="font-mono">ID: 2023-00123 | Pass: student123</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-black/20 rounded">
                                    <span class="font-medium">Teacher:</span>
                                    <span class="font-mono">ID: T-0456 | Pass: teacher123</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-black/20 rounded">
                                    <span class="font-medium">Program Chair:</span>
                                    <span class="font-mono">ID: PC-0789 | Pass: program123</span>
                                </div>
                            </div>
                            <p class="text-xs mt-2 opacity-80 flex items-center">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Check "Show password" to view passwords
                            </p>
                        </div>
                        
                        <!-- Add this in your index.php, around line where demo credentials are shown -->
<div class="mt-6 text-center">
    <p class="text-sm opacity-80">
        Don't have an account? 
        <a href="register.php" class="text-blue-300 hover:text-blue-200 font-medium underline">
            Create new account
        </a>
    </p>
    <p class="text-xs opacity-60 mt-2">
        Students and faculty members can register
    </p>
</div>
                        <!-- Terms -->
                        <div class="pt-4 border-t border-white/20 text-center">
                            <p class="text-xs opacity-80">
                                By signing in, you agree to the 
                                <a href="#" class="text-blue-300 hover:text-blue-200">Terms of Service</a>
                                 and 
                                <a href="#" class="text-blue-300 hover:text-blue-200">Privacy Policy</a>
                            </p>
                        </div>
                    </form>
                </div>
                
                <!-- System Status -->
                <div class="mt-6 flex items-center justify-center text-sm opacity-80">
                    <div class="flex items-center">
                        <div class="w-2 h-2 rounded-full bg-green-500 mr-2"></div>
                        <span>System Status: <span class="font-medium">Online</span></span>
                    </div>
                    <span class="mx-2">•</span>
                    <span>v1.0.0</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill demo credentials on role selection
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const usernameField = document.querySelector('input[name="username"]');
                const passwordField = document.getElementById('password');
                
                if (this.value === 'student') {
                    usernameField.value = '2023-00123';
                    passwordField.value = 'student123';
                } else if (this.value === 'teacher') {
                    usernameField.value = 'T-0456';
                    passwordField.value = 'teacher123';
                } else if (this.value === 'program-chair') {
                    usernameField.value = 'PC-0789';
                    passwordField.value = 'program123';
                }
                
                // Trigger form validation styling
                usernameField.dispatchEvent(new Event('input'));
                passwordField.dispatchEvent(new Event('input'));
            });
        });

        // Toggle password visibility for single field
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Toggle all password fields
        function toggleAllPasswords() {
            const showAll = document.getElementById('show-password').checked;
            const passwordField = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            
            if (showAll) {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Add visual feedback on form interactions
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-blue-400');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-blue-400');
            });
        });

        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[name="username"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        });
    </script>
</body>
</html>
