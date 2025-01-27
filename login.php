<?php
require_once 'config/database.php';
require_once 'class/User.php';

session_start();

$database = new Database();
$db = $database->conn;
$user = new User($db);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email)) {
        $errors[] = "Email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $auth_result = $user->authenticate($email, $password);
        
        if ($auth_result['success']) {
            // Check if user is active
            if (!($auth_result['is_active'] == 't')) {
                $errors[] = "Your account is currently inactive. Please contact support.";
            } else {
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $auth_result['role'];
                $_SESSION['user_id'] = $auth_result['id'];
                
                // Redirect based on role
                if ($auth_result['role'] === 'admin') {
                    header("Location: index.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        :root {
    --primary-color: #4a90e2;
    --secondary-color: #f4f4f4;
    --text-color: #333;
    --border-color: #ddd;
    --success-bg: #dff0d8;
    --success-text: #3c763d;
    --error-bg: #f2dede;
    --error-text: #a94442;
}

body {
    font-family: 'Arial', 'Helvetica Neue', sans-serif;
    background-color: var(--secondary-color);
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    line-height: 1.6;
}

.login-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 40px;
    width: 100%;
    max-width: 400px;
}

.login-title {
    color: var(--primary-color);
    text-align: center;
    margin-bottom: 30px;
    font-size: 24px;
}

.error-message {
    background-color: var(--error-bg);
    color: var(--error-text);
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
    text-align: center;
}

.login-form {
    display: flex;
    flex-direction: column;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: var(--text-color);
}

.form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.login-btn {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.login-btn:hover {
    background-color: #3a7bd5;
}

.register-link {
    text-align: center;
    margin-top: 15px;
    color: var(--text-color);
}

.register-link a {
    color: var(--primary-color);
    text-decoration: none;
}
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Login</h2>
        
        <?php 
        if (!empty($errors)) {
            echo "<div class='error-message'>";
            foreach ($errors as $error) {
                echo htmlspecialchars($error) . "<br>";
            }
            echo "</div>";
        }
        ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="create.php">Register</a>
        </div>
    </div>
</body>
</html>