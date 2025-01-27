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
        // Handle multiple file uploads for profile pictures
if (isset($_FILES['profile_pictures'])) {
    $profile_pictures = [];
    $upload_dir = 'uploads/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    foreach ($_FILES['profile_pictures']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['profile_pictures']['error'][$key] == UPLOAD_ERR_OK) {
            $filename = uniqid() . '_' . basename($_FILES['profile_pictures']['name'][$key]);
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                $profile_pictures[] = $upload_path;
            } else {
                $errors[] = "Failed to upload profile picture: " . $_FILES['profile_pictures']['name'][$key];
            }
        }
    }
    
    if (!empty($profile_pictures)) {
        $user->profile_photos = $profile_pictures; // Assuming your database field is profile_photos
    }
}

// Handle about_me field
$user->about_me = $_POST['about_me'] ?? '';

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
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('imagePreviewContainer');
    
    // Store File objects for form submission
    let selectedFiles = new DataTransfer();
    
    imageInput.addEventListener('change', function(e) {
        const files = e.target.files;
        
        for (let file of files) {
            // Only process image files
            if (!file.type.startsWith('image/')) continue;
            
            // Add to FileList
            selectedFiles.items.add(file);
            
            // Create preview elements
            const wrapper = document.createElement('div');
            wrapper.className = 'image-preview-wrapper';
            
            const img = document.createElement('img');
            img.className = 'image-preview';
            
            // Create remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-image';
            removeBtn.innerHTML = '×';
            
            // Read and display image
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // Handle remove button click
            removeBtn.addEventListener('click', function() {
                wrapper.remove();
                
                // Remove file from FileList
                const newFileList = new DataTransfer();
                for (let i = 0; i < selectedFiles.files.length; i++) {
                    if (selectedFiles.files[i] !== file) {
                        newFileList.items.add(selectedFiles.files[i]);
                    }
                }
                selectedFiles = newFileList;
                imageInput.files = selectedFiles.files;
            });
            
            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            previewContainer.appendChild(wrapper);
        }
        
        // Update input's FileList
        imageInput.files = selectedFiles.files;
    });
});
</script>
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

    .image-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.image-preview-wrapper {
    position: relative;
    width: 150px;
    height: 150px;
}

.image-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: red;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    border: none;
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
                <label>Profile Pictures (Multiple)</label>
                <input type="file" name="profile_pictures[]" accept="image/*" multiple id="imageInput">
                <div id="imagePreviewContainer" class="image-preview-container"></div>
                    <small>Maximum file size: 5MB. Allowed types: JPG, PNG, GIF</small>
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
                <label>About Me</label>
                <textarea name="about_me" id="about_me"></textarea>
                <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
                <script>
                    CKEDITOR.replace('about_me');
                </script>
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