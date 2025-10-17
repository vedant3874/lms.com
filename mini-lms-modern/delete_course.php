<?php
include 'header.php';
include 'config.php';

// Only allow teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$course_id = $_GET['id'] ?? 0;

// Check if course belongs to this teacher
$course_query = $conn->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$course_query->bind_param("ii", $course_id, $teacher_id);
$course_query->execute();
$course = $course_query->get_result()->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found or you don't have permission to delete it.";
    header('Location: teacher_dashboard.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check if there are enrollments
            $enrollment_check = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
            $enrollment_check->bind_param("i", $course_id);
            $enrollment_check->execute();
            $enrollment_count = $enrollment_check->get_result()->fetch_assoc()['count'];
            
            if ($enrollment_count > 0) {
                $_SESSION['error'] = "Cannot delete course with enrolled students. Please remove all enrollments first.";
                header('Location: teacher_dashboard.php');
                exit;
            }
            
            // Delete related quizzes first
            $delete_quizzes = $conn->prepare("DELETE FROM quizzes WHERE course_id = ?");
            $delete_quizzes->bind_param("i", $course_id);
            $delete_quizzes->execute();
            
            // Delete course materials if any
            // Add additional deletion queries for other related tables as needed
            
            // Delete the course
            $delete_course = $conn->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
            $delete_course->bind_param("ii", $course_id, $teacher_id);
            
            if ($delete_course->execute()) {
                // Delete thumbnail file if it exists and is not default
                if (!empty($course['thumbnail']) && file_exists($course['thumbnail']) && 
                    !str_contains($course['thumbnail'], 'default-course.jpg')) {
                    unlink($course['thumbnail']);
                }
                
                $conn->commit();
                $_SESSION['success'] = "Course deleted successfully!";
                header('Location: teacher_dashboard.php');
                exit;
            } else {
                throw new Exception("Error deleting course: " . $conn->error);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
            header('Location: teacher_dashboard.php');
            exit;
        }
    } else {
        // User cancelled deletion
        header('Location: teacher_dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Course - Learning Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --danger: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #fdf2f8 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Sidebar -->
    <div class="sidebar" style="background: linear-gradient(180deg, #4f46e5 0%, #4338ca 100%); color: white; min-height: 100vh; width: 260px; position: fixed; top: 0; left: 0;">
        <div class="p-6">
            <div class="flex items-center mb-10">
                <i class="fas fa-graduation-cap text-2xl mr-3"></i>
                <h1 class="text-xl font-bold">EduPortal</h1>
            </div>
            
            <nav class="space-y-2">
                <a href="teacher_dashboard.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Dashboard</span>
                </a>
                <a href="courses.php" class="flex items-center py-3 px-4 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                    <i class="fas fa-book mr-3"></i>
                    <span>Courses</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" style="margin-left: 260px; padding: 30px; min-height: 100vh;">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Delete Course</h1>
                <p class="text-gray-600">This action cannot be undone</p>
            </div>

            <!-- Warning Card -->
            <div class="glass-card p-8 mb-6 border-l-4 border-red-500">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Warning: Permanent Deletion</h3>
                        <p class="text-gray-600 mb-4">
                            You are about to delete the course "<strong><?php echo htmlspecialchars($course['title']); ?></strong>". 
                            This will permanently remove:
                        </p>
                        <ul class="text-gray-600 list-disc list-inside space-y-1 mb-4">
                            <li>All course content and materials</li>
                            <li>All quizzes and assessments</li>
                            <li>Course progress and analytics</li>
                            <li>All student enrollment records</li>
                        </ul>
                        <p class="text-red-600 font-semibold">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            This action cannot be undone!
                        </p>
                    </div>
                </div>
            </div>

            <!-- Course Info -->
            <div class="glass-card p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Course Details</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden">
                            <?php if (!empty($course['thumbnail']) && file_exists($course['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Course Thumbnail" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center">
                                    <i class="fas fa-book text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['title']); ?></h4>
                            <p class="text-sm text-gray-600">Level: <?php echo htmlspecialchars($course['level']); ?></p>
                            <p class="text-sm text-gray-600">Created: <?php echo date('M j, Y', strtotime($course['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <p><?php echo htmlspecialchars(substr($course['description'], 0, 150)); ?>...</p>
                    </div>
                </div>
            </div>

            <!-- Confirmation Form -->
            <div class="glass-card p-6">
                <form action="" method="POST">
                    <div class="mb-4">
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="confirm_delete" value="1" required 
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            <span class="text-gray-700 font-medium">
                                I understand that this action cannot be undone and I accept the consequences
                            </span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="teacher_dashboard.php" class="btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i> Cancel
                        </a>
                        <button type="submit" class="btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this course?')">
                            <i class="fas fa-trash mr-2"></i> Delete Course Permanently
                        </button>
                    </div>
                </form>
            </div>

            <!-- Additional Warning -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-shield-alt mr-2"></i>
                    For security reasons, this action requires explicit confirmation
                </p>
            </div>
        </div>
    </div>

    <script>
        // Double confirmation for deletion
        document.querySelector('form').addEventListener('submit', function(e) {
            const checkbox = document.querySelector('input[name="confirm_delete"]');
            if (!checkbox.checked) {
                e.preventDefault();
                alert('Please confirm that you understand this action cannot be undone.');
                return false;
            }
            
            const confirmed = confirm('⚠ FINAL WARNING: This will permanently delete the course and all associated data. This action cannot be reversed. Are you absolutely sure?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>