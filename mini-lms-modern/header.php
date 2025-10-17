<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mini LMS</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body style="background-color:black;">
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="login.php"><img src="https://tse3.mm.bing.net/th/id/OIP.a5vynxv8k91XF19mIIYOyAHaCQ?pid=Api&P=0&h=180" alt="" style="hight: 100px;width: 210px;"></a>
    <nav class="nav">
      <?php if(isset($_SESSION['user'])): ?>
        <a href="<?php echo ($_SESSION['user']['role']=='teacher') ? 'teacher_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-primary" style="color:black">Dashboard</a>
        <a href="about_us.php" class="btn btn-primary" style="color:black">About-us</a>
        <a href="logout.php" class="btn btn-primary" style="color:black">Logout</a>
       
      <?php else: ?>
        <a href="login.php" class="btn btn-primary" style="color:black">Login</a>
        <a href="register.php" class="btn btn-primary" style="color:black">Register</a>
        <a href="logout.php" class="btn btn-primary" style="color:black">About-us</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container main">