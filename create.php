<?php
require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if (empty($_POST['name']) || !$user->validateName($_POST['name'])) {
        $errors[] = "Name must be beween 3 and 50 characters";
    }
    
    if (empty($_POST['email']) || !$user->validateEmail($_POST['email'])) {
        $errors[] = "Please enter a valid email";
    }
    
    if (empty($_POST['phone']) || !$user->validatePhone($_POST['phone'])) {
        $errors[] = "Please enter a valid phone number";
    }
    
    if (empty($_POST['age']) || !$user->validateAge($_POST['age'])) {
        $errors[] = "Age must be between 18 and 100";
    }
    
    if (empty($_POST['password']) || !$user->validatePassword($_POST['password'])) {
        $errors[] = "Password must be at least 8 characters long and contain at least one letter and one number";
    }

    if (empty($errors)) {
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->phone = $_POST['phone'];
        $user->age = $_POST['age'];
        $user->gender = $_POST['gender'];
        $user->password = $_POST['password'];

        if ($user->create()) {
            echo "User created successfully";
        } else {
            echo "Failed to create user";
        }
    } else {
        foreach ($errors as $error) {
            echo $error . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <form method="POST" action="">
        Name: <input type="text" name="name" required><br>
        Email: <input type="email" name="email" required><br>
        Phone: <input type="tel" name="phone" required><br>
        Age: <input type="number" name="age" required><br>
        Gender: 
        <select name="gender">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>