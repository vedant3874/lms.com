<?php
// ✅ Start session and include files at the VERY TOP
session_start();
include 'header.php';
include 'config.php';

// ✅ Only teachers
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') { 
    header('Location: login.php'); 
    exit; 
}

// ✅ Handle form submit
if (isset($_POST['create'])) {
    $title = trim($_POST['title']); 
    $desc = trim($_POST['description']); 
    $level = $_POST['level'];
    $category = $_POST['category'];
    $link = trim($_POST['link']);   // link OR iframe code
    $teacher_id = $_SESSION['user']['id'];

    $material = null;
    $thumbnail = null;

    // Upload material
    if (!empty($_FILES['material']['name'])) {
        $uploads_dir = 'uploads/materials'; 
        if (!is_dir($uploads_dir)) mkdir($uploads_dir,0755,true);
        $target = $uploads_dir . '/' . time() . '-' . basename($_FILES['material']['name']);
        if(move_uploaded_file($_FILES['material']['tmp_name'], $target)) {
            $material = $target;
        }
    }

    // Upload thumbnail
    if (!empty($_FILES['thumbnail']['name'])) {
        $uploads_dir = 'uploads/thumbnails'; 
        if (!is_dir($uploads_dir)) mkdir($uploads_dir,0755,true);
        $target = $uploads_dir . '/' . time() . '-' . basename($_FILES['thumbnail']['name']);
        if(move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target)) {
            $thumbnail = $target;
        }
    }

    // Insert into DB
    $sql = "INSERT INTO courses 
            (teacher_id, title, description, level, category_id, material, thumbnail, link) 
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("isssisss", $teacher_id, $title, $desc, $level, $category, $material, $thumbnail, $link);

    if ($stmt->execute()) {
        $success_message = '✅ Course created successfully!';
    } else {
        $error_message = '❌ Error: '.htmlspecialchars($conn->error);
    }
}

// ✅ Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Debug: Check what categories we have
echo "<!-- Debug: Categories found -->";
if ($categories->num_rows > 0) {
    while($cat = $categories->fetch_assoc()) {
        echo "<!-- Category: ID={$cat['id']}, Name={$cat['name']} -->";
    }
    // Reset pointer after debugging
    $categories->data_seek(0);
} else {
    echo "<!-- No categories found in database -->";
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Course - Learning Management System</title>
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            background: white;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-weight: 500;
        }
        
        .file-input-label:hover {
            border-color: var(--primary);
            background: #f8faff;
            color: var(--primary);
        }
        
        .file-input-label i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        .preview-container {
            margin-top: 1rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e2e8f0;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }
        
        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .preview-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .upload-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .form-grid .form-group:last-child,
            .form-grid .form-group:nth-last-child(2) {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body class="min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="glass-card p-8 mb-8 text-center">
            <div class="upload-icon">
                <i class="fas fa-plus"></i>
            </div>
            <h1 class="section-title">Create New Course</h1>
            <p class="text-gray-600 text-lg">Fill in the details below to create an engaging learning experience</p>
        </div>

        <!-- Display success/error messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Course Creation Form -->
        <div class="glass-card p-8">
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <!-- Course Title -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-heading mr-2"></i>Course Title
                    </label>
                    <input type="text" name="title" class="form-input" placeholder="Enter course title" required>
                </div>

               <!-- Category -->
<div class="form-group">
    <label class="form-label">
        <i class="fas fa-tag mr-2"></i>Category
    </label>
    <select name="category" class="form-input form-select" required>
        <option value="">-- Select Category --</option>
        <?php 
        // Reset the pointer if needed
        $categories->data_seek(0);
        while($cat = $categories->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>
                <!-- Course Description -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-align-left mr-2"></i>Description
                    </label>
                    <textarea name="description" rows="4" class="form-input form-textarea" placeholder="Describe what students will learn in this course..."></textarea>
                </div>

                <!-- Difficulty Level -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-chart-line mr-2"></i>Difficulty Level
                    </label>
                    <select name="level" class="form-input form-select" required>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>

                <!-- Thumbnail Upload -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-image mr-2"></i>Course Thumbnail
                    </label>
                    <div class="file-input-wrapper">
                        <input type="file" name="thumbnail" accept="image/*" onchange="previewImage(event)" class="file-input" id="thumbnail">
                        <label for="thumbnail" class="file-input-label">
                            <i class="fas fa-upload"></i> Choose Thumbnail Image
                        </label>
                    </div>
                    <div class="preview-container text-center">
                        <img id="thumbPreview" class="max-w-full rounded-lg shadow-md" style="display:none; max-height: 200px;">
                        <p id="noPreview" class="text-gray-500 text-sm">Thumbnail preview will appear here</p>
                    </div>
                </div>

                <!-- Course Material -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-file-pdf mr-2"></i>Course Material
                    </label>
                    <div class="file-input-wrapper">
                        <input type="file" name="material" accept=".pdf,.ppt,.pptx,.doc,.docx" class="file-input" id="material">
                        <label for="material" class="file-input-label">
                            <i class="fas fa-file-upload"></i> Upload PDF, PPT, or DOC
                        </label>
                    </div>
                </div>

                <!-- Video Content -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-video mr-2"></i>Video Content
                    </label>
                    <textarea name="link" rows="3" class="form-input" placeholder="Paste YouTube link, external URL, or embed code..." oninput="previewLink(this.value)"></textarea>
                    
                    <div id="linkPreviewBox" class="preview-box" style="display:none;">
                        <h4 class="font-semibold text-gray-800 mb-3">Preview:</h4>
                        <div id="linkPreview" class="bg-gray-100 rounded-lg p-4"></div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="create" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Course
                </button>
            </form>
        </div>
    </div>

    <script>
    function previewImage(event){
        const reader = new FileReader();
        reader.onload = function(){
            const img = document.getElementById('thumbPreview');
            const noPreview = document.getElementById('noPreview');
            img.src = reader.result;
            img.style.display = 'block';
            noPreview.style.display = 'none';
        }
        if(event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    // ✅ Preview link OR iframe
    function previewLink(value){
        const previewBox = document.getElementById('linkPreviewBox');
        const preview = document.getElementById('linkPreview');

        if(value.trim() === ""){
            previewBox.style.display = "none";
            preview.innerHTML = "";
            return;
        }

        // If full iframe code pasted
        if(value.includes("<iframe")){
            preview.innerHTML = value;
            previewBox.style.display = "block";
        } 
        // If YouTube link
        else if(value.includes("youtube.com/watch?v=")){
            const embedUrl = value.replace("watch?v=","embed/");
            preview.innerHTML = `<iframe src="${embedUrl}" style="width:100%;height:315px;border-radius:8px;border:1px solid #ccc;" allowfullscreen></iframe>`;
            previewBox.style.display = "block";
        } 
        // Any other link
        else if(value.startsWith('http')) {
            preview.innerHTML = `<iframe src="${value}" style="width:100%;height:315px;border-radius:8px;border:1px solid #ccc;" allowfullscreen></iframe>`;
            previewBox.style.display = "block";
        } else {
            previewBox.style.display = "none";
        }
    }

    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
        const formInputs = document.querySelectorAll('.form-input');
        
        formInputs.forEach(input => {
            // Add focus effects
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('transform', 'transition', 'duration-200');
            });
            
            // Add validation styling
            input.addEventListener('blur', function() {
                if (this.checkValidity()) {
                    this.style.borderColor = '#10b981';
                } else if (this.value) {
                    this.style.borderColor = '#ef4444';
                }
            });
        });
    });
    </script>

    <?php include 'footer.php'; ?>
</body>