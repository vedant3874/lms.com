<?php include 'header.php'; include 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') { header('Location: login.php'); exit; }
$student_id = $_SESSION['user']['id']; $course_id = intval($_GET['course_id']);
$stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id=? AND course_id=?"); $stmt->bind_param("ii",$student_id,$course_id); $stmt->execute(); $stmt->store_result();
if ($stmt->num_rows > 0) { echo '<div class="card">⚠ Already enrolled. <a href="student_dashboard.php">Back</a></div>'; include 'footer.php'; exit; }
$stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?,?)"); $stmt->bind_param("ii",$student_id,$course_id);
if ($stmt->execute()) echo '<div class="card">✅ Enrolled successfully! <a href="student_dashboard.php">Go back</a></div>'; else echo '<div class="card">❌ Error: '.htmlspecialchars($conn->error).'</div>';
include 'footer.php'; ?>