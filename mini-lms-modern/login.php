<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
 <body style="background: url('https://wallpaperaccess.com/full/6367119.jpg') no-repeat center center fixed; background-size: cover; margin: 0;">
   
<?php
session_start();
include 'config.php';
include 'header.php';
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        if ($user['role'] == 'teacher') {
            header("Location: teacher_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit;
    } else {
        echo "❌ Invalid login details!";
    }
}
?>

<h2 style="color:black">Login</h2>
<form method="post">
    <input type="email" name="email" placeholder="Email" required style=" background-color: #3CBC8D;"><br>
    <input type="password" name="password" placeholder="Password" required style=" background-color: #3CBC8D;"><br>
    <button type="submit" name="login">Login</button>
</form>
</body>
</html>
