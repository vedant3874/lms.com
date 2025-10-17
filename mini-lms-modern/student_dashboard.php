<?php
// File: student_dashboard.php

include 'header.php';
include 'config.php';

// Only allow student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['user']['id'];

// Stats
$enrolledCourses = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE student_id=$student_id")->fetch_assoc()['c'] ?? 0;

// Query completed quizzes using prepared statement
$completedQuizzes = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) as q 
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN courses c ON q.course_id = c.id 
    JOIN enrollments e ON e.course_id = c.id 
    WHERE e.student_id = ? AND qa.completed = 1
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$completedQuizzes = $stmt->get_result()->fetch_assoc()['q'] ?? 0;
$stmt->close();

// Get unread message count
$unread_messages = getUnreadMessageCount($student_id, $conn);

// Get recent messages (last 3)
$recent_messages = [];
$stmt = $conn->prepare("
    SELECT m.*, u.name as sender_name, u.role as sender_role 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = ? 
    ORDER BY m.created_at DESC 
    LIMIT 3
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recent_messages_result = $stmt->get_result();
while ($msg = $recent_messages_result->fetch_assoc()) {
    $recent_messages[] = $msg;
}
$stmt->close();

// Get recent announcements for enrolled courses
$recent_announcements = [];
$stmt = $conn->prepare("
    SELECT a.*, c.title as course_name, u.name as teacher_name 
    FROM announcements a 
    JOIN courses c ON a.course_id = c.id 
    JOIN users u ON a.teacher_id = u.id 
    WHERE a.course_id IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    ) 
    OR a.course_id IS NULL  -- Include general announcements
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$announcements_result = $stmt->get_result();
while ($announcement = $announcements_result->fetch_assoc()) {
    $recent_announcements[] = $announcement;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Learning Management System</title>
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
        
        .icon-announcement {
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
        
        .message-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
        }
        
        .message-item:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
        }
        
        .message-item.unread {
            background-color: #f0f9ff;
            border-left: 3px solid #3b82f6;
        }
        
        .message-preview {
            color: #6b7280;
            font-size: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .announcement-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            border-left: 4px solid #4f46e5;
        }
        
        .announcement-item:hover {
            background-color: #f8fafc;
            transform: translateX(4px);
        }
        
        .announcement-item.priority-high {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .announcement-item.priority-medium {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }
        
        .announcement-item.priority-low {
            border-left-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .announcement-preview {
            color: #6b7280;
            font-size: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="min-h-screen" >
    <!-- Header -->
    <div class="gradient-bg text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold flex items-center">
                        <i class="fas fa-graduation-cap mr-3"></i> Student Dashboard
                    </h1>
                    <p class="mt-2 opacity-90">👋 Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong></p>
                </div>
                <div class="flex space-x-4">
                    <a href="browse_courses.php" class="btn-secondary">
                        <i class="fas fa-search"></i> Browse Courses
                    </a>
                    <a href="messages.php" class="btn-primary pulse relative">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($unread_messages > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center">
                                <?php echo $unread_messages; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card glass-card text-white gradient-bg p-6 rounded-xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold">Enrolled Courses</h3>
                        <p class="text-3xl font-bold mt-2"><?php echo $enrolledCourses; ?></p>
                    </div>
                    <div class="text-3xl opacity-80">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm opacity-90">
                    <i class="fas fa-arrow-up mr-1"></i> Continue your learning journey
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-green-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Completed Quizzes</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $completedQuizzes; ?></p>
                    </div>
                    <div class="text-3xl text-green-500">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-trophy mr-1 text-yellow-500"></i> Great progress!
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-blue-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Unread Messages</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo $unread_messages; ?></p>
                    </div>
                    <div class="text-3xl text-blue-500">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-comment mr-1"></i> New messages waiting
                </div>
            </div>
            
            <div class="stat-card glass-card bg-white p-6 rounded-xl border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">New Announcements</h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900"><?php echo count($recent_announcements); ?></p>
                    </div>
                    <div class="text-3xl text-purple-500">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <i class="fas fa-bell mr-1 text-yellow-500"></i> Recent updates
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Enrolled Courses -->
            <div class="lg:col-span-2">
                <!-- Recent Announcements -->
                <div class="glass-card p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="section-title">📢 Recent Announcements</h2>
                        <a href="announcements.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    <div class="space-y-4">
                        <?php if (!empty($recent_announcements)): ?>
                            <?php foreach ($recent_announcements as $announcement): ?>
                                <div class="announcement-item <?php echo 'priority-' . $announcement['priority']; ?>">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-gray-800 text-sm flex items-center">
                                            <?php echo htmlspecialchars($announcement['title']); ?>
                                            <span class="ml-2 text-xs px-2 py-1 rounded-full 
                                                <?php 
                                                if ($announcement['priority'] == 'high') echo 'bg-red-100 text-red-800';
                                                elseif ($announcement['priority'] == 'medium') echo 'bg-yellow-100 text-yellow-800';
                                                else echo 'bg-green-100 text-green-800';
                                                ?>
                                            ">
                                                <?php echo ucfirst($announcement['priority']); ?>
                                            </span>
                                        </h4>
                                        <span class="text-xs text-gray-500 whitespace-nowrap">
                                            <?php echo date('M j, g:i A', strtotime($announcement['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 font-medium mb-1">
                                        From: <?php echo htmlspecialchars($announcement['teacher_name']); ?>
                                        <?php if ($announcement['course_name']): ?>
                                            • <?php echo htmlspecialchars($announcement['course_name']); ?>
                                        <?php else: ?>
                                            • General Announcement
                                        <?php endif; ?>
                                    </p>
                                    <p class="announcement-preview">
                                        <?php echo htmlspecialchars(substr($announcement['content'], 0, 120)); ?>
                                        <?php echo strlen($announcement['content']) > 120 ? '...' : ''; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-bullhorn text-3xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">No announcements yet</p>
                                <p class="text-gray-400 text-xs mt-1">Announcements from your teachers will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enrolled Courses -->
                <div class="glass-card p-6 mb-8">
                    <h2 class="section-title">📚 Your Enrolled Courses</h2>
                    <?php
                    $res = $conn->query("
                        SELECT courses.id, courses.title, courses.thumbnail, courses.description, users.name AS teacher 
                        FROM enrollments 
                        JOIN courses ON enrollments.course_id = courses.id 
                        JOIN users ON courses.teacher_id = users.id 
                        WHERE enrollments.student_id = $student_id
                        ORDER BY enrollments.created_at DESC
                        LIMIT 6
                    ");
                    if ($res && $res->num_rows > 0) {
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                        while ($row = $res->fetch_assoc()) {
                            echo '<div class="course-card bg-white rounded-xl shadow-md p-4 border border-gray-100">';
                            echo '<div class="flex">';
                            echo '<div class="flex-shrink-0 mr-4">';
                            if (!empty($row['thumbnail'])) {
                                echo '<img src="' . htmlspecialchars($row['thumbnail']) . '" alt="Thumbnail" class="w-16 h-16 object-cover rounded-lg">';
                            } else {
                                echo '<div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center">';
                                echo '<i class="fas fa-book text-blue-500 text-xl"></i>';
                                echo '</div>';
                            }
                            echo '</div>';
                            echo '<div class="flex-1">';
                            echo '<h4 class="font-semibold text-gray-800 text-lg mb-1">' . htmlspecialchars($row['title']) . '</h4>';
                            echo '<p class="text-gray-600 text-sm mb-2">By ' . htmlspecialchars($row['teacher']) . '</p>';
                            echo '<div class="flex justify-between items-center mt-3">';
                            echo '<a class="btn-primary text-sm py-2 px-4" href="view_course.php?id=' . $row['id'] . '">Continue</a>';
                            echo '<a class="btn-secondary text-sm py-2 px-3" href="messages.php?receiver_id=' . $row['teacher'] . '&subject=Question about ' . urlencode($row['title']) . '">Message Teacher</a>';
                            echo '</div>';
                            echo '<div class="progress-bar mt-2">';
                            echo '<div class="progress-fill" style="width: 65%"></div>';
                            echo '</div>';
                            echo '<div class="text-xs text-gray-500 mt-1 text-right">65% complete</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '<div class="text-center mt-6">';
                        echo '<a href="my_courses.php" class="btn-secondary">View All Courses</a>';
                        echo '</div>';
                    } else {
                        echo '<div class="text-center py-8">';
                        echo '<i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>';
                        echo '<p class="text-gray-600">You haven\'t enrolled in any courses yet.</p>';
                        echo '<a href="browse_courses.php" class="btn-primary mt-4">Browse Courses</a>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <!-- Available Courses -->
                <div class="glass-card p-6">
                    <h2 class="section-title">🎓 Available Courses</h2>
                    <?php
                    $res = $conn->query("
                        SELECT courses.*, users.name AS teacher 
                        FROM courses 
                        JOIN users ON courses.teacher_id = users.id 
                        LEFT JOIN enrollments ON courses.id = enrollments.course_id AND enrollments.student_id = $student_id
                        WHERE enrollments.id IS NULL
                        ORDER BY courses.created_at DESC
                        LIMIT 4
                    ");
                    if ($res && $res->num_rows > 0) {
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
                        while ($row = $res->fetch_assoc()) {
                            echo '<div class="course-card bg-white rounded-xl shadow-md overflow-hidden">';
                            echo '<div class="course-thumb">';
                            if (!empty($row['thumbnail'])) {
                                echo '<img src="' . htmlspecialchars($row['thumbnail']) . '" alt="Thumbnail" class="w-full h-32 object-cover">';
                            } else {
                                echo '<div class="w-full h-32 bg-gradient-to-br from-blue-200 to-purple-200 flex items-center justify-center">';
                                echo '<i class="fas fa-book-open text-white text-4xl"></i>';
                                echo '</div>';
                            }
                            echo '</div>';
                            echo '<div class="p-4">';
                            echo '<h4 class="font-semibold text-gray-800 text-lg mb-1">' . htmlspecialchars($row['title']) . '</h4>';
                            echo '<p class="text-gray-600 text-sm mb-2">By ' . htmlspecialchars($row['teacher']) . '</p>';
                            echo '<p class="text-gray-600 text-sm mb-4">' . htmlspecialchars(substr($row['description'], 0, 80)) . '...</p>';
                            echo '<div class="flex justify-between">';
                            echo '<a class="btn-primary text-sm py-2 px-3" href="enroll.php?course_id=' . $row['id'] . '">Enroll Now</a>';
                            echo '<a class="btn-secondary text-sm py-2 px-3" href="view_course.php?id=' . $row['id'] . '">Preview</a>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                        echo '<div class="text-center mt-6">';
                        echo '<a href="browse_courses.php" class="btn-secondary">Browse All Courses</a>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-gray-600 text-center py-8">No new courses available for enrollment at the moment.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div class="lg:col-span-1">
                <!-- Recent Messages -->
                <div class="glass-card p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="section-title">💬 Recent Messages</h2>
                        <a href="messages.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    <div class="space-y-3">
                        <?php if (!empty($recent_messages)): ?>
                            <?php foreach ($recent_messages as $message): ?>
                                <a href="messages.php?id=<?php echo $message['id']; ?>" 
                                   class="block message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?>">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="font-semibold text-gray-800 text-sm truncate flex-1 mr-2">
                                            <?php echo htmlspecialchars($message['sender_name']); ?>
                                        </h4>
                                        <span class="text-xs text-gray-500 whitespace-nowrap">
                                            <?php echo date('M j', strtotime($message['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 font-medium mb-1 truncate">
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                    </p>
                                    <p class="message-preview text-gray-500">
                                        <?php echo htmlspecialchars(substr($message['message'], 0, 60)); ?>
                                        <?php echo strlen($message['message']) > 60 ? '...' : ''; ?>
                                    </p>
                                    <?php if (!$message['is_read']): ?>
                                        <div class="flex justify-end mt-1">
                                            <span class="inline-block bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                                New
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-envelope-open text-2xl text-gray-300 mb-2"></i>
                                <p class="text-gray-500 text-sm">No messages yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="messages.php" class="btn-primary w-full text-sm py-2">
                            <i class="fas fa-edit mr-2"></i>Compose Message
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="glass-card p-6 mb-8">
                    <h2 class="section-title">📈 Recent Activity</h2>
                    <div class="space-y-4">
                        <div class="activity-item">
                            <div class="activity-icon icon-announcement">
                                <i class="fas fa-bullhorn text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">New announcement in PHP Course</p>
                                <p class="text-sm text-gray-600">1 hour ago</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon icon-quiz">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Completed Web Dev Quiz</p>
                                <p class="text-sm text-gray-600">2 hours ago</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon icon-course">
                                <i class="fas fa-play text-white"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Started Python Course</p>
                                <p class="text-sm text-gray-600">1 day ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="activity.php" class="text-sm text-blue-600 hover:underline">View All Activity</a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card p-6">
                    <h2 class="section-title">⚡ Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="browse_courses.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-search text-white"></i>
                            </div>
                            <span class="font-medium text-gray-800">Browse Courses</span>
                        </a>
                        <a href="announcements.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-bullhorn text-white"></i>
                            </div>
                            <span class="font-medium text-gray-800">Announcements</span>
                            <?php if (count($recent_announcements) > 0): ?>
                                <span class="ml-auto bg-purple-500 text-white text-xs px-2 py-1 rounded-full">
                                    <?php echo count($recent_announcements); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                            <span class="font-medium text-gray-800">Messages</span>
                            <?php if ($unread_messages > 0): ?>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                    <?php echo $unread_messages; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="track_progress.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-chart-line text-white"></i>
                            </div>
                            <span class="font-medium text-gray-800">Track Progress</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>
        // Simple animations for elements
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

            // Add hover effect to message items
            const messageItems = document.querySelectorAll('.message-item');
            messageItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Add hover effect to announcement items
            const announcementItems = document.querySelectorAll('.announcement-item');
            announcementItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(4px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>