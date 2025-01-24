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
        $errors[] = "Name must be between 3 and 50 characters";
    }
    
    if (empty($_POST['email']) || !$user->validateEmail($_POST['email'])) {
        $errors[] = "Please enter a valid email";
    } elseif ($user->checkEmailExists($_POST['email'])) {
        $errors[] = "Email already exists";
    }
    
    if (empty($_POST['phone']) || !$user->validatePhone($_POST['phone'])) {
        $errors[] = "Please enter a valid phone number";
    }
    
    // Birthdate and age validation
    if (empty($_POST['birthdate'])) {
        $errors[] = "Please select your birthdate";
    } else {
        $birthdate = $_POST['birthdate'];
        $age = $user->calculateAge($birthdate);
        
        if (!$user->validateAge($age)) {
            $errors[] = "Age must be between 18 and 100";
        }
    }
    
    if (empty($_POST['password']) || !$user->validatePassword($_POST['password'])) {
        $errors[] = "Password must be at least 8 characters long and contain at least one letter and one number";
    }

    if (empty($_POST['blood_group'])) {
        $errors[] = "Please select your blood group";
    }
    
    if (empty($_POST['address'])) {
        $errors[] = "Please enter your address";
    }
    
    if (empty($_POST['skills'])) {
        $errors[] = "Please select at least one skill";
    }

    if (empty($errors)) {
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->phone = $_POST['phone'];
        $user->birthdate = $birthdate;
        $user->age = $age;
        $user->gender = $_POST['gender'];
        $user->password = $_POST['password'];
        $user->blood_group = $_POST['blood_group'];
        $user->address = $_POST['address'];
        $user->skills = isset($_POST['skills']) ? $_POST['skills'] : [];
        
        // Store interests
        $user->interests = isset($_POST['interests']) ? $_POST['interests'] : [];
        
        // Store favorite color
        $user->favorite_color = $_POST['favorite_color'] ?? null;

        // Handle file upload for profile picture
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $user->profile_picture = $upload_path;
            } else {
                $errors[] = "Failed to upload profile picture";
            }
        }

        if ($user->create()) {
            header("Location: login.php"); // Redirect to login page
            exit();
        } else {
            $errors[] = "Failed to create user";
        }
    }
}

$blood_groups = [
    'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'
];

// Skills options
$skills = [
    'Programming', 'Design', 'Writing', 'Marketing', 
    'Data Analysis', 'Management', 'Sales', 'Customer Service'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <style>
    :root {
        --primary-color: #4a90e2;
        --secondary-color: #f4f4f4;
        --text-color: #333;
        --border-radius: 8px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f6f8f9 0%, #e5ebee 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        line-height: 1.6;
    }

    .registration-container {
        background: white;
        width: 100%;
        max-width: 550px;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h2 {
        text-align: center;
        color: var(--primary-color);
        margin-bottom: 30px;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 500;
    }

    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
    }

    .form-group .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .form-group .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }

    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary-color);
    }

    .submit-btn {
        width: 100%;
        padding: 15px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 18px;
        font-weight: 600;
        transition: all 0.4s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .submit-btn:hover {
        background-color: #3a7bd5;
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }

    .submit-btn:active {
        transform: translateY(1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .error-message {
        background-color: #ffebee;
        color: #d32f2f;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        text-align: center;
    }

    /* Radio Button Styling */
    .form-group input[type="radio"] {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        accent-color: var(--primary-color);
    }
</style>
</head>
<body>
    <div class="registration-container">
        <h2>Create User</h2>
        <?php 
        if (!empty($errors)) {
            echo "<div class='error-message'>";
            foreach ($errors as $error) {
                echo $error . "<br>";
            }
            echo "</div>";
        }
        ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" required>
            </div>
            
            <div class="form-group">
                <label>Birthdate</label>
                <input type="date" name="birthdate" required>
            </div>
            
            <div class="form-group">
                <label>Blood Group</label>
                <select name="blood_group" required>
                    <option value="">Select Blood Group</option>
                    <?php foreach ($blood_groups as $group): ?>
                        <option value="<?php echo $group; ?>"><?php echo $group; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Gender</label>
                <div>
                    <label><input type="radio" name="gender" value="Male" required> Male</label>
                    <label><input type="radio" name="gender" value="Female" required> Female</label>
                    <label><input type="radio" name="gender" value="Other" required> Other</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Profile Picture</label>
                <input type="file" name="profile_picture" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>Favorite Color</label>
                <input type="color" name="favorite_color">
            </div>
            
            <div class="form-group">
                <label>Skills (Multi-select)</label>
                <div class="checkbox-group">
                    <?php foreach ($skills as $skill): ?>
                        <label>
                            <input type="checkbox" name="skills[]" value="<?php echo $skill; ?>">
                            <?php echo $skill; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Interests</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="interests[]" value="Sports"> Sports</label>
                    <label><input type="checkbox" name="interests[]" value="Music"> Music</label>
                    <label><input type="checkbox" name="interests[]" value="Reading"> Reading</label>
                    <label><input type="checkbox" name="interests[]" value="Travel"> Travel</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="submit-btn">Create User</button>
        </form>
    </div>
</body>
</html>