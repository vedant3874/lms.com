<?php
// Start session and include files at the very top
session_start();
include 'config.php';
include 'header.php';

// Only allow student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['user']['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <!-- Topbar -->
        <div class="mb-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center">
                <span class="mr-2">🔍</span> Browse Courses
            </h2>
            <p class="text-gray-600 mt-2">Explore available courses to enroll in.</p>
        </div>

        <!-- Available Courses -->
        <div class="courses bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">🎓 All Available Courses</h3>
            <?php
            // Check if connection is established
            if (!$conn) {
                echo '<p class="text-red-600">Database connection failed. Please try again later.</p>';
            } else {
                $res = $conn->query("
                    SELECT courses.*, users.name AS teacher 
                    FROM courses 
                    JOIN users ON courses.teacher_id = users.id 
                    LEFT JOIN enrollments ON courses.id = enrollments.course_id AND enrollments.student_id = $student_id
                    WHERE enrollments.id IS NULL
                    ORDER BY courses.created_at DESC
                ");
                
                if ($res && $res->num_rows > 0) {
                    echo '<div class="course-grid">';
                    while ($row = $res->fetch_assoc()) {
                        echo '<div class="course-card bg-gray-50 rounded-lg shadow p-4 flex flex-col">';
                        echo '<div class="course-thumb mb-4">';
                        if (!empty($row['thumbnail'])) {
                            echo '<img src="' . htmlspecialchars($row['thumbnail']) . '" alt="Thumbnail" class="w-full h-32 object-cover rounded">';
                        } else {
                            echo '<div class="no-thumb bg-gray-200 h-32 flex items-center justify-center rounded">📷</div>';
                        }
                        echo '</div>';
                        echo '<div class="course-info flex-1">';
                        echo '<h4 class="text-lg font-semibold text-gray-800">' . htmlspecialchars($row['title']) . '</h4>';
                        echo '<p class="text-gray-600 text-sm mt-2">By ' . htmlspecialchars($row['teacher']) . '</p>';
                        echo '<p class="text-gray-600 text-sm mt-2">' . htmlspecialchars(substr($row['description'], 0, 100)) . '...</p>';
                        echo '<div class="course-actions mt-4 flex gap-2">';
                        echo '<a class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-sm" href="enroll.php?course_id=' . $row['id'] . '">Enroll</a>';
                        echo '<a class="bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition text-sm" href="view_course.php?id=' . $row['id'] . '">View</a>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="text-gray-600">No courses available for enrollment.</p>';
                }
            }
            ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
</html>