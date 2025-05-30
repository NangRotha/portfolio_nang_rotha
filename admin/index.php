<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'portfolio_db');

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$login_error = "";
$is_logged_in = false;
$skill_message = "";
$project_message = "";
$skill_errors = [];
$project_errors = [];

// Check if user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $is_logged_in = true;
}

// Login functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // For demo purposes - in real app use password_hash and database
    $valid_username = "admin";
    $valid_password = "password123";
    
    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $is_logged_in = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Invalid username or password";
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Fetch data if logged in
if ($is_logged_in) {
    // Projects data
    $projects = $conn->query("SELECT * FROM projects") or die($conn->error);
    $project_count = $projects->num_rows;
    
    // Skills data
    $skills = $conn->query("SELECT * FROM skills") or die($conn->error);
    $skill_count = $skills->num_rows;
    
    // Messages data
    $messages = $conn->query("SELECT * FROM contacts") or die($conn->error);
    $message_count = $messages->num_rows;
    
    // Handle skill CRUD operations
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Add new skill
        if (isset($_POST['add_skill'])) {
            $skill = trim($_POST['skill']);
            $proficiency = trim($_POST['proficiency']);
            
            if (empty($skill)) {
                $skill_errors['skill'] = "Skill name is required.";
            }
            if (!is_numeric($proficiency) || $proficiency < 0 || $proficiency > 100) {
                $skill_errors['proficiency'] = "Proficiency must be between 0 and 100.";
            }
            
            if (empty($skill_errors)) {
                $stmt = $conn->prepare("INSERT INTO skills (skill, proficiency) VALUES (?, ?)");
                $stmt->bind_param("si", $skill, $proficiency);
                if ($stmt->execute()) {
                    $skill_message = "Skill added successfully!";
                    $skills = $conn->query("SELECT * FROM skills") or die($conn->error);
                    $skill_count = $skills->num_rows;
                } else {
                    $skill_message = "Error adding skill: " . $conn->error;
                }
                $stmt->close();
            }
        }
        // Update existing skill
        elseif (isset($_POST['update_skill'])) {
            $id = intval($_POST['id']);
            $skill = trim($_POST['skill']);
            $proficiency = trim($_POST['proficiency']);
            
            if (empty($skill)) {
                $skill_errors['skill'] = "Skill name is required.";
            }
            if (!is_numeric($proficiency) || $proficiency < 0 || $proficiency > 100) {
                $skill_errors['proficiency'] = "Proficiency must be between 0 and 100.";
            }
            
            if ($id > 0 && empty($skill_errors)) {
                $stmt = $conn->prepare("UPDATE skills SET skill = ?, proficiency = ? WHERE id = ?");
                $stmt->bind_param("sii", $skill, $proficiency, $id);
                if ($stmt->execute()) {
                    $skill_message = "Skill updated successfully!";
                    $skills = $conn->query("SELECT * FROM skills") or die($conn->error);
                    $skill_count = $skills->num_rows;
                } else {
                    $skill_message = "Error updating skill: " . $conn->error;
                }
                $stmt->close();
            }
        }
        // Add new project
        elseif (isset($_POST['add_project'])) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            $project_date = trim($_POST['project_date']);
            $github_link = trim($_POST['github_link']);
            $demo_link = trim($_POST['demo_link']);
            $image_url = trim($_POST['image_url']);
            
            if (empty($title)) {
                $project_errors['title'] = "Project title is required.";
            }
            if (empty($category)) {
                $project_errors['category'] = "Category is required.";
            }
            if (empty($project_date)) {
                $project_errors['project_date'] = "Project date is required.";
            }
            
            // Handle image upload
            $image_path = null;
            if (!empty($_FILES['image_file']['name'])) {
                $target_dir = "uploads/";
                $image_name = time() . "_" . basename($_FILES['image_file']['name']);
                $target_file = $target_dir . $image_name;
                $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Validate file
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($image_file_type, $allowed_types)) {
                    $project_errors['image'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
                } elseif ($_FILES['image_file']['size'] > 5000000) { // 5MB limit
                    $project_errors['image'] = "Image file is too large. Maximum size is 5MB.";
                } else {
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                        $image_path = $target_file;
                    } else {
                        $project_errors['image'] = "Error uploading image.";
                    }
                }
            } elseif (!empty($image_url)) {
                // Validate URL
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $image_path = $image_url;
                } else {
                    $project_errors['image'] = "Invalid image URL.";
                }
            }
            
            if (empty($project_errors)) {
                $stmt = $conn->prepare("INSERT INTO projects (title, description, category, project_date, github_link, demo_link, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $title, $description, $category, $project_date, $github_link, $demo_link, $image_path);
                if ($stmt->execute()) {
                    $project_message = "Project added successfully!";
                    $projects = $conn->query("SELECT * FROM projects") or die($conn->error);
                    $project_count = $projects->num_rows;
                } else {
                    $project_message = "Error adding project: " . $conn->error;
                }
                $stmt->close();
            }
        }
        // Update existing project
        elseif (isset($_POST['update_project'])) {
            $id = intval($_POST['id']);
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            $project_date = trim($_POST['project_date']);
            $github_link = trim($_POST['github_link']);
            $demo_link = trim($_POST['demo_link']);
            $image_url = trim($_POST['image_url']);
            
            if (empty($title)) {
                $project_errors['title'] = "Project title is required.";
            }
            if (empty($category)) {
                $project_errors['category'] = "Category is required.";
            }
            if (empty($project_date)) {
                $project_errors['project_date'] = "Project date is required.";
            }
            
            // Handle image upload
            $image_path = null;
            if (!empty($_FILES['image_file']['name'])) {
                $target_dir = "uploads/";
                $image_name = time() . "_" . basename($_FILES['image_file']['name']);
                $target_file = $target_dir . $image_name;
                $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Validate file
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($image_file_type, $allowed_types)) {
                    $project_errors['image'] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
                } elseif ($_FILES['image_file']['size'] > 5000000) {
                    $project_errors['image'] = "Image file is too large. Maximum size is 5MB.";
                } else {
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                        $image_path = $target_file;
                    } else {
                        $project_errors['image'] = "Error uploading image.";
                    }
                }
            } elseif (!empty($image_url)) {
                // Validate URL
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $image_path = $image_url;
                } else {
                    $project_errors['image'] = "Invalid image URL.";
                }
            } else {
                // Retain existing image if no new image is provided
                $result = $conn->query("SELECT image_url FROM projects WHERE id = $id");
                if ($result->num_rows > 0) {
                    $image_path = $result->fetch_assoc()['image_url'];
                }
            }
            
            if ($id > 0 && empty($project_errors)) {
                $stmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, category = ?, project_date = ?, github_link = ?, demo_link = ?, image_url = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $title, $description, $category, $project_date, $github_link, $demo_link, $image_path, $id);
                if ($stmt->execute()) {
                    $project_message = "Project updated successfully!";
                    $projects = $conn->query("SELECT * FROM projects") or die($conn->error);
                    $project_count = $projects->num_rows;
                } else {
                    $project_message = "Error updating project: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    
    // Delete skill
    if (isset($_GET['delete_skill'])) {
        $id = intval($_GET['delete_skill']);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM skills WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $skill_message = "Skill deleted successfully!";
                $skills = $conn->query("SELECT * FROM skills") or die($conn->error);
                $skill_count = $skills->num_rows;
            } else {
                $skill_message = "Error deleting skill: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    // Delete project
    if (isset($_GET['delete_project'])) {
        $id = intval($_GET['delete_project']);
        if ($id > 0) {
            // Optionally delete the image file from server
            $result = $conn->query("SELECT image_url FROM projects WHERE id = $id");
            if ($result->num_rows > 0) {
                $image_path = $result->fetch_assoc()['image_url'];
                if ($image_path && file_exists($image_path) && strpos($image_path, 'uploads/') === 0) {
                    unlink($image_path);
                }
            }
            $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $project_message = "Project deleted successfully!";
                $projects = $conn->query("SELECT * FROM projects") or die($conn->error);
                $project_count = $projects->num_rows;
            } else {
                $project_message = "Error deleting project: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
// Delete message
if (isset($_GET['delete_message'])) {
    $id = intval($_GET['delete_message']);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message_delete = "Message deleted successfully!";
            $messages = $conn->query("SELECT * FROM contacts") or die($conn->error);
            $message_count = $messages->num_rows;
        } else {
            $message_delete = "Error deleting message: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NANG ROTHA | Portfolio Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6c63ff',
                        secondary: '#4a44b5',
                        dark: '#1a1c28',
                        darker: '#151721',
                        accent: '#ff6584',
                        success: '#4caf50',
                        danger: '#f44336',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #6c63ff, #ff6584);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .sidebar {
            transition: transform 0.3s ease;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .form-input {
            transition: all 0.2s ease;
        }
        .form-input:focus {
            background-color: #2d2f3b;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
        }
        .form-input-error {
            border-color: #f44336 !important;
            background-color: #f44336/10;
        }
        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #d1d5db;
        }
        .form-error {
            font-size: 0.75rem;
            color: #f44336;
            margin-top: 0.25rem;
        }
        .modal {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .form-scrollable {
            max-height: 60vh;
            overflow-y: auto;
            padding-right: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .form-scrollable::-webkit-scrollbar {
            width: 6px;
        }
        .form-scrollable::-webkit-scrollbar-track {
            background: #2d2f3b;
            border-radius: 3px;
        }
        .form-scrollable::-webkit-scrollbar-thumb {
            background: #6c63ff;
            border-radius: 3px;
        }
        .form-scrollable::-webkit-scrollbar-thumb:hover {
            background: #4a44b5;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 50;
                width: 250px;
            }
            .main-content {
                margin-left: 0;
            }
            .form-scrollable {
                max-height: 50vh;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-darker to-dark min-h-screen text-gray-200">
    <?php if (!$is_logged_in): ?>
        <!-- Login Page -->
        <div class="min-h-screen flex items-center justify-center p-4 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80')">
            <div class="bg-dark/90 backdrop-blur-sm rounded-2xl shadow-2xl p-8 w-full max-w-md border border-gray-700">
                <div class="text-center mb-10">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-white">NR</span>
                    </div>
                    <h1 class="text-4xl font-extrabold gradient-text mb-2">NANG ROTHA</h1>
                    <p class="text-gray-400">Portfolio Dashboard</p>
                </div>
                
                <?php if ($login_error): ?>
                    <div class="bg-red-500/20 border border-red-500 rounded-lg p-4 mb-6 text-center">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['login_required'])): ?>
                    <div class="bg-yellow-500/20 border border-yellow-500 rounded-lg p-4 mb-6 text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Please login to access the dashboard.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-6">
                        <label for="username" class="block form-label mb-2">Username</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                <i class="fas fa-user"></i>
                            </span>
                            <input 
                                type="text" 
                                id="username"
                                name="username" 
                                class="form-input w-full pl-10 pr-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Enter username"
                                required
                                autocomplete="off"
                                aria-label="Username"
                            >
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <label for="password" class="block form-label mb-2">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                id="password"
                                name="password" 
                                class="form-input w-full pl-10 pr-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Enter password"
                                required
                                autocomplete="off"
                                aria-label="Password"
                            >
                        </div>
                    </div>
                    
                    <button 
                        type="submit" 
                        name="login" 
                        class="w-full bg-gradient-to-r from-primary to-secondary py-3 rounded-lg font-semibold text-white hover:opacity-90 transition transform hover:-translate-y-0.5 shadow-lg"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                    </button>
                    
                    <div class="mt-6 text-center text-sm text-gray-500">
                        <p>Demo credentials: <span class="text-gray-300">admin / password123</span></p>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Dashboard Layout -->
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <div id="sidebar" class="sidebar w-64 bg-darker border-r border-gray-800 flex flex-col md:w-20 md:hover:w-64 group transition-all fixed md:static z-50">
                <div class="p-5 border-b border-gray-800 flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center">
                        <span class="text-white font-bold">NR</span>
                    </div>
                    <div class="md:hidden group-hover:block">
                        <div class="logo-text text-xl font-bold gradient-text">NANG ROTHA</div>
                        <div class="text-xs text-gray-500">Portfolio Dashboard</div>
                    </div>
                </div>
                
                <nav class="flex-1 p-4 space-y-1">
                    <a href="#dashboard" class="flex items-center space-x-3 p-3 rounded-lg bg-gray-800 text-white">
                        <i class="fas fa-tachometer-alt w-6 text-center"></i>
                        <span class="nav-text md:hidden group-hover:block">Dashboard</span>
                    </a>
                    <a href="#projects" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="fas fa-project-diagram w-6 text-center"></i>
                        <span class="nav-text md:hidden group-hover:block">Projects</span>
                    </a>
                    <a href="#skills" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="fas fa-code w-6 text-center"></i>
                        <span class="nav-text md:hidden group-hover:block">Skills</span>
                    </a>
                    <a href="#messages" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition">
                        <i class="fas fa-envelope w-6 text-center"></i>
                        <span class="nav-text md:hidden group-hover:block">Messages</span>
                    </a>
                    <a href="?logout=1" class="flex items-center space-x-3 p-3 rounded-lg text-gray-400 hover:bg-red-500/10 hover:text-red-500 transition">
                        <i class="fas fa-sign-out-alt w-6 text-center"></i>
                        <span class="nav-text md:hidden group-hover:block">Logout</span>
                    </a>
                </nav>
                
                <div class="p-4 border-t border-gray-800 text-center text-xs text-gray-600 md:hidden group-hover:block">
                    © 2025 NANG ROTHA Portfolio
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1 p-4 sm:p-6 main-content md:ml-20">
                <!-- Mobile Menu Toggle -->
                <button id="menu-toggle" class="md:hidden text-gray-400 hover:text-primary mb-4">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                
                <!-- Header -->
                <div class="flex justify-between items-center mb-8 pb-4 border-b border-gray-800">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold">Dashboard Overview</h1>
                        <p class="text-gray-500 text-sm sm:text-base">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center">
                            <span class="text-white font-bold">NR</span>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div id="dashboard" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6 mb-8">
                    <div class="card bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6 hover:border-primary/30 transition transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center mr-4">
                                <i class="fas fa-project-diagram text-primary text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-400 text-sm">Total Projects</h3>
                                <p class="text-2xl sm:text-3xl font-bold"><?php echo $project_count; ?></p>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm">Portfolio projects created and maintained</p>
                    </div>
                    
                    <div class="card bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6 hover:border-primary/30 transition transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center mr-4">
                                <i class="fas fa-code text-primary text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-400 text-sm">Skills</h3>
                                <p class="text-2xl sm:text-3xl font-bold"><?php echo $skill_count; ?></p>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm">Technical expertise mastered and utilized</p>
                    </div>
                    
                    <div class="card bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6 hover:border-primary/30 transition transform hover:-translate-y-1">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center mr-4">
                                <i class="fas fa-envelope text-primary text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-400 text-sm">Messages</h3>
                                <p class="text-2xl sm:text-3xl font-bold"><?php echo $message_count; ?></p>
                            </div>
                        </div>
                        <p class="text-gray-500 text-sm">Client inquiries and messages</p>
                    </div>
                </div>
                
                <!-- Messages Section -->
                <div id="messages" class="bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6 mb-8">
                    <div class="flex justify-between items-center mb-4 sm:mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold">Recent Messages</h2>
                        <div class="text-sm text-gray-500"><?php echo $message_count; ?> total messages</div>
                    </div>
                    
                    <div class="space-y-4 overflow-x-auto">
                        <?php while($row = $messages->fetch_assoc()): ?>
                        <div class="bg-gray-900/50 border-l-4 border-primary rounded-lg p-4">
                            <div class="flex flex-col sm:flex-row justify-between">
                                <h3 class="font-bold text-base sm:text-lg"><?php echo htmlspecialchars($row['subject']); ?></h3>
                                <span class="text-xs text-gray-500"><?php echo $row['created_at']; ?></span>
                            </div>
                            <p class="text-gray-400 my-2 text-sm sm:text-base"><?php echo substr(htmlspecialchars($row['message']), 0, 100); ?>...</p>
                            <div class="flex justify-between text-xs sm:text-sm">
                                <div class="text-gray-500">From: <?php echo htmlspecialchars($row['name']); ?> &lt;<?php echo htmlspecialchars($row['email']); ?>&gt;</div>
                                <a href="?delete_message=<?php echo $row['id']; ?>" 
                                   class="text-red-500 hover:text-red-400"
                                   onclick="return confirm('Are you sure you want to delete this message?')">
                                   <i class="fas fa-trash mr-1"></i>Delete
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Projects and Skills Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-8 mb-8">
                    <!-- Projects -->
                    <div id="projects" class="bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl font-bold">Projects</h2>
                            <button 
                                onclick="openProjectModal()"
                                class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm flex items-center"
                            >
                                <i class="fas fa-plus mr-2"></i> Add Project
                            </button>
                        </div>
                        
                        <?php if (!empty($project_message)): ?>
                            <div class="mb-4 p-3 rounded-lg <?php echo strpos($project_message, 'success') !== false ? 'bg-green-500/20 border border-green-500' : 'bg-red-500/20 border border-red-500' ?>">
                                <?php echo $project_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm sm:text-base">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b border-gray-700">
                                        <th class="pb-3 pr-2">Image</th>
                                        <th class="pb-3 pr-2">Project</th>
                                        <th class="pb-3 pr-2">Category</th>
                                        <th class="pb-3 pr-2">Date</th>
                                        <th class="pb-3 pr-2">Links</th>
                                        <th class="pb-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $projects->data_seek(0);
                                    while($row = $projects->fetch_assoc()): 
                                    ?>
                                    <tr class="border-b border-gray-800 hover:bg-gray-900/50">
                                        <td class="py-4 pr-2">
                                            <?php if (!empty($row['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-12 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gray-700 rounded flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 pr-2">
                                            <div class="font-medium"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <div class="text-xs sm:text-sm text-gray-500"><?php echo substr(htmlspecialchars($row['description']), 0, 30); ?>...</div>
                                        </td>
                                        <td class="py-4 pr-2">
                                            <span class="bg-primary/20 text-primary text-xs px-2 py-1 rounded-full">
                                                <?php echo htmlspecialchars($row['category']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 pr-2 text-xs sm:text-sm text-gray-500"><?php echo $row['project_date']; ?></td>
                                        <td class="py-4 pr-2">
                                            <div class="flex space-x-2">
                                                <?php if (!empty($row['github_link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['github_link']); ?>" target="_blank" class="text-gray-400 hover:text-primary">
                                                        <i class="fab fa-github"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($row['demo_link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['demo_link']); ?>" target="_blank" class="text-gray-400 hover:text-accent">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4">
                                            <div class="flex space-x-2">
                                                <button 
                                                    onclick="openEditProjectModal(
                                                        <?php echo $row['id']; ?>, 
                                                        '<?php echo addslashes($row['title']); ?>', 
                                                        '<?php echo addslashes($row['description']); ?>', 
                                                        '<?php echo addslashes($row['category']); ?>', 
                                                        '<?php echo $row['project_date']; ?>',
                                                        '<?php echo addslashes($row['github_link']); ?>',
                                                        '<?php echo addslashes($row['demo_link']); ?>',
                                                        '<?php echo addslashes($row['image_url']); ?>'
                                                    )"
                                                    class="w-8 h-8 rounded-full bg-gray-700 hover:bg-primary/20 flex items-center justify-center text-gray-400 hover:text-primary"
                                                >
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>
                                                <a 
                                                    href="?delete_project=<?php echo $row['id']; ?>" 
                                                    class="w-8 h-8 rounded-full bg-gray-700 hover:bg-red-500/20 flex items-center justify-center text-gray-400 hover:text-red-500"
                                                    onclick="return confirm('Are you sure you want to delete this project?')"
                                                >
                                                    <i class="fas fa-trash text-xs"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Skills -->
                    <div id="skills" class="bg-gray-800/50 backdrop-blur border border-gray-700 rounded-xl p-4 sm:p-6">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-xl sm:text-2xl font-bold">Skills</h2>
                            <button 
                                onclick="openSkillModal()"
                                class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm flex items-center"
                            >
                                <i class="fas fa-plus mr-2"></i> Add Skill
                            </button>
                        </div>
                        
                        <?php if (!empty($skill_message)): ?>
                            <div class="mb-4 p-3 rounded-lg <?php echo strpos($skill_message, 'success') !== false ? 'bg-green-500/20 border border-green-500' : 'bg-red-500/20 border border-red-500' ?>">
                                <?php echo $skill_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-4">
                            <?php 
                            $skills->data_seek(0);
                            while($row = $skills->fetch_assoc()): 
                            ?>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium text-sm sm:text-base"><?php echo htmlspecialchars($row['skill']); ?></span>
                                    <div class="flex items-center">
                                        <span class="text-xs sm:text-sm text-gray-500 mr-2"><?php echo $row['proficiency']; ?>%</span>
                                        <button 
                                            onclick="openEditSkillModal(
                                                <?php echo $row['id']; ?>, 
                                                '<?php echo addslashes($row['skill']); ?>', 
                                                <?php echo $row['proficiency']; ?>
                                            )"
                                            class="text-gray-500 hover:text-primary mr-2"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a 
                                            href="?delete_skill=<?php echo $row['id']; ?>" 
                                            class="text-gray-500 hover:text-red-500"
                                            onclick="return confirm('Are you sure you want to delete this skill?')"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div 
                                        class="bg-gradient-to-r from-primary to-accent h-2 rounded-full" 
                                        style="width: <?php echo $row['proficiency']; ?>%"
                                    ></div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Skill Modal -->
                <div id="skill-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
                    <div class="modal bg-gray-800 border border-gray-700 rounded-2xl p-6 w-full max-w-md sm:max-w-lg shadow-2xl">
                        <h3 id="modal-title" class="text-xl sm:text-2xl font-bold gradient-text mb-6">Add New Skill</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="id" id="skill-id" value="">
                            <div class="mb-5">
                                <label for="skill-name" class="block form-label mb-2">Skill Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="fas fa-code"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="skill" 
                                        id="skill-name" 
                                        class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($skill_errors['skill']) ? 'form-input-error' : ''; ?>" 
                                        placeholder="e.g., JavaScript" 
                                        required
                                        aria-label="Skill Name"
                                        aria-describedby="skill-error"
                                    >
                                </div>
                                <?php if (isset($skill_errors['skill'])): ?>
                                    <p id="skill-error" class="form-error"><?php echo $skill_errors['skill']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="mb-6">
                                <label for="skill-proficiency" class="block form-label mb-2">Proficiency (0-100%)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="fas fa-percentage"></i>
                                    </span>
                                    <input 
                                        type="number" 
                                        name="proficiency" 
                                        id="skill-proficiency" 
                                        min="0" 
                                        max="100" 
                                        class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($skill_errors['proficiency']) ? 'form-input-error' : ''; ?>" 
                                        placeholder="e.g., 90" 
                                        required
                                        aria-label="Proficiency"
                                        aria-describedby="proficiency-error"
                                    >
                                </div>
                                <?php if (isset($skill_errors['proficiency'])): ?>
                                    <p id="proficiency-error" class="form-error"><?php echo $skill_errors['proficiency']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                                <button 
                                    type="button" 
                                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-3 rounded-lg transition font-medium"
                                    onclick="closeSkillModal()"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    name="add_skill" 
                                    id="add-skill-btn" 
                                    class="flex-1 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-white py-3 rounded-lg transition font-semibold shadow-md"
                                >
                                    Add Skill
                                </button>
                                <button 
                                    type="submit" 
                                    name="update_skill" 
                                    id="update-skill-btn" 
                                    class="flex-1 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-white py-3 rounded-lg transition font-semibold shadow-md hidden"
                                >
                                    Update Skill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Project Modal -->
                <div id="project-modal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 hidden">
                    <div class="modal bg-gray-800 border border-gray-700 rounded-2xl p-4 sm:p-6 w-full max-w-md sm:max-w-lg shadow-2xl flex flex-col">
                        <h3 id="project-modal-title" class="text-xl sm:text-2xl font-bold gradient-text mb-4">Add New Project</h3>
                        <form method="POST" action="" enctype="multipart/form-data" class="flex flex-col flex-1">
                            <input type="hidden" name="id" id="project-id" value="">
                            <div class="form-scrollable">
                                <div class="mb-5">
                                    <label for="project-title" class="block form-label mb-2">Project Title</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fas fa-project-diagram"></i>
                                        </span>
                                        <input 
                                            type="text" 
                                            name="title" 
                                            id="project-title" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($project_errors['title']) ? 'form-input-error' : ''; ?>" 
                                            placeholder="e.g., Portfolio Website" 
                                            required
                                            aria-label="Project Title"
                                            aria-describedby="title-error"
                                        >
                                    </div>
                                    <?php if (isset($project_errors['title'])): ?>
                                        <p id="title-error" class="form-error"><?php echo $project_errors['title']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-5">
                                    <label for="project-description" class="block form-label mb-2">Description</label>
                                    <div class="relative">
                                        <span class="absolute top-3 left-3 text-gray-500">
                                            <i class="fas fa-align-left"></i>
                                        </span>
                                        <textarea 
                                            name="description" 
                                            id="project-description" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary min-h-[100px]"
                                            placeholder="e.g., A responsive portfolio website"
                                            aria-label="Project Description"
                                        ></textarea>
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label for="project-category" class="block form-label mb-2">Category</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <input 
                                            type="text" 
                                            name="category" 
                                            id="project-category" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($project_errors['category']) ? 'form-input-error' : ''; ?>" 
                                            placeholder="e.g., Web Development" 
                                            required
                                            aria-label="Category"
                                            aria-describedby="category-error"
                                        >
                                    </div>
                                    <?php if (isset($project_errors['category'])): ?>
                                        <p id="category-error" class="form-error"><?php echo $project_errors['category']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-5">
                                    <label for="project-date" class="block form-label mb-2">Project Date</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <input 
                                            type="date" 
                                            name="project_date" 
                                            id="project-date" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($project_errors['project_date']) ? 'form-input-error' : ''; ?>" 
                                            required
                                            aria-label="Project Date"
                                            aria-describedby="date-error"
                                        >
                                    </div>
                                    <?php if (isset($project_errors['project_date'])): ?>
                                        <p id="date-error" class="form-error"><?php echo $project_errors['project_date']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-5">
                                    <label for="project-github-link" class="block form-label mb-2">GitHub Link (optional)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fab fa-github"></i>
                                        </span>
                                        <input 
                                            type="url" 
                                            name="github_link" 
                                            id="project-github-link" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                            placeholder="e.g., https://github.com/username/repo"
                                            aria-label="GitHub Link"
                                        >
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label for="project-demo-link" class="block form-label mb-2">Demo Link (optional)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fas fa-external-link-alt"></i>
                                        </span>
                                        <input 
                                            type="url" 
                                            name="demo_link" 
                                            id="project-demo-link" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                            placeholder="e.g., https://demo.example.com"
                                            aria-label="Demo Link"
                                        >
                                    </div>
                                </div>
                                <div class="mb-5">
                                    <label for="project-image-file" class="block form-label mb-2">Upload Image (optional)</label>
                                    <input 
                                        type="file" 
                                        name="image_file" 
                                        id="project-image-file" 
                                        class="form-input w-full py-2 px-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($project_errors['image']) ? 'form-input-error' : ''; ?>"
                                        accept="image/jpeg,image/png,image/gif"
                                        aria-label="Upload Image"
                                        aria-describedby="image-error"
                                    >
                                    <?php if (isset($project_errors['image'])): ?>
                                        <p id="image-error" class="form-error"><?php echo $project_errors['image']; ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-5">
                                    <label for="project-image-url" class="block form-label mb-2">Or Image URL (optional)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                            <i class="fas fa-link"></i>
                                        </span>
                                        <input 
                                            type="url" 
                                            name="image_url" 
                                            id="project-image-url" 
                                            class="form-input w-full pl-10 pr-4 py-3 bg-gray-700/50 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary <?php echo isset($project_errors['image']) ? 'form-input-error' : ''; ?>"
                                            placeholder="e.g., https://example.com/image.jpg"
                                            aria-label="Image URL"
                                            aria-describedby="image-error"
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 pt-4 border-t border-gray-700">
                                <button 
                                    type="button" 
                                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-3 rounded-lg transition font-medium"
                                    onclick="closeProjectModal()"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    name="add_project" 
                                    id="add-project-btn" 
                                    class="flex-1 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-white py-3 rounded-lg transition font-semibold shadow-md"
                                >
                                    Add Project
                                </button>
                                <button 
                                    type="submit" 
                                    name="update_project" 
                                    id="update-project-btn" 
                                    class="flex-1 bg-gradient-to-r from-primary to-secondary hover:opacity-90 text-white py-3 rounded-lg transition font-semibold shadow-md hidden"
                                >
                                    Update Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="mt-8 sm:mt-10 pt-6 border-t border-gray-800 text-center text-xs sm:text-sm text-gray-500">
                    <p>NANG ROTHA Portfolio Dashboard © 2025 | All rights reserved</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                const icon = menuToggle.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });
        }
        
        // Smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    // Close mobile sidebar
                    if (window.innerWidth < 768) {
                        sidebar.classList.add('hidden');
                        const icon = menuToggle.querySelector('i');
                        icon.classList.add('fa-bars');
                        icon.classList.remove('fa-times');
                    }
                }
            });
        });
        
        // Active navigation highlighting
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('div[id]');
            const navLinks = document.querySelectorAll('.sidebar nav a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('bg-gray-800', 'text-white');
                if (link.getAttribute('href').substring(1) === current) {
                    link.classList.add('bg-gray-800', 'text-white');
                }
            });
        });
        
        // Set dashboard as active by default
        window.addEventListener('load', function() {
            const dashboardLink = document.querySelector('.sidebar nav a[href="#dashboard"]');
            if (dashboardLink) {
                dashboardLink.classList.add('bg-gray-800', 'text-white');
            }
        });
        
        // Skill Modal Functions
        function openSkillModal() {
            const modal = document.getElementById('skill-modal');
            modal.classList.remove('hidden');
            document.getElementById('modal-title').textContent = 'Add New Skill';
            document.getElementById('add-skill-btn').classList.remove('hidden');
            document.getElementById('update-skill-btn').classList.add('hidden');
            document.getElementById('skill-id').value = '';
            document.getElementById('skill-name').value = '';
            document.getElementById('skill-proficiency').value = '';
            document.getElementById('skill-name').focus();
        }
        
        function openEditSkillModal(id, skill, proficiency) {
            const modal = document.getElementById('skill-modal');
            modal.classList.remove('hidden');
            document.getElementById('modal-title').textContent = 'Edit Skill';
            document.getElementById('add-skill-btn').classList.add('hidden');
            document.getElementById('update-skill-btn').classList.remove('hidden');
            document.getElementById('skill-id').value = id;
            document.getElementById('skill-name').value = skill;
            document.getElementById('skill-proficiency').value = proficiency;
            document.getElementById('skill-name').focus();
        }
        
        function closeSkillModal() {
            document.getElementById('skill-modal').classList.add('hidden');
        }
        
        // Project Modal Functions
        function openProjectModal() {
            const modal = document.getElementById('project-modal');
            modal.classList.remove('hidden');
            document.getElementById('project-modal-title').textContent = 'Add New Project';
            document.getElementById('add-project-btn').classList.remove('hidden');
            document.getElementById('update-project-btn').classList.add('hidden');
            document.getElementById('project-id').value = '';
            document.getElementById('project-title').value = '';
            document.getElementById('project-description').value = '';
            document.getElementById('project-category').value = '';
            document.getElementById('project-date').value = '';
            document.getElementById('project-github-link').value = '';
            document.getElementById('project-demo-link').value = '';
            document.getElementById('project-image-url').value = '';
            document.getElementById('project-image-file').value = '';
            document.getElementById('project-title').focus();
        }
        
        function openEditProjectModal(id, title, description, category, project_date, github_link, demo_link, image_url) {
            const modal = document.getElementById('project-modal');
            modal.classList.remove('hidden');
            document.getElementById('project-modal-title').textContent = 'Edit Project';
            document.getElementById('add-project-btn').classList.add('hidden');
            document.getElementById('update-project-btn').classList.remove('hidden');
            document.getElementById('project-id').value = id;
            document.getElementById('project-title').value = title;
            document.getElementById('project-description').value = description;
            document.getElementById('project-category').value = category;
            document.getElementById('project-date').value = project_date;
            document.getElementById('project-github-link').value = github_link;
            document.getElementById('project-demo-link').value = demo_link;
            document.getElementById('project-image-url').value = image_url;
            document.getElementById('project-image-file').value = '';
            document.getElementById('project-title').focus();
        }
        
        function closeProjectModal() {
            document.getElementById('project-modal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const skillModal = document.getElementById('skill-modal');
            const projectModal = document.getElementById('project-modal');
            if (event.target === skillModal) {
                closeSkillModal();
            }
            if (event.target === projectModal) {
                closeProjectModal();
            }
        });
    </script>
</body>
</html>