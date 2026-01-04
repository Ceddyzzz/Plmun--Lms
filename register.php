<?php
// Configure session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Now start the session
session_start();
include_once 'includes/config.php';
include_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}

// Check if registration is allowed
if (!ALLOW_REGISTRATION) {
    header('Location: index.php?error=registration_disabled');
    exit();
}

$error = '';
$success = '';
$form_data = [];
$generated_ids = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $form_data = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'email' => $_POST['email'] ?? '',
        'name' => $_POST['name'] ?? '',
        'role' => $_POST['role'] ?? ROLE_STUDENT,
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'terms' => $_POST['terms'] ?? ''
    ];
    
    // Add role-specific fields
    if ($form_data['role'] == ROLE_STUDENT) {
        $form_data['program'] = $_POST['program'] ?? '';
        $form_data['year_level'] = $_POST['year_level'] ?? '';
    } else {
        $form_data['department'] = $_POST['department'] ?? '';
        $form_data['position'] = $_POST['position'] ?? '';
    }
    
    // Validate passwords match
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $error = 'Passwords do not match';
    }
    // Check terms agreement
    elseif (empty($form_data['terms'])) {
        $error = 'You must agree to the Terms of Service and Privacy Policy';
    } else {
        // Attempt registration
        $result = register_user($form_data);
        
        if ($result['success']) {
            $success = $result['message'];
            
            // Store generated IDs for display
            if (isset($result['student_id'])) {
                $generated_ids['Student ID'] = $result['student_id'];
            }
            if (isset($result['employee_id'])) {
                $generated_ids['Employee ID'] = $result['employee_id'];
            }
            if (isset($result['email'])) {
                $generated_ids['Email Address'] = $result['email'];
            }
            
            // Clear form data on success
            $form_data = [];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PLMUN LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        .bg-plmun-campus {
            background-image: linear-gradient(rgba(0, 51, 102, 0.85), rgba(0, 51, 102, 0.9)), url('campus.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .step-line {
            height: 2px;
            flex: 1;
            margin: 0 10px;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Role verification modal */
        .role-verification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .role-verification-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        /* Shake animation for wrong selection */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease;
        }
        
        /* Password strength */
        .password-strength-meter {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .password-strength-0 { width: 0%; background-color: #ef4444; }
        .password-strength-1 { width: 25%; background-color: #f97316; }
        .password-strength-2 { width: 50%; background-color: #eab308; }
        .password-strength-3 { width: 75%; background-color: #22c55e; }
        .password-strength-4 { width: 100%; background-color: #16a34a; }
    </style>
</head>
<body class="min-h-screen bg-plmun-campus text-white">
    <!-- Role Verification Modal -->
    <div id="roleVerificationModal" class="role-verification-modal">
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20 max-w-md w-full mx-4">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-500 mb-4">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold" id="verificationTitle">Confirm Your Role</h3>
                <p class="opacity-80 mt-2" id="verificationMessage"></p>
            </div>
            
            <div class="space-y-4">
                <div class="p-4 bg-white/5 rounded-xl">
                    <p class="font-medium mb-2">Selected Role:</p>
                    <div class="flex items-center">
                        <div id="selectedRoleIcon" class="w-10 h-10 rounded-full flex items-center justify-center mr-3"></div>
                        <span id="selectedRoleName" class="text-lg font-bold"></span>
                    </div>
                </div>
                
                <div class="p-4 bg-yellow-500/20 border border-yellow-500/30 rounded-xl">
                    <p class="text-sm">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important:</strong> Make sure you select the correct role. 
                        Students and teachers have different access rights and email formats.
                    </p>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button 
                        onclick="cancelRoleSelection()"
                        class="flex-1 px-4 py-3 bg-gray-500/50 hover:bg-gray-500 text-white font-medium rounded-xl transition"
                    >
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button 
                        onclick="confirmRoleSelection()"
                        class="flex-1 px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl transition"
                    >
                        <i class="fas fa-check mr-2"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            <!-- Header with Back Button -->
            <div class="mb-6 flex items-center justify-between">
                <a href="index.php" class="flex items-center text-white hover:text-blue-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </a>
                <div class="text-center">
                    <h1 class="text-3xl font-bold">PLMUN LMS Registration</h1>
                    <p class="opacity-90">Create Your Official PLMUN Account</p>
                </div>
                <div class="w-24"></div> <!-- Spacer for centering -->
            </div>
            
            <!-- Registration Card -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl p-8 border border-white/20">
                <!-- Step Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-center mb-4">
                        <div class="flex items-center">
                            <div class="step-indicator bg-blue-500 text-white">1</div>
                            <div class="step-line bg-blue-500"></div>
                            <div class="step-indicator bg-blue-500/50 text-white">2</div>
                            <div class="step-line bg-blue-500/30"></div>
                            <div class="step-indicator bg-blue-500/30 text-white">3</div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">Account Info</span>
                        <span class="font-medium">Personal Details</span>
                        <span class="font-medium">Role Info</span>
                    </div>
                </div>
                
                <!-- Success Message with Generated IDs -->
                <?php if ($success): ?>
                    <div class="mb-6 p-6 bg-gradient-to-r from-green-500/20 to-blue-500/20 backdrop-blur-sm border border-green-500/30 rounded-xl">
                        <div class="flex items-start">
                            <div class="mr-4">
                                <i class="fas fa-check-circle text-3xl text-green-300"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-2">Registration Successful!</h3>
                                <p class="mb-4"><?php echo htmlspecialchars($success); ?></p>
                                
                                <?php if (!empty($generated_ids)): ?>
                                    <div class="bg-black/30 p-4 rounded-lg mb-4">
                                        <h4 class="font-bold mb-3">Your Official PLMUN Credentials:</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($generated_ids as $label => $value): ?>
                                                <div class="flex justify-between items-center p-2 bg-white/5 rounded">
                                                    <span class="font-medium"><?php echo htmlspecialchars($label); ?>:</span>
                                                    <span class="font-mono bg-black/50 px-3 py-1 rounded"><?php echo htmlspecialchars($value); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="p-4 bg-yellow-500/20 border border-yellow-500/30 rounded-lg">
                                        <p class="text-sm">
                                            <i class="fas fa-lightbulb mr-2"></i>
                                            <strong>Important:</strong> Save these credentials! Your email will be used for all official communications.
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-6">
                                    <a href="index.php" 
                                       class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                                        <i class="fas fa-sign-in-alt mr-2"></i>
                                        Go to Login
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Error Message -->
                <?php if ($error && !$success): ?>
                    <div class="mb-6 p-4 bg-red-500/20 backdrop-blur-sm border border-red-500/30 rounded-xl">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-300 mr-3"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <!-- Registration Form -->
                <form method="POST" action="" id="registrationForm" class="space-y-6">
                    <!-- Hidden field for role verification -->
                    <input type="hidden" name="verified_role" id="verifiedRole" value="">
                    
                    <!-- Step 1: Account Information -->
                    <div id="step1" class="form-step active">
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b border-white/20">Account Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Username <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="username" 
                                        value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                                        class="w-full pl-10 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="Choose a username"
                                        required
                                        oninput="checkUsername()"
                                    >
                                </div>
                                <div id="username-feedback" class="text-xs mt-2"></div>
                            </div>
                            
                            <!-- Email (Read-only, will be generated) -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Email Address <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email"
                                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                                        class="w-full pl-10 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="Will be generated automatically"
                                        readonly
                                    >
                                </div>
                                <div id="email-feedback" class="text-xs mt-2 text-blue-300">
                                    Email will be generated based on your role and program/department
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Password <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password"
                                        class="w-full pl-10 pr-12 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="At least 6 characters"
                                        required
                                        oninput="checkPasswordStrength()"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition"
                                    >
                                        <i id="password-toggle-icon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <div id="password-strength-meter" class="password-strength-meter password-strength-0"></div>
                                    <div id="password-feedback" class="text-xs mt-1"></div>
                                </div>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Confirm Password <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="password" 
                                        name="confirm_password" 
                                        id="confirm_password"
                                        class="w-full pl-10 pr-12 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="Confirm your password"
                                        required
                                        oninput="checkPasswordMatch()"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('confirm_password')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition"
                                    >
                                        <i id="confirm-password-toggle-icon" class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="confirm-password-feedback" class="text-xs mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-white/20 flex justify-between">
                            <div></div> <!-- Spacer -->
                            <button 
                                type="button" 
                                onclick="nextStep()"
                                class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl transition"
                            >
                                Next Step <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Personal Information -->
                    <div id="step2" class="form-step">
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b border-white/20">Personal Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-2">
                                    Full Name <span class="text-red-400">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>"
                                    class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                    placeholder="Juan Dela Cruz"
                                    required
                                >
                            </div>
                            
                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Phone Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="tel" 
                                        name="phone" 
                                        value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                                        class="w-full pl-10 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="09123456789"
                                    >
                                </div>
                            </div>
                            
                            <!-- Birthdate -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Birthdate</label>
                                <input 
                                    type="date" 
                                    name="birthdate" 
                                    value="<?php echo htmlspecialchars($form_data['birthdate'] ?? ''); ?>"
                                    class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                    max="<?php echo date('Y-m-d'); ?>"
                                >
                            </div>
                            
                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium mb-2">Gender</label>
                                <select 
                                    name="gender" 
                                    class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white"
                                >
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($form_data['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($form_data['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($form_data['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-2">Address</label>
                                <textarea 
                                    name="address" 
                                    rows="2"
                                    class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300 resize-none"
                                    placeholder="Enter your complete address"
                                ><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-white/20 flex justify-between">
                            <button 
                                type="button" 
                                onclick="prevStep()"
                                class="px-6 py-3 bg-gray-500/50 hover:bg-gray-500 text-white font-medium rounded-xl transition"
                            >
                                <i class="fas fa-arrow-left mr-2"></i> Previous
                            </button>
                            <button 
                                type="button" 
                                onclick="nextStep()"
                                class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl transition"
                            >
                                Next Step <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Role Information -->
                    <div id="step3" class="form-step">
                        <h3 class="text-xl font-bold mb-6 pb-3 border-b border-white/20">Role Information</h3>
                        
                        <!-- Role Selection -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium mb-4">
                                I am registering as: <span class="text-red-400">*</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="roleSelection">
                                <!-- Student Role -->
                                <div class="role-selection">
                                    <input 
                                        type="radio" 
                                        name="role" 
                                        value="student" 
                                        id="role_student"
                                        class="hidden peer"
                                        <?php echo ($form_data['role'] ?? ROLE_STUDENT) == ROLE_STUDENT ? 'checked' : ''; ?>
                                        onchange="showRoleVerification('student')"
                                        required
                                    >
                                    <label for="role_student" class="cursor-pointer">
                                        <div class="p-6 rounded-xl bg-white/5 hover:bg-white/10 border-2 border-transparent peer-checked:border-blue-400 peer-checked:bg-blue-500/20 transition-all h-full">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center mb-4">
                                                    <i class="fas fa-user-graduate text-2xl"></i>
                                                </div>
                                                <h4 class="font-bold text-lg mb-2">Student</h4>
                                                <p class="text-sm opacity-80 text-center">
                                                    I am a student of PLMUN. I will get a student ID and program-based email.
                                                </p>
                                                <div class="mt-4 p-2 bg-blue-500/20 rounded-lg text-xs">
                                                    Email format: bscs@plmun.edu.ph
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Teacher Role -->
                                <div class="role-selection">
                                    <input 
                                        type="radio" 
                                        name="role" 
                                        value="teacher" 
                                        id="role_teacher"
                                        class="hidden peer"
                                        <?php echo ($form_data['role'] ?? '') == ROLE_TEACHER ? 'checked' : ''; ?>
                                        onchange="showRoleVerification('teacher')"
                                    >
                                    <label for="role_teacher" class="cursor-pointer">
                                        <div class="p-6 rounded-xl bg-white/5 hover:bg-white/10 border-2 border-transparent peer-checked:border-red-400 peer-checked:bg-red-500/20 transition-all h-full">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 rounded-full bg-red-500 flex items-center justify-center mb-4">
                                                    <i class="fas fa-chalkboard-teacher text-2xl"></i>
                                                </div>
                                                <h4 class="font-bold text-lg mb-2">Teacher</h4>
                                                <p class="text-sm opacity-80 text-center">
                                                    I am a faculty member of PLMUN. I will get an employee ID and department-based email.
                                                </p>
                                                <div class="mt-4 p-2 bg-red-500/20 rounded-lg text-xs">
                                                    Email format: prof.bscs@plmun.edu.ph
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Wrong selection message -->
                            <div id="wrongSelectionMessage" class="mt-4 p-4 bg-red-500/20 border border-red-500/30 rounded-xl hidden">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-300 mr-3"></i>
                                    <div>
                                        <p class="font-medium">Wrong selection detected!</p>
                                        <p class="text-sm opacity-90">You selected <span id="wrongRoleName" class="font-bold"></span>. If this is wrong, please cancel and select the correct role.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Student Fields -->
                        <div id="student-fields" class="<?php echo ($form_data['role'] ?? ROLE_STUDENT) == ROLE_STUDENT ? '' : 'hidden'; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium mb-2">
                                        Program <span class="text-red-400">*</span>
                                    </label>
                                    <select 
                                        name="program" 
                                        class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white"
                                        required
                                        onchange="updateGeneratedEmail()"
                                    >
                                        <option value="">Select Program</option>
                                        <option value="BS Computer Science" <?php echo ($form_data['program'] ?? '') == 'BS Computer Science' ? 'selected' : ''; ?>>BS Computer Science</option>
                                        <option value="BS Information Technology" <?php echo ($form_data['program'] ?? '') == 'BS Information Technology' ? 'selected' : ''; ?>>BS Information Technology</option>
                                        <option value="BS Business Administration" <?php echo ($form_data['program'] ?? '') == 'BS Business Administration' ? 'selected' : ''; ?>>BS Business Administration</option>
                                        <option value="BS Education" <?php echo ($form_data['program'] ?? '') == 'BS Education' ? 'selected' : ''; ?>>BS Education</option>
                                        <option value="BS Psychology" <?php echo ($form_data['program'] ?? '') == 'BS Psychology' ? 'selected' : ''; ?>>BS Psychology</option>
                                    </select>
                                    <div class="text-xs mt-2 text-blue-300">
                                        Your student ID will be generated after registration
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Year Level</label>
                                    <select 
                                        name="year_level" 
                                        class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white"
                                    >
                                        <option value="">Select Year Level</option>
                                        <option value="1st Year" <?php echo ($form_data['year_level'] ?? '') == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo ($form_data['year_level'] ?? '') == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo ($form_data['year_level'] ?? '') == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo ($form_data['year_level'] ?? '') == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="5th Year" <?php echo ($form_data['year_level'] ?? '') == '5th Year' ? 'selected' : ''; ?>>5th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Teacher Fields -->
                        <div id="teacher-fields" class="<?php echo ($form_data['role'] ?? '') == ROLE_TEACHER ? '' : 'hidden'; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium mb-2">
                                        Department <span class="text-red-400">*</span>
                                    </label>
                                    <select 
                                        name="department" 
                                        class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white"
                                        required
                                        onchange="updateGeneratedEmail()"
                                    >
                                        <option value="">Select Department</option>
                                        <option value="Computer Science" <?php echo ($form_data['department'] ?? '') == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Information Technology" <?php echo ($form_data['department'] ?? '') == 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Business Administration" <?php echo ($form_data['department'] ?? '') == 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Education" <?php echo ($form_data['department'] ?? '') == 'Education' ? 'selected' : ''; ?>>Education</option>
                                        <option value="Psychology" <?php echo ($form_data['department'] ?? '') == 'Psychology' ? 'selected' : ''; ?>>Psychology</option>
                                    </select>
                                    <div class="text-xs mt-2 text-blue-300">
                                        Your employee ID will be generated after registration
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium mb-2">Position</label>
                                    <input 
                                        type="text" 
                                        name="position" 
                                        value="<?php echo htmlspecialchars($form_data['position'] ?? ''); ?>"
                                        class="w-full px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-gray-300"
                                        placeholder="e.g., Assistant Professor"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preview of Generated Credentials -->
                        <div id="credentials-preview" class="mt-6 p-4 bg-black/30 rounded-xl border border-blue-500/30 hidden">
                            <h4 class="font-bold mb-3">Preview of Generated Credentials:</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center p-2 bg-white/5 rounded">
                                    <span class="font-medium">Role:</span>
                                    <span id="preview-role" class="font-bold"></span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white/5 rounded">
                                    <span class="font-medium">ID:</span>
                                    <span id="preview-id" class="font-mono">Will be generated</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white/5 rounded">
                                    <span class="font-medium">Email:</span>
                                    <span id="preview-email" class="font-mono">Will be generated</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="mt-8 p-4 bg-white/5 rounded-xl">
                            <label class="flex items-start">
                                <input 
                                    type="checkbox" 
                                    name="terms" 
                                    id="termsCheckbox"
                                    class="mt-1 mr-3 w-4 h-4 text-blue-500 bg-white/10 border-white/20 rounded focus:ring-blue-400"
                                    required
                                    onchange="validateForm()"
                                >
                                <span class="text-sm">
                                    I certify that I am a <span id="termsRole" class="font-bold">student/teacher</span> of PLMUN and I agree to the 
                                    <a href="#" class="text-blue-300 hover:text-blue-200 underline">Terms of Service</a> 
                                    and 
                                    <a href="#" class="text-blue-300 hover:text-blue-200 underline">Privacy Policy</a>. 
                                    I understand that misrepresenting my role may result in account suspension.
                                </span>
                            </label>
                        </div>
                        
                        <!-- Final Buttons -->
                        <div class="mt-8 pt-6 border-t border-white/20 flex justify-between">
                            <button 
                                type="button" 
                                onclick="prevStep()"
                                class="px-6 py-3 bg-gray-500/50 hover:bg-gray-500 text-white font-medium rounded-xl transition"
                            >
                                <i class="fas fa-arrow-left mr-2"></i> Previous
                            </button>
                            <button 
                                type="submit" 
                                id="submitButton"
                                class="px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium rounded-xl transition transform hover:-translate-y-1 hover:shadow-lg"
                                disabled
                            >
                                <i class="fas fa-user-plus mr-2"></i> Create Account
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Already have account -->
                <div class="mt-8 pt-6 border-t border-white/20 text-center">
                    <p class="text-sm opacity-80">
                        Already have an account? 
                        <a href="index.php" class="text-blue-300 hover:text-blue-200 font-medium underline">
                            Sign in here
                        </a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Registration Notice -->
            <div class="mt-6 text-center text-sm opacity-80">
                <p>
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php if (ADMIN_APPROVAL_REQUIRED): ?>
                        Note: New accounts require admin approval before activation.
                    <?php else: ?>
                        Note: Your account will be activated immediately after registration.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        let selectedRole = '';
        let wrongSelectionAttempts = 0;
        
        // Role Verification
        function showRoleVerification(role) {
            selectedRole = role;
            
            // Show verification modal
            const modal = document.getElementById('roleVerificationModal');
            const title = document.getElementById('verificationTitle');
            const message = document.getElementById('verificationMessage');
            const roleIcon = document.getElementById('selectedRoleIcon');
            const roleName = document.getElementById('selectedRoleName');
            
            if (role === 'student') {
                title.textContent = 'Confirm Student Registration';
                message.textContent = 'You are registering as a Student. Students receive program-based emails and student IDs.';
                roleIcon.innerHTML = '<i class="fas fa-user-graduate text-xl"></i>';
                roleIcon.className = 'w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center mr-3';
                roleName.textContent = 'Student';
            } else {
                title.textContent = 'Confirm Teacher Registration';
                message.textContent = 'You are registering as a Teacher. Teachers receive department-based emails and employee IDs.';
                roleIcon.innerHTML = '<i class="fas fa-chalkboard-teacher text-xl"></i>';
                roleIcon.className = 'w-10 h-10 rounded-full bg-red-500 flex items-center justify-center mr-3';
                roleName.textContent = 'Teacher';
            }
            
            modal.classList.add('active');
        }
        
        function confirmRoleSelection() {
            // Set the verified role
            document.getElementById('verifiedRole').value = selectedRole;
            
            // Hide modal
            document.getElementById('roleVerificationModal').classList.remove('active');
            
            // Show role fields
            toggleRoleFields();
            
            // Update terms text
            updateTermsText();
            
            // Show preview
            showCredentialsPreview();
            
            // Reset wrong selection attempts
            wrongSelectionAttempts = 0;
            document.getElementById('wrongSelectionMessage').classList.add('hidden');
        }
        
        function cancelRoleSelection() {
            // Hide modal
            document.getElementById('roleVerificationModal').classList.remove('active');
            
            // Uncheck radio button
            document.querySelector(`input[name="role"][value="${selectedRole}"]`).checked = false;
            
            // If this is the 2nd wrong attempt, show warning
            wrongSelectionAttempts++;
            if (wrongSelectionAttempts >= 2) {
                const wrongMessage = document.getElementById('wrongSelectionMessage');
                const wrongRoleName = document.getElementById('wrongRoleName');
                
                wrongRoleName.textContent = selectedRole === 'student' ? 'Student' : 'Teacher';
                wrongMessage.classList.remove('hidden');
                
                // Shake the role selection
                document.getElementById('roleSelection').classList.add('shake');
                setTimeout(() => {
                    document.getElementById('roleSelection').classList.remove('shake');
                }, 500);
            }
        }
        
        // Toggle role fields
        function toggleRoleFields() {
            const studentFields = document.getElementById('student-fields');
            const teacherFields = document.getElementById('teacher-fields');
            const isStudent = selectedRole === 'student';
            
            if (isStudent) {
                studentFields.classList.remove('hidden');
                teacherFields.classList.add('hidden');
            } else {
                studentFields.classList.add('hidden');
                teacherFields.classList.remove('hidden');
            }
        }
        
        // Update terms text based on role
        function updateTermsText() {
            const termsRole = document.getElementById('termsRole');
            termsRole.textContent = selectedRole === 'student' ? 'student' : 'teacher';
            termsRole.className = selectedRole === 'student' ? 'font-bold text-blue-300' : 'font-bold text-red-300';
        }
        
        // Show credentials preview
        function showCredentialsPreview() {
            const preview = document.getElementById('credentials-preview');
            const previewRole = document.getElementById('preview-role');
            const previewId = document.getElementById('preview-id');
            const previewEmail = document.getElementById('preview-email');
            
            preview.classList.remove('hidden');
            
            if (selectedRole === 'student') {
                previewRole.textContent = 'Student';
                previewRole.className = 'font-bold text-blue-300';
                previewId.textContent = 'Will be generated (Format: YYYY-00001)';
                previewEmail.textContent = 'Format: bscs@plmun.edu.ph';
            } else {
                previewRole.textContent = 'Teacher';
                previewRole.className = 'font-bold text-red-300';
                previewId.textContent = 'Will be generated (Format: T-0001)';
                previewEmail.textContent = 'Format: prof.bscs@plmun.edu.ph';
            }
        }
        
        // Update generated email preview
        function updateGeneratedEmail() {
            const role = selectedRole;
            let programOrDept = '';
            
            if (role === 'student') {
                programOrDept = document.querySelector('select[name="program"]').value;
                if (programOrDept) {
                    // Map program to email domain
                    const programMap = {
                        'BS Computer Science': 'bscs',
                        'BS Information Technology': 'bsit',
                        'BS Business Administration': 'bsba',
                        'BS Education': 'bsed',
                        'BS Psychology': 'bspsych'
                    };
                    
                    if (programMap[programOrDept]) {
                        document.getElementById('preview-email').textContent = programMap[programOrDept] + '@plmun.edu.ph';
                    }
                }
            } else if (role === 'teacher') {
                programOrDept = document.querySelector('select[name="department"]').value;
                if (programOrDept) {
                    // Map department to email domain
                    const deptMap = {
                        'Computer Science': 'prof.bscs',
                        'Information Technology': 'prof.bsit',
                        'Business Administration': 'prof.bsba',
                        'Education': 'prof.bsed',
                        'Psychology': 'prof.bspsych'
                    };
                    
                    if (deptMap[programOrDept]) {
                        document.getElementById('preview-email').textContent = deptMap[programOrDept] + '@plmun.edu.ph';
                    }
                }
            }
            
            validateForm();
        }
        
        // Step Navigation
        function nextStep() {
            if (validateStep(currentStep)) {
                document.getElementById(`step${currentStep}`).classList.remove('active');
                currentStep++;
                document.getElementById(`step${currentStep}`).classList.add('active');
                updateStepIndicator();
            }
        }
        
        function prevStep() {
            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep--;
            document.getElementById(`step${currentStep}`).classList.add('active');
            updateStepIndicator();
        }
        
        function updateStepIndicator() {
            const indicators = document.querySelectorAll('.step-indicator');
            const lines = document.querySelectorAll('.step-line');
            
            indicators.forEach((indicator, index) => {
                indicator.classList.remove('bg-blue-500', 'bg-blue-500/50', 'bg-blue-500/30');
                if (index + 1 < currentStep) {
                    indicator.classList.add('bg-blue-500');
                } else if (index + 1 === currentStep) {
                    indicator.classList.add('bg-blue-500');
                } else {
                    indicator.classList.add('bg-blue-500/30');
                }
            });
            
            lines.forEach((line, index) => {
                line.classList.remove('bg-blue-500', 'bg-blue-500/30');
                if (index + 1 < currentStep) {
                    line.classList.add('bg-blue-500');
                } else {
                    line.classList.add('bg-blue-500/30');
                }
            });
        }
        
        function validateStep(step) {
            let isValid = true;
            
            if (step === 1) {
                const username = document.querySelector('input[name="username"]');
                const password = document.querySelector('input[name="password"]');
                const confirmPassword = document.querySelector('input[name="confirm_password"]');
                
                if (!username.value.trim()) {
                    showError(username, 'Username is required');
                    isValid = false;
                }
                
                if (!password.value) {
                    showError(password, 'Password is required');
                    isValid = false;
                } else if (password.value.length < 6) {
                    showError(password, 'Password must be at least 6 characters');
                    isValid = false;
                }
                
                if (!confirmPassword.value) {
                    showError(confirmPassword, 'Please confirm your password');
                    isValid = false;
                } else if (password.value !== confirmPassword.value) {
                    showError(confirmPassword, 'Passwords do not match');
                    isValid = false;
                }
            }
            
            if (step === 2) {
                const name = document.querySelector('input[name="name"]');
                if (!name.value.trim()) {
                    showError(name, 'Full name is required');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function validateForm() {
            const submitButton = document.getElementById('submitButton');
            const termsCheckbox = document.getElementById('termsCheckbox');
            let isValid = true;
            
            // Check if role is selected and verified
            if (!selectedRole) {
                isValid = false;
            }
            
            // Check role-specific required fields
            if (selectedRole === 'student') {
                const program = document.querySelector('select[name="program"]');
                if (!program.value) {
                    isValid = false;
                }
            } else if (selectedRole === 'teacher') {
                const department = document.querySelector('select[name="department"]');
                if (!department.value) {
                    isValid = false;
                }
            }
            
            // Check terms
            if (!termsCheckbox.checked) {
                isValid = false;
            }
            
            submitButton.disabled = !isValid;
            return isValid;
        }
        
        function showError(input, message) {
            input.classList.add('border-red-400');
            const feedback = document.createElement('div');
            feedback.className = 'text-red-300 text-xs mt-1';
            feedback.textContent = message;
            
            // Remove existing error
            const existingError = input.parentElement.querySelector('.text-red-300');
            if (existingError) {
                existingError.remove();
            }
            
            input.parentElement.appendChild(feedback);
        }
        
        // Password Strength Checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const meter = document.getElementById('password-strength-meter');
            const feedback = document.getElementById('password-feedback');
            
            let strength = 0;
            let message = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update meter
            meter.className = `password-strength-meter password-strength-${strength}`;
            
            // Update message
            switch(strength) {
                case 0:
                    message = '';
                    break;
                case 1:
                case 2:
                    message = 'Weak password';
                    break;
                case 3:
                    message = 'Good password';
                    break;
                case 4:
                case 5:
                    message = 'Strong password';
                    break;
            }
            
            feedback.textContent = message;
        }
        
        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const feedback = document.getElementById('confirm-password-feedback');
            
            if (!confirmPassword) {
                feedback.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                feedback.textContent = '✓ Passwords match';
                feedback.className = 'text-green-300 text-xs mt-2';
            } else {
                feedback.textContent = '✗ Passwords do not match';
                feedback.className = 'text-red-300 text-xs mt-2';
            }
        }
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Check username availability
        function checkUsername() {
            const username = document.querySelector('input[name="username"]').value;
            const feedback = document.getElementById('username-feedback');
            
            if (username.length < 3) {
                feedback.textContent = 'Username must be at least 3 characters';
                feedback.className = 'text-yellow-300 text-xs mt-2';
                return;
            }
            
            // Simulate AJAX check
            setTimeout(() => {
                const takenUsernames = ['admin', 'test', 'user', '2023-00123', 'T-0456'];
                if (takenUsernames.includes(username.toLowerCase())) {
                    feedback.textContent = 'Username already taken';
                    feedback.className = 'text-red-300 text-xs mt-2';
                } else {
                    feedback.textContent = '✓ Username available';
                    feedback.className = 'text-green-300 text-xs mt-2';
                }
            }, 500);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateStepIndicator();
            
            // Auto-focus on first input
            const firstInput = document.querySelector('input[name="username"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
            
            // Check if there's a previously selected role
            const checkedRole = document.querySelector('input[name="role"]:checked');
            if (checkedRole) {
                selectedRole = checkedRole.value;
                showRoleVerification(selectedRole);
            }
        });
    </script>
</body>
</html>
