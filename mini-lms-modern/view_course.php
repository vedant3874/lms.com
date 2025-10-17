<?php 
include 'header.php'; 
include 'config.php';

if (!isset($_SESSION['user'])) { 
    header('Location: login.php'); 
    exit; 
}

$course_id = intval($_GET['id']);
$res = $conn->query("SELECT courses.*, users.name as teacher, categories.name as category_name
                     FROM courses 
                     JOIN users ON courses.teacher_id=users.id 
                     LEFT JOIN categories ON courses.category_id = categories.id
                     WHERE courses.id=$course_id");
$course = $res->fetch_assoc();

// Check if user is enrolled (for students)
$is_enrolled = false;
if ($_SESSION['user']['role'] == 'student') {
    $student_id = $_SESSION['user']['id'];
    $enrollment_check = $conn->query("SELECT id FROM enrollments WHERE student_id=$student_id AND course_id=$course_id");
    $is_enrolled = $enrollment_check->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --accent: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #fdf2f8 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
        }
        
        .course-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .level-beginner { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); 
            color: #065f46; 
            border: 1px solid #a7f3d0;
        }
        
        .level-intermediate { 
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); 
            color: #92400e; 
            border: 1px solid #fde68a;
        }
        
        .level-advanced { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); 
            color: #991b1b; 
            border: 1px solid #fecaca;
        }
        
        .category-badge { 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); 
            color: #3730a3; 
            border: 1px solid #c7d2fe;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            margin: 20px 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(107, 114, 128, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3);
        }
        
        .btn-accent {
            background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%);
            color: white;
        }
        
        .btn-accent:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.3);
        }
        
        .course-header {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .course-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
        }
        
        .course-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .teacher-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin-right: 16px;
        }
        
        .material-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 20px;
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        
        .material-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .info-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 16px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease;
        }
    </style>
</head>
<body class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        <!-- Course Header -->
        <div class="course-header fade-in">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between relative z-10">
                <div class="flex-1">
                    <div class="flex flex-wrap gap-2 mb-6">
                        <?php if ($course['level']): ?>
                            <span class="course-badge level-<?php echo strtolower($course['level']); ?>">
                                <i class="fas fa-chart-line mr-2"></i>
                                <?php echo htmlspecialchars($course['level']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($course['category_name']): ?>
                            <span class="course-badge category-badge">
                                <i class="fas fa-tag mr-2"></i>
                                <?php echo htmlspecialchars($course['category_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-4xl font-bold mb-4 leading-tight"><?php echo htmlspecialchars($course['title']); ?></h1>
                    
                    <div class="flex items-center mb-6">
                        <div class="teacher-avatar">
                            <?php echo substr($course['teacher'], 0, 1); ?>
                        </div>
                        <div>
                            <p class="text-xl font-semibold">By <?php echo htmlspecialchars($course['teacher']); ?></p>
                            <p class="text-gray-200">
                                <i class="far fa-calendar mr-2"></i>
                                Posted: <?php echo date('F j, Y', strtotime($course['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <?php if ($course['thumbnail']): ?>
                    <div class="md:ml-8 mt-6 md:mt-0">
                        <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             alt="Course Thumbnail" 
                             class="w-40 h-40 object-cover rounded-2xl shadow-2xl border-4 border-white">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Content -->
        <div class="glass-card p-8 fade-in">
            <h2 class="section-title">
                <i class="fas fa-align-left mr-3"></i>Course Description
            </h2>
            <p class="text-gray-700 leading-relaxed text-lg mb-8 bg-gray-50 p-6 rounded-xl border-l-4 border-primary">
                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
            </p>

            <!-- Course Information Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-chart-line mr-2 text-primary"></i>
                        Difficulty Level
                    </h3>
                    <p class="text-gray-700"><?php echo htmlspecialchars($course['level']); ?></p>
                </div>
                
                <div class="info-item">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-tag mr-2 text-primary"></i>
                        Category
                    </h3>
                    <p class="text-gray-700"><?php echo htmlspecialchars($course['category_name']); ?></p>
                </div>
                
                <div class="info-item">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-user-graduate mr-2 text-primary"></i>
                        Instructor
                    </h3>
                    <p class="text-gray-700"><?php echo htmlspecialchars($course['teacher']); ?></p>
                </div>
                
                <div class="info-item">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="far fa-calendar mr-2 text-primary"></i>
                        Created Date
                    </h3>
                    <p class="text-gray-700"><?php echo date('F j, Y', strtotime($course['created_at'])); ?></p>
                </div>
            </div>

            <!-- Video/Link Content -->
            <?php if ($course['link']): ?>
                <h2 class="section-title mt-12">
                    <i class="fas fa-video mr-3"></i>Course Content
                </h2>
                <div class="mb-8">
                    <?php 
                    $link = $course['link'];
                    // If teacher saved iframe code → render directly
                    if (stripos($link, '<iframe') !== false) {
                        echo '<div class="video-container">' . $link . '</div>';
                    } 
                    // If normal YouTube link → convert
                    else if (strpos($link, 'youtube.com/watch?v=') !== false) {
                        $embed = str_replace("watch?v=","embed/",$link);
                        echo '<div class="video-container"><iframe src="'.htmlspecialchars($embed).'" allowfullscreen></iframe></div>';
                    } 
                    // If external link
                    else {
                        echo '<div class="video-container"><iframe src="'.htmlspecialchars($link).'" allowfullscreen></iframe></div>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Course Materials -->
            <?php if ($course['material']): ?>
                <h2 class="section-title">
                    <i class="fas fa-file-pdf mr-3"></i>Course Materials
                </h2>
                <div class="mb-8">
                    <div class="material-card">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4">
                                    <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Course Material</h3>
                                    <p class="text-gray-600 text-sm">Download the course materials</p>
                                </div>
                            </div>
                            <a class="action-btn btn-primary" href="<?php echo htmlspecialchars($course['material']); ?>" target="_blank">
                                <i class="fas fa-download"></i>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons for Students -->
            <?php if ($_SESSION['user']['role'] == 'student'): ?>
                <div class="flex flex-wrap gap-4 mt-12 pt-8 border-t border-gray-200">
                    <?php if (!$is_enrolled): ?>
                        <a class="action-btn btn-primary" href="enroll.php?course_id=<?php echo $course_id; ?>">
                            <i class="fas fa-plus-circle"></i>
                            Enroll in Course
                        </a>
                    <?php else: ?>
                        <a class="action-btn btn-success" href="take_quiz.php?course_id=<?php echo $course_id; ?>">
                            <i class="fas fa-tasks"></i>
                            Take Quiz
                        </a>
                        
                        <a class="action-btn btn-accent" href="certificate.php?course_id=<?php echo $course_id; ?>">
                            <i class="fas fa-award"></i>
                            Get Certificate
                        </a>
                        
                        <a class="action-btn btn-secondary" href="progress.php?course_id=<?php echo $course_id; ?>">
                            <i class="fas fa-chart-bar"></i>
                            View Progress
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons for Teachers (if they own the course) -->
            <?php if ($_SESSION['user']['role'] == 'teacher' && $_SESSION['user']['id'] == $course['teacher_id']): ?>
                <div class="flex flex-wrap gap-4 mt-12 pt-8 border-t border-gray-200">
                    <a class="action-btn btn-primary" href="edit_course.php?id=<?php echo $course_id; ?>">
                        <i class="fas fa-edit"></i>
                        Edit Course
                    </a>
                    <a class="action-btn btn-secondary" href="manage_students.php?course_id=<?php echo $course_id; ?>">
                        <i class="fas fa-users"></i>
                        Manage Students
                    </a>
                    <a class="action-btn btn-accent" href="quiz_results.php?course_id=<?php echo $course_id; ?>">
                        <i class="fas fa-chart-pie"></i>
                        View Results
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate action buttons on hover
            const actionButtons = document.querySelectorAll('.action-btn');
            
            actionButtons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>