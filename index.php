<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "portfolio_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);

    // Insert into database
    $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        $success_message = "Message sent successfully!";
    } else {
        $error_message = "Error: " . $sql . "<br>" . $conn->error;
    }
    $stmt->close();
}

// Fetch projects from database
$projects = [];
$sql = "SELECT id, title, description, category, image_url, project_date, github_link, demo_link FROM projects ORDER BY project_date DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch skills from database
$skills = [];
$sql = "SELECT skill, proficiency FROM skills ORDER BY proficiency DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NANG ROTHA | Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6c63ff',
                        secondary: '#4a44b5',
                        dark: '#2a2a3c',
                        light: '#f8f9fa',
                        accent: '#ff6584',
                        success: '#4caf50',
                        warning: '#ff9800',
                        danger: '#f44336',
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-900 to-dark text-gray-200 dark:bg-gradient-to-br dark:from-gray-100 dark:to-gray-300 dark:text-gray-800 min-h-screen transition-colors duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <header class="py-5 flex justify-between items-center border-b border-gray-700/50 dark:border-gray-300/50">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white text-xl font-bold">
                    NR
                </div>
                <div class="text-2xl font-extrabold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent uppercase tracking-wide">
                    NANG ROTHA
                </div>
            </div>
            <nav class="hidden md:flex space-x-8">
                <a href="#dashboard" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium relative after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-primary after:transition-all hover:after:w-full">Dashboard</a>
                <a href="#projects" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium relative after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-primary after:transition-all hover:after:w-full">Projects</a>
                <a href="#skills" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium relative after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-primary after:transition-all hover:after:w-full">Skills</a>
                <a href="#contact" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium relative after:content-[''] after:absolute after:bottom-[-5px] after:left-0 after:w-0 after:h-[2px] after:bg-primary after:transition-all hover:after:w-full">Contact</a>
            </nav>
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" class="text-gray-400 hover:text-primary focus:outline-none" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button id="mobile-menu-btn" class="md:hidden text-gray-400 hover:text-primary focus:outline-none" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </header>
        <nav id="mobile-menu" class="hidden md:hidden flex-col space-y-4 bg-dark/95 dark:bg-light/95 p-4 absolute top-[80px] left-0 w-full border-b border-gray-700/50 dark:border-gray-300/50">
            <a href="#dashboard" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium py-2">Dashboard</a>
            <a href="#projects" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium py-2">Projects</a>
            <a href="#skills" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium py-2">Skills</a>
            <a href="#contact" class="text-gray-400 hover:text-white dark:hover:text-gray-900 font-medium py-2">Contact</a>
        </nav>

        <section class="py-20 text-center">
            <h1 class="text-4xl sm:text-5xl font-bold bg-gradient-to-r from-white to-primary bg-clip-text text-transparent mb-6">
                Creative Portfolio
            </h1>
            <p class="text-lg sm:text-xl text-gray-400 dark:text-gray-600 max-w-2xl mx-auto mb-10">
                Welcome to the NANG ROTHA portfolio. Showcase your projects, skills, and connect with clients all in one place.
            </p>
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="#projects" class="bg-primary text-white px-8 py-3 rounded-full font-semibold hover:bg-secondary hover:-translate-y-1 transition transform shadow-lg">
                    View Projects
                </a>
                <a href="#contact" class="border-2 border-primary text-primary px-8 py-3 rounded-full font-semibold hover:bg-primary hover:text-white hover:-translate-y-1 transition transform">
                    Get In Touch
                </a>
            </div>
        </section>

        <section id="dashboard" class="py-12">
            <h2 class="text-3xl font-bold text-center mb-12 relative after:content-[''] after:absolute after:bottom-[-10px] after:left-1/2 after:-translate-x-1/2 after:w-20 after:h-1 after:bg-primary after:rounded">
                Dashboard Overview
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl p-6 hover:-translate-y-2 hover:shadow-xl transition transform">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-project-diagram text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Total Projects</h3>
                            <p class="text-sm text-gray-400 dark:text-gray-600">All completed works</p>
                        </div>
                    </div>
                    <div class="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent"><?php echo count($projects); ?></div>
                </div>
                <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl p-6 hover:-translate-y-2 hover:shadow-xl transition transform">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-code text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Skills</h3>
                            <p class="text-sm text-gray-400 dark:text-gray-600">Technical expertise</p>
                        </div>
                    </div>
                    <div class="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent"><?php echo count($skills); ?></div>
                </div>
                <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl p-6 hover:-translate-y-2 hover:shadow-xl transition transform">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-users text-primary text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Clients</h3>
                            <p class="text-sm text-gray-400 dark:text-gray-600">Satisfied customers</p>
                        </div>
                    </div>
                    <div class="text-3xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">24+</div>
                </div>
            </div>
        </section>

        <section id="projects" class="py-12">
            <h2 class="text-3xl font-bold text-center mb-12 relative after:content-[''] after:absolute after:bottom-[-10px] after:left-1/2 after:-translate-x-1/2 after:w-20 after:h-1 after:bg-primary after:rounded">
                Featured Projects
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl overflow-hidden hover:-translate-y-2 transition transform">
                            <div class="h-48 bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-4xl">
                                <?php if (!empty($project['image_url'])): ?>
                                    <img src="./admin/<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-project-diagram"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <span class="inline-block bg-primary/20 text-primary text-xs px-3 py-1 rounded-full mb-4">
                                    <?php echo htmlspecialchars($project['category']); ?>
                                </span>
                                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($project['title']); ?></h3>
                                <p class="text-gray-400 dark:text-gray-600 mb-4"><?php echo htmlspecialchars($project['description']); ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-600">Date: <?php echo htmlspecialchars($project['project_date']); ?></span>
                                    <div class="flex space-x-3">
                                        <?php if (!empty($project['github_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($project['github_link']); ?>" target="_blank" class="text-gray-400 hover:text-primary" aria-label="GitHub">
                                                <i class="fab fa-github"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($project['demo_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($project['demo_link']); ?>" target="_blank" class="text-gray-400 hover:text-accent" aria-label="Demo">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-dark/30 dark:bg-light/30 rounded-xl p-10 text-center">
                        <h3 class="text-xl font-semibold">No Projects Found</h3>
                        <p class="text-gray-400 dark:text-gray-600">No projects have been added to the database yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="skills" class="py-12">
            <h2 class="text-3xl font-bold text-center mb-12 relative after:content-[''] after:absolute after:bottom-[-10px] after:left-1/2 after:-translate-x-1/2 after:w-20 after:h-1 after:bg-primary after:rounded">
                Technical Skills
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if (!empty($skills)): ?>
                    <?php foreach ($skills as $skill): ?>
                        <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl p-6 hover:-translate-y-2 transition transform">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-star text-primary text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($skill['skill']); ?></h3>
                                    <p class="text-sm text-gray-400 dark:text-gray-600">Expertise level</p>
                                </div>
                            </div>
                            <div class="w-full bg-gray-700/50 dark:bg-gray-300/50 rounded-full h-2 mb-4">
                                <div class="bg-primary h-2 rounded-full" style="width: <?php echo $skill['proficiency']; ?>%"></div>
                            </div>
                            <div class="text-xl font-bold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent"><?php echo $skill['proficiency']; ?>%</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-dark/30 dark:bg-light/30 rounded-xl p-10 text-center">
                        <h3 class="text-xl font-semibold">No Skills Found</h3>
                        <p class="text-gray-400 dark:text-gray-600">No skills have been added to the database yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="contact" class="py-12">
            <h2 class="text-3xl font-bold text-center mb-12 relative after:content-[''] after:absolute after:bottom-[-10px] after:left-1/2 after:-translate-x-1/2 after:w-20 after:h-1 after:bg-primary after:rounded">
                Get In Touch
            </h2>
            <div class="bg-dark/30 dark:bg-light/30 backdrop-blur-md border border-gray-700/20 dark:border-gray-300/20 rounded-xl p-8 max-w-2xl mx-auto">
                <?php if (isset($success_message)): ?>
                    <div class="bg-success/20 border border-success text-success-300 rounded-lg p-4 mb-6 text-center">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="bg-danger/20 border border-danger text-danger-300 rounded-lg p-4 mb-6 text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">Your Name</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-3 bg-gray-700/50 dark:bg-gray-200/50 border border-gray-600/50 dark:border-gray-400/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-white dark:text-gray-800" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email Address</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-3 bg-gray-700/50 dark:bg-gray-200/50 border border-gray-600/50 dark:border-gray-400/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-white dark:text-gray-800" required>
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" class="w-full px-4 py-3 bg-gray-700/50 dark:bg-gray-200/50 border border-gray-600/50 dark:border-gray-400/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-white dark:text-gray-800" required>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium mb-2">Your Message</label>
                        <textarea id="message" name="message" class="w-full px-4 py-3 bg-gray-700/50 dark:bg-gray-200/50 border border-gray-600/50 dark:border-gray-400/50 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-white dark:text-gray-800 min-h-[150px] resize-y" required></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="w-full bg-primary text-white px-8 py-3 rounded-full font-semibold hover:bg-secondary hover:-translate-y-1 transition transform shadow-lg">
                        Send Message
                    </button>
                </form>
            </div>
        </section>

        <footer class="py-8 border-t border-gray-700/50 dark:border-gray-300/50 text-center">
            <div class="text-2xl font-extrabold bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent mb-4">
                NANG ROTHA
            </div>
            <p class="text-gray-400 dark:text-gray-600 mb-6">Creative Portfolio & Dashboard System</p>
            <div class="flex justify-center space-x-4 mb-6">
                <a href="#" class="w-10 h-10 rounded-full bg-gray-700/50 dark:bg-gray-200/50 flex items-center justify-center text-primary hover:bg-primary hover:text-white hover:-translate-y-1 transition" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-gray-700/50 dark:bg-gray-200/50 flex items-center justify-center text-primary hover:bg-primary hover:text-white hover:-translate-y-1 transition" aria-label="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-gray-700/50 dark:bg-gray-200/50 flex items-center justify-center text-primary hover:bg-primary hover:text-white hover:-translate-y-1 transition" aria-label="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-gray-700/50 dark:bg-gray-200/50 flex items-center justify-center text-primary hover:bg-primary hover:text-white hover:-translate-y-1 transition" aria-label="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="https://github.com/NangRotha/" class="w-10 h-10 rounded-full bg-gray-700/50 dark:bg-gray-200/50 flex items-center justify-center text-primary hover:bg-primary hover:text-white hover:-translate-y-1 transition" aria-label="GitHub">
                    <i class="fab fa-github"></i>
                </a>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-600">© 2025 NANG ROTHA Portfolio. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        // Load saved theme
        if (localStorage.getItem('theme') === 'dark') {
            htmlElement.classList.add('dark');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }

        themeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });

        // Mobile Menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    mobileMenu.classList.add('hidden'); // Close mobile menu
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });

        // Active nav highlighting
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('header nav a'); // Select desktop nav links

            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes('#' + current) && current !== '') {
                    link.classList.add('active');
                } else if (current === '' && link.getAttribute('href') === '#dashboard') {
                    link.classList.add('active'); // Highlight dashboard on top
                } else {
                    // Remove active class if the condition is not met
                    link.classList.remove('active');
                }
                // Custom styling for the "active" state (underline)
                if (link.classList.contains('active')) {
                    link.classList.add('after:w-full');
                } else {
                    link.classList.remove('after:w-full');
                }
            });

            // Also update mobile nav links
            const mobileNavLinks = document.querySelectorAll('#mobile-menu a');
            mobileNavLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes('#' + current) && current !== '') {
                    link.classList.add('active');
                } else if (current === '' && link.getAttribute('href') === '#dashboard') {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>