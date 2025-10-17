<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Create Account</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: url('https://wallpaperaccess.com/full/6367119.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #333;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 28px;
        }

        .form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        input, select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
        }

        button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .small {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }

        .small a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .small a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border: 1px solid #2ecc71;
        }

        .error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border: 1px solid #e74c3c;
        }

        @media (max-width: 480px) {
            .card {
                padding: 25px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body style="background: url('https://wallpaperaccess.com/full/6367119.jpg') no-repeat center center fixed; background-size: cover; margin: 0;">
   
    <?php include 'header.php'; include 'config.php'; ?>
    
    <?php
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role = $_POST['role'];
        $sql = "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt->execute()) {
            echo '<div class="message success">✅ Registration successful. <a href="login.php" style="color: #27ae60">Login</a></div>';
        } else {
            echo '<div class="message error">❌ Error: '.htmlspecialchars($conn->error).'</div>';
        }
    }
    ?>
    
    <div class="container">
        <div class="card">
            <h2 style=" background-color: black;">Create an account</h2>
            <form method="post" class="form">

<label style="color:black">Name</label>
<input type="text" name="name" placeholder="Full Name" required style=" background-color: #3CBC8D;" >
<label style="color:black">Email</label>
<input type="email" name="email" placeholder="Email" required style=" background-color: #3CBC8D;">
<label style="color:black">Password</label>
<input type="password" name="password" placeholder="Password" required style=" background-color: #3CBC8D;">
<label style="color:black">Role</label>
<select name="role" required style=" background-color: #3CBC8D;">
    
    <option value="student">Student</option>
</select>
<button type="submit" name="register" style=" background-color: black;">Create account</button>
</form>
            <p class="small" style=" background-color:black;">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
    
  
</body>
</html>