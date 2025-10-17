<?php
include 'header.php';
include 'config.php';

// Security check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header('Location: login.php');
    exit;
}

// Handle form submission
if (isset($_POST['add'])) {
    $course_id = intval($_POST['course_id']);
    $q = trim($_POST['question']);
    $a = trim($_POST['a']);
    $b = trim($_POST['b']);
    $c = trim($_POST['c']);
    $d = trim($_POST['d']);
    $correct = $_POST['correct'];
    
    // Validate all fields are filled
    if (empty($q) || empty($a) || empty($b) || empty($c) || empty($d)) {
        $error = "All fields are required!";
    } else {
        $sql = "INSERT INTO questions (course_id,question,option_a,option_b,option_c,option_d,correct_option) VALUES (?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssss", $course_id, $q, $a, $b, $c, $d, $correct);
        
        if ($stmt->execute()) {
            $success = "✅ Question added successfully!";
            // Clear form after successful submission
            $_POST = array();
        } else {
            $error = "❌ Error: " . htmlspecialchars($conn->error);
        }
    }
}

// Get teacher's courses
$courses = $conn->query("SELECT * FROM courses WHERE teacher_id=" . $_SESSION['user']['id']);

// Get existing quiz questions for the selected course
$existing_questions = array();
if (isset($_POST['course_id']) || isset($_GET['course_id'])) {
    $selected_course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : intval($_GET['course_id']);
    $questions_query = $conn->query("SELECT * FROM questions WHERE course_id = $selected_course_id ORDER BY id DESC");
    while ($question = $questions_query->fetch_assoc()) {
        $existing_questions[] = $question;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1, h2, h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        h1 {
            font-size: 2.2rem;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        h3 {
            font-size: 1.4rem;
            margin-top: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        select, textarea, input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s, box-shadow 0.3s;
        }
        
        select:focus, textarea:focus, input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        .question-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .question-item {
            background: var(--light);
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 8px 8px 0;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .option {
            padding: 8px;
            background: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .correct {
            background: #d4edda;
            border-left: 3px solid #28a745;
        }
        
        .option-label {
            font-weight: 600;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            border-radius: 50%;
        }
        
        .correct .option-label {
            background: #28a745;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-secondary {
            background: var(--gray);
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
    </style>
</head>
<body>      <form method="post" id="quizForm">
                    <label for="course_id"><i class="fas fa-book"></i> Select Course</label>
                    <select name="course_id" id="course_id" required onchange="this.form.submit()" style="background-color:black">
                        <option value="">Select Course</option>
                        <?php 
                        $courses->data_seek(0); // Reset pointer
                        while($c = $courses->fetch_assoc()): 
                            $selected = (isset($selected_course_id) && $selected_course_id == $c['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo intval($c['id']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                        </form> 
<div class="card">
                <h2><i class="fas fa-list"></i> Existing Questions</h2>
                <div class="question-list">
                    <?php if (empty($existing_questions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No questions added yet for this course.</p>
                            <p>Select a course and add your first question!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($existing_questions as $index => $question): ?>
                            <div class="question-item">
                                <div class="question-text"><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question']); ?></div>
                                <div class="options">
                                    <div class="option <?php echo $question['correct_option'] == 'A' ? 'correct' : ''; ?>">
                                        <span class="option-label">A</span>
                                        <span><?php echo htmlspecialchars($question['option_a']); ?></span>
                                    </div>
                                    <div class="option <?php echo $question['correct_option'] == 'B' ? 'correct' : ''; ?>">
                                        <span class="option-label">B</span>
                                        <span><?php echo htmlspecialchars($question['option_b']); ?></span>
                                    </div>
                                    <div class="option <?php echo $question['correct_option'] == 'C' ? 'correct' : ''; ?>">
                                        <span class="option-label">C</span>
                                        <span><?php echo htmlspecialchars($question['option_c']); ?></span>
                                    </div>
                                    <div class="option <?php echo $question['correct_option'] == 'D' ? 'correct' : ''; ?>">
                                        <span class="option-label">D</span>
                                        <span><?php echo htmlspecialchars($question['option_d']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-submit form when course is selected
        document.getElementById('course_id').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('quizForm').submit();
            }
        });
        
        // Form validation
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const question = document.getElementById('question').value.trim();
            const a = document.getElementById('a').value.trim();
            const b = document.getElementById('b').value.trim();
            const c = document.getElementById('c').value.trim();
            const d = document.getElementById('d').value.trim();
            
            if (!question || !a || !b || !c || !d) {
                e.preventDefault();
                alert('Please fill in all fields before submitting.');
            }
        });
    </script>
</body>
</html>
</body>
</html>