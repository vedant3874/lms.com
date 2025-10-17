<?php
include 'header.php';
include 'config.php';

// Only allow teacher
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

// If neither exists, we need to handle this
if (!$has_teacher_id && !$has_instructor_id) {
    $teacher_col = 'user_id';
}

// Stats with safer queries
try {
    // Course count
    $courseCount = 0;
    $courseQuery = $conn->query("SELECT COUNT(*) as c FROM courses WHERE $teacher_col=$tid");
    if ($courseQuery) {
        $courseCount = $courseQuery->fetch_assoc()['c'] ?? 0;
    }
    
    // Student count - count unique students enrolled in teacher's courses
    $studentCount = 0;
    $studentQuery = $conn->query("
        SELECT COUNT(DISTINCT e.student_id) as s 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.$teacher_col = $tid
    ");
    if ($studentQuery) {
        $studentCount = $studentQuery->fetch_assoc()['s'] ?? 0;
    }
    
    // Quiz count - count quizzes created by teacher
    $quizCount = 0;
    $quizQuery = $conn->query("
        SELECT COUNT(*) as q 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        WHERE c.$teacher_col = $tid
    ");
    if ($quizQuery) {
        $quizCount = $quizQuery->fetch_assoc()['q'] ?? 0;
    }

    // Get average student progress across all courses
    $avgProgress = 0;
    $progressQuery = $conn->query("
        SELECT AVG(sp.progress_percentage) as avg_progress 
        FROM student_progress sp 
        JOIN courses c ON sp.course_id = c.id 
        WHERE c.$teacher_col = $tid
    ");
    if ($progressQuery) {
        $avgProgress = round($progressQuery->fetch_assoc()['avg_progress'] ?? 0, 1);
    }

    // Get recent courses
    $recentCourses = $conn->query("SELECT * FROM courses WHERE $teacher_col=$tid ORDER BY created_at DESC LIMIT 6");
    
    // Get recent enrollments for activity
    $recentEnrollments = $conn->query("
        SELECT u.name as student_name, c.title as course_name, e.created_at 
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.$teacher_col = $tid 
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");

    // Get top performing students
    $topStudents = $conn->query("
        SELECT 
            u.name as student_name,
            c.title as course_name,
            AVG(sp.progress_percentage) as avg_progress,
            AVG(qa.score) as avg_quiz_score
        FROM student_progress sp
        JOIN users u ON sp.student_id = u.id
        JOIN courses c ON sp.course_id = c.id
        LEFT JOIN quiz_attempts qa ON qa.student_id = u.id AND qa.quiz_id IN (
            SELECT id FROM quizzes WHERE course_id = c.id
        )
        WHERE c.$teacher_col = $tid
        GROUP BY u.id, c.id
        ORDER BY avg_progress DESC, avg_quiz_score DESC
        LIMIT 5
    ");

    // Get course progress statistics
    $courseProgress = $conn->query("
        SELECT 
            c.id,
            c.title,
            COUNT(DISTINCT e.student_id) as total_students,
            AVG(sp.progress_percentage) as avg_progress,
            COUNT(DISTINCT qa.id) as total_quiz_attempts
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN student_progress sp ON c.id = sp.course_id AND sp.student_id = e.student_id
        LEFT JOIN quiz_attempts qa ON qa.student_id = e.student_id AND qa.quiz_id IN (
            SELECT id FROM quizzes WHERE course_id = c.id
        )
        WHERE c.$teacher_col = $tid
        GROUP BY c.id, c.title
        ORDER BY avg_progress DESC
    ");
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $courseCount = 0;
    $studentCount = 0;
    $quizCount = 0;
    $avgProgress = 0;
    $recentCourses = false;
    $recentEnrollments = false;
    $topStudents = false;
    $courseProgress = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stat-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .course-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .course-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .course-card:hover::before {
            left: 100%;
        }
        
        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.5s ease;
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
        
        .btn-edit {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
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
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-delete {
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
        
        .btn-delete:hover {
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
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .icon-quiz {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }
        
        .icon-course {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .icon-certificate {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        }
        
        .icon-message {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
        }
        
        .icon-student {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        }
        
        .icon-progress {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
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
        
        .course-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .progress-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(var(--primary) 0% var(--progress), #e5e7eb var(--progress) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .progress-indicator::before {
            content: '';
            position: absolute;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
        }
        
        .progress-value {
            position: relative;
            font-weight: bold;
            font-size: 0.875rem;
            color: var(--dark);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .student-progress-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .student-progress-item:hover {
            border-left-color: var(--primary);
            background-color: #f8fafc;
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
                <a href="teacher_dashboard.php" class="flex items-center py-3 px-4 bg-white bg-opacity-10 rounded-lg">
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
                <a href="progress_tracking.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span class="sidebar-text">Progress Tracking</span>
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
                            <i class="fas fa-chalkboard-teacher mr-3"></i> Teacher Dashboard
                        </h1>
                        <p class="mt-2 opacity-90">👋 Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong></p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="add_course.php" class="btn-secondary">
                            <i class="fas fa-plus"></i> New Course
                        </a>
                        <a href="progress_tracking.php" class="btn-primary pulse">
                            <i class="fas fa-chart-line"></i> View Progress
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

            <!-- Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card glass-card text-white gradient-bg p-6 rounded-xl">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold">Total Courses</h3>
                            <p class="text-3xl font-bold mt-2"><?php echo $courseCount; ?></p>
                        </div>
                        <div class="text-3xl opacity-80">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm opacity-90">
                        <i class="fas fa-arrow-up mr-1"></i> Your created courses
                    </div>
                </div>
                
                <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-green-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Total Students</h3>
                            <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $studentCount; ?></p>
                        </div>
                        <div class="text-3xl text-green-500">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <i class="fas fa-user-graduate mr-1 text-blue-500"></i> Enrolled in your courses
                    </div>
                </div>
                
                <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-blue-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Average Progress</h3>
                            <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $avgProgress; ?>%</p>
                        </div>
                        <div class="text-3xl text-blue-500">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <i class="fas fa-trending-up mr-1 text-green-500"></i> Student progress rate
                    </div>
                </div>
                
                <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-purple-500">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Total Quizzes</h3>
                            <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $quizCount; ?></p>
                        </div>
                        <div class="text-3xl text-purple-500">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-600">
                        <i class="fas fa-check-circle mr-1 text-green-500"></i> Active quizzes
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Courses and Progress -->
                <div class="lg:col-span-2">
                    <!-- Recent Courses -->
                    <div class="glass-card p-6 mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="section-title">📚 Your Courses</h2>
                            <a href="courses.php" class="text-blue-600 hover:text-blue-800 font-medium">view all</a>
                        </div>
                        
                        <?php
                        if ($recentCourses && $recentCourses->num_rows > 0) {
                            echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                            while ($course = $recentCourses->fetch_assoc()) {
                                // Get progress stats for this course
                                $courseStats = $conn->query("
                                    SELECT 
                                        COUNT(DISTINCT e.student_id) as total_students,
                                        AVG(sp.progress_percentage) as avg_progress
                                    FROM courses c
                                    LEFT JOIN enrollments e ON c.id = e.course_id
                                    LEFT JOIN student_progress sp ON c.id = sp.course_id AND sp.student_id = e.student_id
                                    WHERE c.id = {$course['id']}
                                ")->fetch_assoc();
                                
                                $avgProgress = round($courseStats['avg_progress'] ?? 0, 1);
                                $totalStudents = $courseStats['total_students'] ?? 0;
                                
                                echo '<div class="course-card bg-white rounded-xl shadow-md p-4 border border-gray-100">';
                                echo '<div class="flex">';
                                echo '<div class="flex-shrink-0 mr-4">';
                                if (!empty($course['thumbnail'])) {
                                    echo '<img src="' . htmlspecialchars($course['thumbnail']) . '" alt="Thumbnail" class="w-16 h-16 object-cover rounded-lg">';
                                } else {
                                    echo '<div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center">';
                                    echo '<i class="fas fa-book text-blue-500 text-xl"></i>';
                                    echo '</div>';
                                }
                                echo '</div>';
                                echo '<div class="flex-1">';
                                echo '<h4 class="font-semibold text-gray-800 text-lg mb-1">' . htmlspecialchars($course['title']) . '</h4>';
                                echo '<p class="text-gray-600 text-sm mb-2">' . htmlspecialchars(substr($course['description'] ?? 'No description', 0, 60)) . '...</p>';
                                
                                // Progress information
                                echo '<div class="mb-3">';
                                echo '<div class="flex justify-between text-sm text-gray-600 mb-1">';
                                echo '<span>Average Progress</span>';
                                echo '<span>' . $avgProgress . '%</span>';
                                echo '</div>';
                                echo '<div class="progress-bar">';
                                echo '<div class="progress-fill" style="width: ' . $avgProgress . '%"></div>';
                                echo '</div>';
                                echo '<div class="text-xs text-gray-500 mt-1">' . $totalStudents . ' students enrolled</div>';
                                echo '</div>';
                                
                                // Course meta information
                                echo '<div class="course-meta">';
                                echo '<span class="text-xs bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($course['level'] ?? 'Beginner') . '</span>';
                                echo '<span class="text-xs">' . date('M j, Y', strtotime($course['created_at'])) . '</span>';
                                echo '</div>';
                                
                                // Action buttons
                                echo '<div class="course-actions">';
                                echo '<a href="view_progress.php?course_id=' . $course['id'] . '" class="btn-edit">';
                                echo '<i class="fas fa-chart-line mr-1"></i> Progress';
                                echo '</a>';
                                echo '<a href="edit_course.php?id=' . $course['id'] . '" class="btn-edit">';
                                echo '<i class="fas fa-edit mr-1"></i> Edit';
                                echo '</a>';
                                echo '</div>';
                                
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="text-center py-8">';
                            echo '<i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>';
                            echo '<p class="text-gray-600">You haven\'t created any courses yet.</p>';
                            echo '<a href="add_course.php" class="btn-primary mt-4">Create Your First Course</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>

                    <!-- Course Progress Overview -->
                    <div class="glass-card p-6 mb-8">
                        <h2 class="section-title">📊 Course Progress Overview</h2>
                        <?php if ($courseProgress && $courseProgress->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($course = $courseProgress->fetch_assoc()): ?>
                                    <div class="student-progress-item bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h4>
                                                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
                                                    <span><i class="fas fa-users mr-1"></i> <?php echo $course['total_students']; ?> students</span>
                                                    <span><i class="fas fa-tasks mr-1"></i> <?php echo $course['total_quiz_attempts']; ?> quiz attempts</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <div class="text-right">
                                                    <div class="text-lg font-bold text-gray-800"><?php echo round($course['avg_progress'] ?? 0, 1); ?>%</div>
                                                    <div class="text-xs text-gray-500">Avg Progress</div>
                                                </div>
                                                <div class="progress-indicator" style="--progress: <?php echo ($course['avg_progress'] ?? 0); ?>%">
                                                    <span class="progress-value"><?php echo round($course['avg_progress'] ?? 0, 0); ?>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span>Overall Progress</span>
                                                <span><?php echo round($course['avg_progress'] ?? 0, 1); ?>%</span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $course['avg_progress'] ?? 0; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-600">No progress data available yet.</p>
                                <p class="text-gray-500 text-sm mt-2">Student progress will appear here as they enroll and complete courses.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Activity and Top Performers -->
                <div class="lg:col-span-1">
                    <!-- Recent Activity -->
                    <div class="glass-card p-6 mb-8">
                        <h2 class="section-title">📈 Recent Activity</h2>
                        <div class="space-y-4">
                            <?php
                            if ($recentEnrollments && $recentEnrollments->num_rows > 0) {
                                while ($enrollment = $recentEnrollments->fetch_assoc()) {
                                    echo '<div class="activity-item">';
                                    echo '<div class="activity-icon icon-student">';
                                    echo '<i class="fas fa-user-plus text-white"></i>';
                                    echo '</div>';
                                    echo '<div>';
                                    echo '<p class="font-medium text-gray-800">' . htmlspecialchars($enrollment['student_name']) . ' enrolled</p>';
                                    echo '<p class="text-sm text-gray-600">' . htmlspecialchars($enrollment['course_name']) . '</p>';
                                    echo '<p class="text-xs text-gray-500">' . date('M j, g:i A', strtotime($enrollment['created_at'])) . '</p>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center py-4">';
                                echo '<i class="fas fa-history text-2xl text-gray-300 mb-2"></i>';
                                echo '<p class="text-gray-500 text-sm">No recent activity</p>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Top Performing Students -->
                    <div class="glass-card p-6 mb-8">
                        <h2 class="section-title">🏆 Top Performers</h2>
                        <div class="space-y-4">
                            <?php if ($topStudents && $topStudents->num_rows > 0): ?>
                                <?php 
                                $rank = 1;
                                while ($student = $topStudents->fetch_assoc()): 
                                ?>
                                    <div class="student-progress-item p-3 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-center text-white text-sm font-bold mr-3">
                                                    <?php echo $rank; ?>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($student['student_name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($student['course_name']); ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-gray-800"><?php echo round($student['avg_progress'], 1); ?>%</div>
                                                <div class="text-xs text-gray-500">Progress</div>
                                            </div>
                                        </div>
                                        <?php if ($student['avg_quiz_score']): ?>
                                        <div class="mt-2 flex justify-between items-center text-xs">
                                            <span class="text-gray-500">Quiz Score</span>
                                            <span class="font-medium text-green-600"><?php echo round($student['avg_quiz_score'], 1); ?>%</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php 
                                $rank++;
                                endwhile; 
                                ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-trophy text-2xl text-gray-300 mb-2"></i>
                                    <p class="text-gray-500 text-sm">No performance data yet</p>
                                    <p class="text-gray-400 text-xs mt-1">Top performers will appear here</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="glass-card p-6">
                        <h2 class="section-title">⚡ Quick Actions</h2>
                        <div class="space-y-3">
                            <a href="add_course.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-plus text-white"></i>
                                </div>
                                <span class="font-medium text-gray-800">Create Course</span>
                            </a>
                            <a href="add_quiz.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                                <span class="font-medium text-gray-800">Add Quiz</span>
                            </a>
                            <a href="progress_tracking.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-line text-white"></i>
                                </div>
                                <span class="font-medium text-gray-800">Track Progress</span>
                            </a>
                            <a href="announcements.php" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-bullhorn text-white"></i>
                                </div>
                                <span class="font-medium text-gray-800">Announcements</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add floating animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = ${index * 0.1}s;
            });
            
            // Add hover effect to course cards
            const courseCards = document.querySelectorAll('.course-card');
            courseCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add hover effect to student progress items
            const progressItems = document.querySelectorAll('.student-progress-item');
            progressItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Enhanced delete confirmation
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('⚠ Are you sure you want to delete this course? This will permanently remove all course data, quizzes, and student progress. This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>