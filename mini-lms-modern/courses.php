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
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $courseCount = 0;
    $studentCount = 0;
    $quizCount = 0;
    $recentCourses = false;
    $recentEnrollments = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Learning Management System</title>
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
    </style>
</head>

</html>div class="glass-card p-6 mb-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="section-title">📚 Your Courses</h2>
                                   </div>
                        
                        <?php
                        if ($recentCourses && $recentCourses->num_rows > 0) {
                            echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                            while ($course = $recentCourses->fetch_assoc()) {
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
                                
                                // Course meta information
                                echo '<div class="course-meta">';
                                echo '<span class="text-xs bg-gray-100 px-2 py-1 rounded">' . htmlspecialchars($course['level'] ?? 'Beginner') . '</span>';
                                echo '<span class="text-xs">' . date('M j, Y', strtotime($course['created_at'])) . '</span>';
                                echo '</div>';
                                
                                // Action buttons
                                echo '<div class="course-actions">';
                                echo '<a href="edit_course.php?id=' . $course['id'] . '" class="btn-edit">';
                                echo '<i class="fas fa-edit mr-1"></i> Edit';
                                echo '</a>';
                                echo '<a href="delete_course.php?id=' . $course['id'] . '" class="btn-delete" onclick="return confirm(\'Are you sure you want to delete this course? This action cannot be undone.\')">';
                                echo '<i class="fas fa-trash mr-1"></i> Delete';
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
                    </body>
</html>