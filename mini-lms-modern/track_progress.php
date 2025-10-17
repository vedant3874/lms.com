<?php
include 'header.php';
include 'config.php';

// Only allow student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get overall progress statistics
$total_courses = $conn->query("SELECT COUNT(*) as total FROM enrollments WHERE student_id = $student_id")->fetch_assoc()['total'] ?? 0;

// Check what columns exist in student_progress table
$progress_columns = $conn->query("SHOW COLUMNS FROM student_progress");
$progress_cols = [];
while ($col = $progress_columns->fetch_assoc()) {
    $progress_cols[] = $col['Field'];
}

// Get completed courses - adapt based on available columns
if (in_array('progress_percentage', $progress_cols)) {
    $completed_courses = $conn->query("
        SELECT COUNT(*) as completed 
        FROM student_progress 
        WHERE student_id = $student_id AND progress_percentage >= 90
    ")->fetch_assoc()['completed'] ?? 0;
    
    // Get average progress
    $avg_progress_result = $conn->query("
        SELECT AVG(progress_percentage) as avg_progress 
        FROM student_progress 
        WHERE student_id = $student_id
    ");
    $avg_progress = $avg_progress_result ? round($avg_progress_result->fetch_assoc()['avg_progress'] ?? 0, 1) : 0;
} else {
    $completed_courses = 0;
    $avg_progress = 0;
}

// Get quiz statistics
$quiz_stats = $conn->query("
    SELECT 
        COUNT(*) as total_quizzes,
        AVG(score) as avg_score,
        MAX(score) as best_score
    FROM quiz_attempts 
    WHERE student_id = $student_id AND completed = 1
")->fetch_assoc();

// Build the course progress query based on available columns
$select_fields = "
    c.id,
    c.title,
    c.thumbnail,
    c.description,
    u.name as teacher_name
";

$join_condition = "LEFT JOIN student_progress sp ON c.id = sp.course_id AND sp.student_id = e.student_id";

// Add available progress columns
if (in_array('progress_percentage', $progress_cols)) {
    $select_fields .= ", COALESCE(sp.progress_percentage, 0) as progress_percentage";
} else {
    $select_fields .= ", 0 as progress_percentage";
}

if (in_array('quiz_score', $progress_cols)) {
    $select_fields .= ", COALESCE(sp.quiz_score, 0) as quiz_score";
} else {
    $select_fields .= ", 0 as quiz_score";
}

if (in_array('completed_at', $progress_cols)) {
    $select_fields .= ", sp.completed_at";
} else {
    $select_fields .= ", NULL as completed_at";
}

if (in_array('last_accessed', $progress_cols)) {
    $select_fields .= ", sp.last_accessed";
} else {
    $select_fields .= ", NULL as last_accessed";
}

$select_fields .= ", (SELECT COUNT(*) FROM quizzes WHERE course_id = c.id) as total_quizzes";

// Get detailed course progress
$course_progress = $conn->query("
    SELECT 
        $select_fields
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    $join_condition
    WHERE e.student_id = $student_id
    ORDER BY c.title ASC
");

// Get recent activity - simplified to use only available tables
$recent_activity = $conn->query("
    SELECT 
        'quiz' as type,
        c.title as course_title,
        qa.score,
        qa.completed_at as date,
        CONCAT('Scored ', qa.score, ' on quiz') as description
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE qa.student_id = $student_id AND qa.completed = 1
    ORDER BY qa.completed_at DESC
    LIMIT 10
");

// Check if we have any progress data at all
$has_progress_data = $conn->query("SELECT COUNT(*) as count FROM student_progress WHERE student_id = $student_id")->fetch_assoc()['count'] > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Progress - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
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
        
        .course-card {
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .activity-item:hover {
            border-left-color: var(--primary);
            background-color: #f8fafc;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
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
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <div class="gradient-bg text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold flex items-center">
                        <i class="fas fa-chart-line mr-3"></i> Track Your Progress
                    </h1>
                    <p class="mt-2 opacity-90">📊 Monitor your learning journey and achievements</p>
                </div>
                <div class="flex space-x-4">
                    <a href="student_dashboard.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white py-2 px-4 rounded-lg transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                    <a href="my_courses.php" class="bg-white text-blue-600 hover:bg-blue-50 py-2 px-4 rounded-lg transition flex items-center">
                        <i class="fas fa-book-open mr-2"></i> My Courses
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card glass-card text-white gradient-bg p-6 rounded-xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold">Enrolled Courses</h3>
                        <p class="text-3xl font-bold mt-2"><?php echo $total_courses; ?></p>
                    </div>
                    <div class="text-3xl opacity-80">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm opacity-90">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo $completed_courses; ?> completed
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Average Progress</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $avg_progress; ?>%</p>
                    </div>
                    <div class="text-3xl text-green-500">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-trending-up mr-1"></i> Overall completion rate
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Quiz Average</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo round($quiz_stats['avg_score'] ?? 0, 1); ?>%</p>
                    </div>
                    <div class="text-3xl text-blue-500">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-star mr-1 text-yellow-500"></i> From <?php echo $quiz_stats['total_quizzes'] ?? 0; ?> quizzes
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Best Score</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo round($quiz_stats['best_score'] ?? 0, 1); ?>%</p>
                    </div>
                    <div class="text-3xl text-purple-500">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-crown mr-1 text-yellow-500"></i> Highest quiz score
                </div>
            </div>
        </div>

        <?php if (!$has_progress_data): ?>
        <div class="glass-card p-6 mb-8 bg-blue-50 border border-blue-200">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                <div>
                    <h3 class="font-semibold text-blue-800">Start Your Learning Journey</h3>
                    <p class="text-blue-700 text-sm">Progress tracking will appear here as you start taking quizzes and completing course materials.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Course Progress -->
            <div class="lg:col-span-2">
                <!-- Course Progress -->
                <div class="glass-card p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="section-title">📚 Your Courses</h2>
                        <span class="text-sm text-gray-600"><?php echo $total_courses; ?> courses enrolled</span>
                    </div>
                    
                    <?php if ($course_progress && $course_progress->num_rows > 0): ?>
                        <div class="space-y-6">
                            <?php while ($course = $course_progress->fetch_assoc()): ?>
                                <div class="course-card bg-white rounded-xl shadow-md p-6 border border-gray-100">
                                    <div class="flex flex-col md:flex-row md:items-start">
                                        <!-- Course Thumbnail -->
                                        <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                            <?php if (!empty($course['thumbnail'])): ?>
                                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Course Thumbnail" class="w-20 h-20 object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-book text-blue-500 text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Course Details -->
                                        <div class="flex-1">
                                            <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-3">
                                                <div>
                                                    <h3 class="font-bold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($course['title']); ?></h3>
                                                    <p class="text-gray-600 text-sm mb-2">By <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                                                </div>
                                                <div class="flex items-center space-x-2 mb-3 md:mb-0">
                                                    <?php if ($course['progress_percentage'] >= 90): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                                        </span>
                                                    <?php elseif ($course['progress_percentage'] >= 50): ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-spinner mr-1"></i> In Progress
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-play mr-1"></i> Started
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <span class="text-lg font-bold text-gray-800">
                                                        <?php echo $course['progress_percentage']; ?>%
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Progress Bar -->
                                            <div class="mb-4">
                                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                    <span>Course Progress</span>
                                                    <span><?php echo $course['progress_percentage']; ?>%</span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                                </div>
                                            </div>
                                            
                                            <!-- Course Stats -->
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-center mb-4">
                                                <div>
                                                    <p class="text-sm text-gray-600">Quizzes Available</p>
                                                    <p class="font-semibold text-gray-800"><?php echo $course['total_quizzes'] ?? 0; ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-600">Quiz Score</p>
                                                    <p class="font-semibold text-gray-800">
                                                        <?php echo ($course['quiz_score'] > 0) ? $course['quiz_score'] . '%' : 'Not taken'; ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-600">Status</p>
                                                    <p class="font-semibold text-gray-800 text-xs">
                                                        <?php echo ($course['progress_percentage'] >= 90) ? 'Completed' : 'In Progress'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- Action Buttons -->
                                            <div class="flex justify-end space-x-3">
                                                <a href="view_course.php?id=<?php echo $course['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm transition flex items-center">
                                                    <i class="fas fa-play mr-2"></i> Continue
                                                </a>
                                                <?php if ($course['total_quizzes'] > 0): ?>
                                                <a href="take_quiz.php?course_id=<?php echo $course['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg text-sm transition flex items-center">
                                                    <i class="fas fa-tasks mr-2"></i> Take Quiz
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-book-open text-5xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Courses Enrolled</h3>
                            <p class="text-gray-500 mb-6">You haven't enrolled in any courses yet.</p>
                            <a href="browse_courses.php" class="bg-blue-500 hover:bg-blue-600 text-white py-3 px-6 rounded-lg transition inline-flex items-center">
                                <i class="fas fa-search mr-2"></i> Browse Courses
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Charts and Activity -->
            <div class="lg:col-span-1">
                <!-- Progress Chart -->
                <div class="glass-card p-6 mb-8">
                    <h2 class="section-title">📈 Progress Overview</h2>
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card p-6 mb-8">
                    <h2 class="section-title">🕐 Recent Activity</h2>
                    <div class="space-y-4">
                        <?php if ($recent_activity && $recent_activity->num_rows > 0): ?>
                            <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                <div class="activity-item p-3 rounded-lg">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-tasks text-blue-500 text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-800">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo htmlspecialchars($activity['course_title']); ?> • 
                                                <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history text-2xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500 text-sm">No recent activity</p>
                                <p class="text-gray-400 text-xs mt-1">Complete quizzes to see activity here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="glass-card p-6">
                    <h2 class="section-title">⚡ Quick Stats</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Courses Completed</span>
                            <span class="font-bold text-gray-800"><?php echo $completed_courses; ?>/<?php echo $total_courses; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Quizzes</span>
                            <span class="font-bold text-gray-800"><?php echo $quiz_stats['total_quizzes'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Average Score</span>
                            <span class="font-bold text-gray-800"><?php echo round($quiz_stats['avg_score'] ?? 0, 1); ?>%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Learning Level</span>
                            <span class="font-bold text-gray-800">
                                <?php 
                                $total_quizzes = $quiz_stats['total_quizzes'] ?? 0;
                                if ($total_quizzes >= 10) echo 'Expert';
                                elseif ($total_quizzes >= 5) echo 'Intermediate';
                                elseif ($total_quizzes >= 1) echo 'Beginner';
                                else echo 'New Learner';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Progress Chart
            const progressCtx = document.getElementById('progressChart').getContext('2d');
            const progressChart = new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Not Started'],
                    datasets: [{
                        data: [
                            <?php echo $completed_courses; ?>,
                            <?php echo $total_courses - $completed_courses; ?>,
                            0
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#3b82f6',
                            '#6b7280'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Add animations to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = ${index * 0.1}s;
            });

            // Add hover effects
            const courseCards = document.querySelectorAll('.course-card');
            courseCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>