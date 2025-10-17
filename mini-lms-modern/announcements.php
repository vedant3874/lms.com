<?php
include 'header.php';
include 'config.php';

// Only allow teacher access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$tid = $_SESSION['user']['id'];

// Check if courses table has teacher_id or instructor_id
$courses_columns = $conn->query("DESCRIBE courses");
$has_teacher_id = false;
$has_instructor_id = false;
$teacher_col = '';

while ($col = $courses_columns->fetch_assoc()) {
    if ($col['Field'] == 'teacher_id') {
        $has_teacher_id = true;
        $teacher_col = 'teacher_id';
    }
    if ($col['Field'] == 'instructor_id') {
        $has_instructor_id = true;
        $teacher_col = 'instructor_id';
    }
}

// If neither exists, use user_id as fallback
if (!$has_teacher_id && !$has_instructor_id) {
    $teacher_col = 'user_id';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $course_id = $_POST['course_id'];
    $priority = $_POST['priority'];
    
    // Validate input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Please fill in both title and content fields.";
    } else {
        // Insert announcement into database
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, course_id, teacher_id, priority, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssiss", $title, $content, $course_id, $tid, $priority);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Announcement posted successfully!";
            // Reset form fields
            $title = $content = '';
            $course_id = $priority = '';
        } else {
            $_SESSION['error'] = "Failed to post announcement. Please try again.";
        }
        $stmt->close();
    }
}

// Get teacher's courses for dropdown
$courses = $conn->query("SELECT id, title FROM courses WHERE $teacher_col = $tid ORDER BY title");

// Get recent announcements
$announcements_query = $conn->query("
    SELECT a.*, c.title as course_name 
    FROM announcements a 
    LEFT JOIN courses c ON a.course_id = c.id 
    WHERE a.teacher_id = $tid 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --accent: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #fdf2f8 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            min-height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .sidebar-text {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                min-height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .priority-high {
            border-left: 4px solid #ef4444;
        }
        
        .priority-medium {
            border-left: 4px solid #f59e0b;
        }
        
        .priority-low {
            border-left: 4px solid #10b981;
        }
        
        .announcement-card {
            transition: all 0.3s ease;
        }
        
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-6">
            <div class="flex items-center mb-10">
                <i class="fas fa-graduation-cap text-2xl mr-3"></i>
                <h1 class="text-xl font-bold sidebar-text">EduPortal</h1>
            </div>
            
            <nav class="space-y-2">
                <a href="teacher_dashboard.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="courses.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-book mr-3"></i>
                    <span class="sidebar-text">Courses</span>
                </a>
                <a href="students.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Students</span>
                </a>
                <a href="quizzes.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-tasks mr-3"></i>
                    <span class="sidebar-text">Quizzes</span>
                </a>
                <a href="announcements.php" class="flex items-center py-3 px-4 bg-white bg-opacity-10 rounded-lg">
                    <i class="fas fa-bullhorn mr-3"></i>
                    <span class="sidebar-text">Announcements</span>
                </a>
                <a href="messages.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition relative">
                    <i class="fas fa-envelope mr-3"></i>
                    <span class="sidebar-text">Messages</span>
                    <span class="notification-badge">3</span>
                </a>
                <a href="settings.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text">Settings</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="gradient-bg text-white py-6 rounded-xl mb-8">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <h1 class="text-3xl font-bold flex items-center">
                            <i class="fas fa-bullhorn mr-3"></i> Post Announcement
                        </h1>
                        <p class="mt-2 opacity-90">Share important updates with your students</p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="teacher_dashboard.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard 
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4">
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Announcement Form -->
                <div class="lg:col-span-2">
                    <div class="glass-card p-6 mb-8">
                        <h2 class="section-title">📢 Create New Announcement</h2>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="title" class="form-label">Announcement Title *</label>
                                <input type="text" id="title" name="title" class="form-input" placeholder="Enter announcement title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"required style="background-color:black">
                            </div>
                            
                            <div class="form-group">
                                <label for="content" class="form-label" >Announcement Content *</label>
                                <textarea id="content" name="content" rows="6" class="form-input" placeholder="Write your announcement here..." required style="background-color:black"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="course_id" class="form-label">Select Course</label>
                                    <select id="course_id" name="course_id" class="form-input"style="background-color:black" >
                                        <option value="">All Courses (General Announcement)</option>
                                        <?php
                                        if ($courses && $courses->num_rows > 0) {
                                            while ($course = $courses->fetch_assoc()) {
                                                $selected = (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : '';
                                                echo "<option value='{$course['id']}' $selected>{$course['title']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="priority" class="form-label">Priority Level</label>
                                    <select id="priority" name="priority" class="form-input"style="background-color:black">
                                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>Low Priority</option>
                                        <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'medium') ? 'selected' : ''; ?>>Medium Priority</option>
                                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>High Priority</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-8">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This announcement will be visible to all students in the selected course(s)
                                </div>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i> Post Announcement
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Tips Section -->
                    <div class="glass-card p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Announcement Tips
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-start">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-bullseye text-blue-500"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Be Clear & Concise</h4>
                                    <p class="text-sm text-gray-600">Get straight to the point with important information</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-calendar text-green-500"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Include Deadlines</h4>
                                    <p class="text-sm text-gray-600">Always mention dates for time-sensitive information</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-exclamation text-purple-500"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Use Priority Wisely</h4>
                                    <p class="text-sm text-gray-600">Reserve high priority for urgent matters only</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-link text-orange-500"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800">Add Resources</h4>
                                    <p class="text-sm text-gray-600">Include links to relevant materials when needed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Announcements -->
                <div class="lg:col-span-1">
                    <div class="glass-card p-6">
                        <h2 class="section-title">📋 Recent Announcements</h2>
                        
                        <?php
                        if ($announcements_query && $announcements_query->num_rows > 0):
                            echo '<div class="space-y-4">';
                            while ($announcement = $announcements_query->fetch_assoc()):
                                $priority_class = 'priority-' . $announcement['priority'];
                        ?>
                                <div class="announcement-card bg-white rounded-lg p-4 border border-gray-200 <?php echo $priority_class; ?>">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                        <span class="text-xs px-2 py-1 rounded-full 
                                            <?php 
                                            if ($announcement['priority'] == 'high') echo 'bg-red-100 text-red-800';
                                            elseif ($announcement['priority'] == 'medium') echo 'bg-yellow-100 text-yellow-800';
                                            else echo 'bg-green-100 text-green-800';
                                            ?>
                                        ">
                                            <?php echo ucfirst($announcement['priority']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-xs mb-2 line-clamp-2"><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>...</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>
                                            <?php 
                                            if ($announcement['course_name']) {
                                                echo htmlspecialchars($announcement['course_name']);
                                            } else {
                                                echo 'All Courses';
                                            }
                                            ?>
                                        </span>
                                        <span><?php echo date('M j, g:i A', strtotime($announcement['created_at'])); ?></span>
                                    </div>
                                </div>
                        <?php
                            endwhile;
                            echo '</div>';
                        else:
                        ?>
                            <div class="text-center py-8">
                                <i class="fas fa-bullhorn text-3xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No announcements posted yet.</p>
                                <p class="text-gray-400 text-xs mt-1">Your recent announcements will appear here</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-6 text-center">
                            <a href="announcements_history.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-history mr-1"></i> View All Announcements
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="glass-card p-6 mt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Announcement Stats</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Posted</span>
                                <span class="font-semibold text-gray-800">
                                    <?php
                                    $total_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE teacher_id = $tid");
                                    echo $total_count ? $total_count->fetch_assoc()['count'] : 0;
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">This Month</span>
                                <span class="font-semibold text-gray-800">
                                    <?php
                                    $month_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE teacher_id = $tid AND MONTH(created_at) = MONTH(NOW())");
                                    echo $month_count ? $month_count->fetch_assoc()['count'] : 0;
                                    ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">High Priority</span>
                                <span class="font-semibold text-red-600">
                                    <?php
                                    $high_count = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE teacher_id = $tid AND priority = 'high'");
                                    echo $high_count ? $high_count->fetch_assoc()['count'] : 0;
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Character counter for announcement content
            const contentTextarea = document.getElementById('content');
            const charCount = document.createElement('div');
            charCount.className = 'text-sm text-gray-500 mt-1';
            charCount.textContent = '0 characters';
            contentTextarea.parentNode.appendChild(charCount);
            
            contentTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = ${length} characters;
                
                if (length > 500) {
                    charCount.className = 'text-sm text-red-500 mt-1';
                } else {
                    charCount.className = 'text-sm text-gray-500 mt-1';
                }
            });
            
            // Priority indicator
            const prioritySelect = document.getElementById('priority');
            const priorityIndicator = document.createElement('div');
            priorityIndicator.className = 'text-sm mt-1';
            prioritySelect.parentNode.appendChild(priorityIndicator);
            
            function updatePriorityIndicator() {
                const priority = prioritySelect.value;
                let text = '';
                let color = '';
                
                switch (priority) {
                    case 'low':
                        text = '🟢 Low Priority - General information';
                        color = 'text-green-600';
                        break;
                    case 'medium':
                        text = '🟡 Medium Priority - Important updates';
                        color = 'text-yellow-600';
                        break;
                    case 'high':
                        text = '🔴 High Priority - Urgent matters';
                        color = 'text-red-600';
                        break;
                }
                
                priorityIndicator.textContent = text;
                priorityIndicator.className = text-sm mt-1 ${color};
            }
            
            prioritySelect.addEventListener('change', updatePriorityIndicator);
            updatePriorityIndicator(); // Initial call
        });
    </script>
</body>
</html>