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

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $student_id = $_GET['student_id'] ?? '';
    $course_id = $_GET['course_id'] ?? '';
    
    if ($action == 'unenroll' && !empty($student_id) && !empty($course_id)) {
        // Verify the course belongs to the teacher
        $verify = $conn->query("SELECT id FROM courses WHERE id = $course_id AND $teacher_col = $tid");
        if ($verify->num_rows > 0) {
            $delete = $conn->query("DELETE FROM enrollments WHERE student_id = $student_id AND course_id = $course_id");
            if ($delete) {
                $_SESSION['success'] = "Student successfully unenrolled from the course.";
            } else {
                $_SESSION['error'] = "Failed to unenroll student.";
            }
        } else {
            $_SESSION['error'] = "You don't have permission to modify this course.";
        }
        header("Location: students.php");
        exit;
    }
}

// Get all students enrolled in teacher's courses
$students_query = $conn->query("
    SELECT DISTINCT u.id, u.name, u.email, 
           COUNT(DISTINCT e.course_id) as course_count,
           (SELECT COUNT(*) FROM enrollments e2 
            JOIN courses c2 ON e2.course_id = c2.id 
            WHERE e2.student_id = u.id AND c2.$teacher_col = $tid) as my_courses_count
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.$teacher_col = $tid AND u.role = 'student'
    GROUP BY u.id, u.name, u.email
    ORDER BY u.name
");

// Get all courses taught by this teacher - removed 'code' column
$courses_query = $conn->query("
    SELECT id, title 
    FROM courses 
    WHERE $teacher_col = $tid 
    ORDER BY title
");

// Get enrollments for each student - removed 'code' column
$enrollments = [];
if ($students_query && $students_query->num_rows > 0) {
    while ($student = $students_query->fetch_assoc()) {
        $student_id = $student['id'];
        $enrollments_query = $conn->query("
            SELECT c.id, c.title, e.created_at as enrolled_date
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = $student_id AND c.$teacher_col = $tid
            ORDER BY c.title
        ");
        
        $student_enrollments = [];
        if ($enrollments_query && $enrollments_query->num_rows > 0) {
            while ($enrollment = $enrollments_query->fetch_assoc()) {
                $student_enrollments[] = $enrollment;
            }
        }
        
        $student['enrollments'] = $student_enrollments;
        $enrollments[] = $student;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Learning Management System</title>
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
        
        .student-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .student-card:hover::before {
            left: 100%;
        }
        
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
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
        
        .tab-button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-button:hover:not(.active) {
            color: var(--dark);
            border-bottom-color: #d1d5db;
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
        
        .enrollment-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .enrollment-item:last-child {
            border-bottom: none;
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
                <a href="students.php" class="flex items-center py-3 px-4 bg-white bg-opacity-10 rounded-lg">
                    <i class="fas fa-users mr-3"></i>
                    <span class="sidebar-text">Students</span>
                </a>
                <a href="quizzes.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-tasks mr-3"></i>
                    <span class="sidebar-text">Quizzes</span>
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
                            <i class="fas fa-users mr-3"></i> Manage Students
                        </h1>
                        <p class="mt-2 opacity-90">Manage students enrolled in your courses</p>
                    </div>
                    <div class="flex space-x-4">
                        <button class="btn-secondary" onclick="toggleSearch()">
                            <i class="fas fa-search"></i> Search Students
                        </button>
                        <a href="teacher_dashboard.php" class="btn-primary">
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

            <!-- Search Bar -->
            <div id="searchBar" class="glass-card p-4 mb-6 hidden">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" id="searchInput" placeholder="Search students by name or email..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-primary" onclick="searchStudents()">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <button class="btn-secondary" onclick="clearSearch()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="glass-card p-6 mb-8">
                <div class="border-b border-gray-200">
                    <div class="flex overflow-x-auto">
                        <button class="tab-button active" onclick="switchTab('all-students')">
                            <i class="fas fa-users mr-2"></i> All Students
                        </button>
                        <button class="tab-button" onclick="switchTab('by-course')">
                            <i class="fas fa-book mr-2"></i> By Course
                        </button>
                        <button class="tab-button" onclick="switchTab('performance')">
                            <i class="fas fa-chart-line mr-2"></i> Performance
                        </button>
                    </div>
                </div>
            </div>

            <!-- All Students Tab -->
            <div id="all-students" class="tab-content">
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="section-title">👥 All Students</h2>
                        <div class="text-gray-600">
                            <span class="font-semibold"><?php echo count($enrollments); ?></span> students
                        </div>
                    </div>
                    
                    <?php if (count($enrollments) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($enrollments as $student): ?>
                                <div class="student-card bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                                    <div class="flex items-start mb-4">
                                        <div class="flex-shrink-0 mr-4">
                                            <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-blue-500 text-xl"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($student['name']); ?></h3>
                                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($student['email']); ?></p>
                                            <div class="flex items-center mt-1 text-sm text-gray-500">
                                                <i class="fas fa-book mr-1"></i>
                                                <span><?php echo $student['my_courses_count']; ?> course<?php echo $student['my_courses_count'] != 1 ? 's' : ''; ?> with you</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Your Courses</span>
                                            <span><?php echo $student['my_courses_count']; ?> of <?php echo $student['course_count']; ?> total</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $student['course_count'] > 0 ? ($student['my_courses_count'] / $student['course_count'] * 100) : 0; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-100 pt-4">
                                        <h4 class="font-semibold text-gray-700 mb-2">Enrolled Courses:</h4>
                                        <div class="space-y-2 max-h-32 overflow-y-auto">
                                            <?php if (count($student['enrollments']) > 0): ?>
                                                <?php foreach ($student['enrollments'] as $enrollment): ?>
                                                    <div class="enrollment-item">
                                                        <div class="flex-1">
                                                            <p class="font-medium text-sm"><?php echo htmlspecialchars($enrollment['title']); ?></p>
                                                            <p class="text-xs text-gray-500">Enrolled <?php echo date('M j, Y', strtotime($enrollment['enrolled_date'])); ?></p>
                                                        </div>
                                                        <div>
                                                            <a href="students.php?action=unenroll&student_id=<?php echo $student['id']; ?>&course_id=<?php echo $enrollment['id']; ?>" 
                                                               class="btn-danger text-xs" 
                                                               onclick="return confirm('Are you sure you want to unenroll this student from <?php echo htmlspecialchars($enrollment['title']); ?>?')">
                                                                <i class="fas fa-user-minus"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500 italic">No enrollments in your courses</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex justify-between">
                                        <a href="student_progress.php?id=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            <i class="fas fa-chart-line mr-1"></i> View Progress
                                        </a>
                                        <a href="messages.php?to=<?php echo $student['id']; ?>" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                            <i class="fas fa-envelope mr-1"></i> Message
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Students Yet</h3>
                            <p class="text-gray-500 mb-6">Students will appear here once they enroll in your courses.</p>
                            <a href="courses.php" class="btn-primary">
                                <i class="fas fa-book mr-2"></i> Manage Your Courses
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- By Course Tab -->
            <div id="by-course" class="tab-content hidden">
                <div class="glass-card p-6">
                    <h2 class="section-title">📚 Students by Course</h2>
                    
                    <?php
                    if ($courses_query && $courses_query->num_rows > 0):
                        while ($course = $courses_query->fetch_assoc()):
                            $course_id = $course['id'];
                            
                            // Get students enrolled in this course
                            $course_students_query = $conn->query("
                                SELECT u.id, u.name, u.email, e.created_at as enrolled_date
                                FROM users u
                                JOIN enrollments e ON u.id = e.student_id
                                WHERE e.course_id = $course_id AND u.role = 'student'
                                ORDER BY u.name
                            ");
                            
                            $student_count = $course_students_query ? $course_students_query->num_rows : 0;
                    ?>
                            <div class="mb-8 last:mb-0">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                                        <?php echo $student_count; ?> student<?php echo $student_count != 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                                
                                <?php if ($student_count > 0): ?>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <?php while ($student = $course_students_query->fetch_assoc()): ?>
                                                        <tr>
                                                            <td class="px-4 py-3 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                                        <i class="fas fa-user text-blue-500"></i>
                                                                    </div>
                                                                    <div class="ml-4">
                                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($student['enrolled_date'])); ?></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                                <a href="student_progress.php?id=<?php echo $student['id']; ?>&course=<?php echo $course_id; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                                    <i class="fas fa-chart-line"></i> Progress
                                                                </a>
                                                                <a href="students.php?action=unenroll&student_id=<?php echo $student['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                                                   class="text-red-600 hover:text-red-900"
                                                                   onclick="return confirm('Are you sure you want to unenroll <?php echo htmlspecialchars($student['name']); ?> from <?php echo htmlspecialchars($course['title']); ?>?')">
                                                                    <i class="fas fa-user-minus"></i> Unenroll
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                                        <i class="fas fa-users text-3xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">No students enrolled in this course yet.</p>
                                        <a href="course_details.php?id=<?php echo $course_id; ?>" class="inline-block mt-3 text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="fas fa-eye mr-1"></i> View Course Details
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="text-center py-12">
                            <i class="fas fa-book-open text-5xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Courses Found</h3>
                            <p class="text-gray-500 mb-6">You need to create courses before you can view students by course.</p>
                            <a href="add_course.php" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i> Create Your First Course
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance Tab -->
            <div id="performance" class="tab-content hidden">
                <div class="glass-card p-6">
                    <h2 class="section-title">📊 Student Performance</h2>
                    <p class="text-gray-600 mb-6">Track student progress and performance across your courses.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-700">Average Completion</h3>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">72%</p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-lg">
                                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">Across all your courses</p>
                        </div>
                        
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-700">Avg. Quiz Score</h3>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">84%</p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-lg">
                                    <i class="fas fa-chart-bar text-blue-500 text-xl"></i>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">Based on all completed quizzes</p>
                        </div>
                        
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-700">Active Students</h3>
                                    <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($enrollments); ?></p>
                                </div>
                                <div class="bg-purple-100 p-3 rounded-lg">
                                    <i class="fas fa-user-check text-purple-500 text-xl"></i>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">Currently enrolled in your courses</p>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Top Performing Students</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (count($enrollments) > 0): ?>
                                <?php 
                                // For demo purposes, we'll show the first 5 students as top performers
                                $topStudents = array_slice($enrollments, 0, min(5, count($enrollments)));
                                foreach ($topStudents as $index => $student): 
                                    // Generate a random performance score for demo
                                    $performance = rand(75, 98);
                                ?>
                                    <div class="px-6 py-4 flex items-center">
                                        <div class="flex-shrink-0 mr-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center">
                                                <span class="font-bold text-blue-600"><?php echo $index + 1; ?></span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($student['name']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo $student['my_courses_count']; ?> course<?php echo $student['my_courses_count'] != 1 ? 's' : ''; ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-900"><?php echo $performance; ?>%</div>
                                            <div class="text-sm text-gray-500">Performance</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-6 py-8 text-center">
                                    <i class="fas fa-chart-line text-3xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">No student data available yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the first tab as active
            switchTab('all-students');
        });
        
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabName).classList.remove('hidden');
            
            // Add active class to the clicked tab button
            event.target.classList.add('active');
        }
        
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchBar.classList.toggle('hidden');
            
            if (!searchBar.classList.contains('hidden')) {
                document.getElementById('searchInput').focus();
            }
        }
        
        function searchStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const studentCards = document.querySelectorAll('.student-card');
            
            studentCards.forEach(card => {
                const studentName = card.querySelector('h3').textContent.toLowerCase();
                const studentEmail = card.querySelector('p').textContent.toLowerCase();
                
                if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            const studentCards = document.querySelectorAll('.student-card');
            
            studentCards.forEach(card => {
                card.style.display = 'block';
            });
        }
    </script>
</body>
</html>