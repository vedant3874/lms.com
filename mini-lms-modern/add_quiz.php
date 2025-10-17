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
        // First, check if a quiz exists for this course, if not create one
        $quiz_check = $conn->query("SELECT id FROM quizzes WHERE course_id = $course_id");
        if ($quiz_check->num_rows == 0) {
            // Create a new quiz for this course
            $course_name = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc()['title'];
            $quiz_title = $course_name . " Quiz";
            $conn->query("INSERT INTO quizzes (course_id, title) VALUES ($course_id, '$quiz_title')");
            $quiz_id = $conn->insert_id;
        } else {
            $quiz_data = $quiz_check->fetch_assoc();
            $quiz_id = $quiz_data['id'];
        }
        
        // Now insert the question with the quiz_id
        $sql = "INSERT INTO questions (course_id, quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssss", $course_id, $quiz_id, $q, $a, $b, $c, $d, $correct);
        
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
$selected_course_id = null;

if (isset($_POST['course_id']) || isset($_GET['course_id'])) {
    $selected_course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : intval($_GET['course_id']);
    
    // Get quiz ID for this course
    $quiz_check = $conn->query("SELECT id FROM quizzes WHERE course_id = $selected_course_id");
    if ($quiz_check->num_rows > 0) {
        $quiz_data = $quiz_check->fetch_assoc();
        $quiz_id = $quiz_data['id'];
        
        // Get questions for this quiz
        $questions_query = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id DESC");
        while ($question = $questions_query->fetch_assoc()) {
            $existing_questions[] = $question;
        }
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
            color: white;
            text-decoration: none;
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
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
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
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1><i class="fas fa-tasks"></i> Quiz Management System</h1>
            <a href="dashboard.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $courses->num_rows; ?></div>
                <div class="stat-label">Your Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($existing_questions); ?></div>
                <div class="stat-label">Questions in Selected Course</div>
            </div>
        </div>
        
        <div class="grid">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Question</h2>
                <form method="post" id="quizForm">
                    <label for="course_id"><i class="fas fa-book"></i> Select Course</label>
                    <select name="course_id" id="course_id" required onchange="this.form.submit()">
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
                    
                    <?php if (isset($selected_course_id) && $selected_course_id): ?>
                        <label for="question"><i class="fas fa-question-circle"></i> Question</label>
                        <textarea name="question" id="question" rows="3" required placeholder="Enter your question here..."><?php echo isset($_POST['question']) ? htmlspecialchars($_POST['question']) : ''; ?></textarea>
                        
                        <div class="options-grid">
                            <div>
                                <label for="a"><i class="fas fa-circle"></i> Option A</label>
                                <input type="text" name="a" id="a" value="<?php echo isset($_POST['a']) ? htmlspecialchars($_POST['a']) : ''; ?>" required>
                            </div>
                            <div>
                                <label for="b"><i class="fas fa-circle"></i> Option B</label>
                                <input type="text" name="b" id="b" value="<?php echo isset($_POST['b']) ? htmlspecialchars($_POST['b']) : ''; ?>" required>
                            </div>
                            <div>
                                <label for="c"><i class="fas fa-circle"></i> Option C</label>
                                <input type="text" name="c" id="c" value="<?php echo isset($_POST['c']) ? htmlspecialchars($_POST['c']) : ''; ?>" required>
                            </div>
                            <div>
                                <label for="d"><i class="fas fa-circle"></i> Option D</label>
                                <input type="text" name="d" id="d" value="<?php echo isset($_POST['d']) ? htmlspecialchars($_POST['d']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <label for="correct"><i class="fas fa-check-circle"></i> Correct Option</label>
                        <select name="correct" id="correct" required>
                            <option value="A" <?php echo (isset($_POST['correct']) && $_POST['correct'] == 'A') ? 'selected' : ''; ?>>Option A</option>
                            <option value="B" <?php echo (isset($_POST['correct']) && $_POST['correct'] == 'B') ? 'selected' : ''; ?>>Option B</option>
                            <option value="C" <?php echo (isset($_POST['correct']) && $_POST['correct'] == 'C') ? 'selected' : ''; ?>>Option C</option>
                            <option value="D" <?php echo (isset($_POST['correct']) && $_POST['correct'] == 'D') ? 'selected' : ''; ?>>Option D</option>
                        </select>
                        
                        <button type="submit" name="add"><i class="fas fa-save"></i> Add Question</button>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <p>Please select a course to add questions</p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
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
            const courseId = document.getElementById('course_id').value;
            if (!courseId) {
                e.preventDefault();
                alert('Please select a course first.');
                return;
            }
            
            const question = document.getElementById('question') ? document.getElementById('question').value.trim() : '';
            const a = document.getElementById('a') ? document.getElementById('a').value.trim() : '';
            const b = document.getElementById('b') ? document.getElementById('b').value.trim() : '';
            const c = document.getElementById('c') ? document.getElementById('c').value.trim() : '';
            const d = document.getElementById('d') ? document.getElementById('d').value.trim() : '';
            
            if (!question || !a || !b || !c || !d) {
                e.preventDefault();
                alert('Please fill in all fields before submitting.');
            }
        });
    </script>
</body>
</html>
<?php include 'footer.php'; ?>