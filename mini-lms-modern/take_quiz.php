<?php 
include 'header.php'; 
include 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') { 
    header('Location: login.php'); 
    exit; 
}

$course_id = intval($_GET['course_id']);

// First, check if there's a quiz for this course and get quiz info
$quiz_res = $conn->query("SELECT id, title FROM quizzes WHERE course_id = $course_id");
if ($quiz_res->num_rows == 0) {
    die('<div class="card">❌ No quiz found for this course. Please contact administrator.</div>');
}

$quiz_data = $quiz_res->fetch_assoc();
$quiz_id = $quiz_data['id'];

// Debug output to check values
echo "<!-- Debug: course_id=$course_id, quiz_id=$quiz_id -->";

if (isset($_POST['submit'])) {
    $score = 0;
    $total_questions = 0;
    
    foreach($_POST as $qid => $ans) {
        if ($qid == 'submit') continue;
        $qid_int = intval($qid);
        $res = $conn->query("SELECT correct_option FROM questions WHERE id=$qid_int AND quiz_id=$quiz_id");
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['correct_option'] == $ans) $score++;
            $total_questions++;
        }
    }
    
    $student_id = $_SESSION['user']['id'];
    $completed_at = date('Y-m-d H:i:s');
    $started_at = date('Y-m-d H:i:s');
    
    // Let's first check what's in the quizzes table
    $check_quiz = $conn->query("SELECT id FROM quizzes WHERE id = $quiz_id");
    if ($check_quiz->num_rows == 0) {
        die('<div class="card">❌ Invalid quiz ID. Quiz not found in database.</div>');
    }
    
    // Also check if this student already has an attempt (if that matters)
    echo "<!-- Debug: Inserting - student_id=$student_id, course_id=$course_id, quiz_id=$quiz_id, score=$score -->";
    
    // Fixed prepared statement
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (student_id, course_id, quiz_id, score, max_score, completed, started_at, completed_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)");
    
    if (!$stmt) {
        die('<div class="card">❌ Prepare failed: ' . $conn->error . '</div>');
    }
    
    $bind_result = $stmt->bind_param("iiiiiss", $student_id, $course_id, $quiz_id, $score, $total_questions, $started_at, $completed_at);
    
    if (!$bind_result) {
        die('<div class="card">❌ Bind failed: ' . $stmt->error . '</div>');
    }
    
    if ($stmt->execute()) {
        echo '<div class="card">✅ You scored '.$score.' out of '.$total_questions.' points! <a href="view_course.php?id='.$course_id.'">Back to course</a></div>';
    } else {
        echo '<div class="card">❌ Error saving quiz attempt: '.$stmt->error.'</div>';
        // Additional debug info
        echo '<div class="card">Debug info - Student ID: '.$student_id.', Course ID: '.$course_id.', Quiz ID: '.$quiz_id.'</div>';
    }
    
    include 'footer.php'; 
    exit;
}

// Get questions for this specific quiz - FIXED: using quiz_id instead of id
$res = $conn->query("SELECT * FROM questions WHERE quiz_id=$quiz_id");
if ($res->num_rows == 0) {
    echo '<div class="card">❌ No questions found for this quiz.</div>';
    include 'footer.php';
    exit;
}
?>

<div class="card">
    <h2>Quiz: <?php echo htmlspecialchars($quiz_data['title']); ?></h2>
    <form method="post">
        <?php 
        $question_num = 1;
        while($q = $res->fetch_assoc()){ 
        ?>
            <div style="margin-bottom:16px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <p><strong>Question <?php echo $question_num; ?>: <?php echo htmlspecialchars($q['question']); ?></strong></p>
                <label><input type="radio" name="<?php echo $q['id']; ?>" value="A" required> A) <?php echo htmlspecialchars($q['option_a']); ?></label><br>
                <label><input type="radio" name="<?php echo $q['id']; ?>" value="B" required> B) <?php echo htmlspecialchars($q['option_b']); ?></label><br>
                <label><input type="radio" name="<?php echo $q['id']; ?>" value="C" required> C) <?php echo htmlspecialchars($q['option_c']); ?></label><br>
                <label><input type="radio" name="<?php echo $q['id']; ?>" value="D" required> D) <?php echo htmlspecialchars($q['option_d']); ?></label><br>
            </div>
            <?php $question_num++; ?>
        <?php } ?>
        <button type="submit" name="submit" class="btn btn-primary">Submit Answers</button>
    </form>
</div>

<?php include 'footer.php'; ?>