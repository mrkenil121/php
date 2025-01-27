<?php
require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

// Check if ID is provided 
if (!isset($_GET['id'])) {
    die("No user ID provided.");
}

$id = intval($_GET['id']);

// Fetch user details
$userData = $user->readById($id);

if (!$userData) {
    die("User not found.");
}

// Normalize interests and skills
$userData['interests'] = str_replace(['{', '}'], '', $userData['interests'] ?? '');
$userData['skills'] = str_replace(['{', '}'], '', $userData['skills'] ?? '');

// var_dump($userData['profile_photos']);
// $userData['profile_photos'] = !empty($userData['profile_photos']) ? array_filter(explode(',', trim($userData['profile_photos'], '{}'))) : [];
// $userData['deleted_profile_pictures'] = !empty($userData['deleted_profile_pictures']) ? array_filter(explode(',', trim($userData['deleted_profile_pictures'], '{}'))) : [];

// Convert to arrays
$userData['interests'] = !empty($userData['interests']) ? explode(',', $userData['interests']) : [];
$userData['skills'] = !empty($userData['skills']) ? explode(',', $userData['skills']) : [];

// Predefined lists
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$skills = [
    'Programming', 'Design', 'Writing', 'Marketing', 
    'Data Analysis', 'Management', 'Sales', 'Customer Service'
];


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->id = $id;
    $user->name = trim($_POST['name']);
    $user->email = trim($_POST['email']);
    $user->phone = trim($_POST['phone']);
    $user->id = $id;
    $user->name = trim($_POST['name']);
    $user->email = trim($_POST['email']);
    $user->phone = trim($_POST['phone']);
    $user->birthdate = $_POST['birthdate'];
    $user->age = $user->calculateAge($user->birthdate);
    $user->gender = $_POST['gender'];
    $user->interests = isset($_POST['interests']) ? $_POST['interests'] : [];
    $user->favorite_color = $_POST['favorite_color'];
    $user->blood_group = $_POST['blood_group'];
    $user->address = trim($_POST['address']);
    $user->skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $user->about_me = $_POST['about_me'];

    // Validation checks
    $errors = [];
    if (!$user->validateName($user->name)) $errors[] = "Invalid name (3-50 letters)";
    if (!$user->validateEmail($user->email)) $errors[] = "Invalid email format";
    if (!$user->validatePhone($user->phone)) $errors[] = "Invalid phone number (10 digits)";
    if (!$user->validateAge($user->age)) $errors[] = "Age must be between 18-100";
    if (!$user->validateBloodGroup($user->blood_group)) $errors[] = "Invalid blood group";
    if (!$user->validateAddress($user->address)) $errors[] = "Invalid address (10-255 characters)";
    if (!$user->validateSkills($user->skills)) $errors[] = "Invalid skills selected";
    if (!$user->validateAboutMe($user->about_me)) $errors[] = "Invalid about me section";

    // Handle deleted images
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_path) {
            if (in_array($image_path, $userData['profile_photos'])) {
                // Remove from profile_photos and add to deleted_profile_pictures
                $userData['deleted_profile_pictures'][] = $image_path;
                $key = array_search($image_path, $userData['profile_photos']);
                if ($key !== false) {
                    unset($userData['profile_photos'][$key]);
                }
            }
        }
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_pictures'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['profile_pictures']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['profile_pictures']['error'][$key] == UPLOAD_ERR_OK) {
                $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['profile_pictures']['name'][$key]));
                $upload_path = $upload_dir . $filename;
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (in_array($_FILES['profile_pictures']['type'][$key], $allowed_types)) {
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $userData['profile_photos'][] = $upload_path;
                    } else {
                        $errors[] = "Failed to upload: " . $_FILES['profile_pictures']['name'][$key];
                    }
                } else {
                    $errors[] = "Invalid file type: " . $_FILES['profile_pictures']['name'][$key];
                }
            }
        }
        $user->profile_photos = array_values($userData['profile_photos']);
        $user->deleted_profile_pictures = array_values($userData['deleted_profile_pictures']);
    }

    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $image_path) {
            if (in_array($image_path, $userData['profile_photos'])) {
                // Remove from profile_photos array
                $key = array_search($image_path, $userData['profile_photos']);
                if ($key !== false) {
                    unset($userData['profile_photos'][$key]);
                    // Add to deleted_profile_pictures array
                    if (!in_array($image_path, $userData['deleted_profile_pictures'])) {
                        $userData['deleted_profile_pictures'][] = $image_path;
                    }
                }
            }
        }
        $user->profile_photos = array_values($userData['profile_photos']);
        $user->deleted_profile_pictures = array_values($userData['deleted_profile_pictures']);
    }


    $user->profile_photos = $userData['profile_photos'];
    $user->deleted_profile_pictures = is_array($userData['deleted_profile_pictures']) ? 
        '{' . implode(',', $userData['deleted_profile_pictures']) . '}' : '{}';

    if (empty($errors)) {
        if ($user->update()) {
            header("Location: index.php?success=1");
            exit();
        } else {
            $updateError = "Failed to update user. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
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

function markImageForDeletion(button, imagePath) {
    // Create hidden input for deleted image
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'delete_images[]';
    input.value = imagePath;
    
    // Check if image exists in profile_photos array
    const existingPhotoInput = button.previousElementSibling;
    if (existingPhotoInput && existingPhotoInput.name === 'existing_photos[]') {
        // Add to deleted images container
        document.getElementById('deletedImagesContainer').appendChild(input);
    }
    
    // Remove the preview
    button.closest('.image-preview-wrapper').remove();
}
</script>
</head>
<body>
    <div class="registration-container">
        <h2>Edit User</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($updateError)): ?>
            <div class="error-message"><?php echo htmlspecialchars($updateError); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required maxlength="50">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>" required pattern="[0-9]{10}">
            </div>

            <div class="form-group">
                <label>Birthdate</label>
                <input type="date" name="birthdate" value="<?php echo $userData['birthdate']; ?>" required>
            </div>

            <div class="form-group">
                <label>Blood Group</label>
                <select name="blood_group" required>
                    <option value="">Select Blood Group</option>
                    <?php foreach ($blood_groups as $group): ?>
                        <option value="<?php echo $group; ?>" 
                            <?php echo ($userData['blood_group'] == $group ? 'selected' : ''); ?>>
                            <?php echo $group; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" required><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <div>
                    <label><input type="radio" name="gender" value="Male" 
                        <?php echo ($userData['gender'] == 'Male' ? 'checked' : ''); ?>> Male</label>
                    <label><input type="radio" name="gender" value="Female" 
                        <?php echo ($userData['gender'] == 'Female' ? 'checked' : ''); ?>> Female</label>
                    <label><input type="radio" name="gender" value="Other" 
                        <?php echo ($userData['gender'] == 'Other' ? 'checked' : ''); ?>> Other</label>
                </div>
            </div>

            <div class="form-group">
                <label>Skills (Multi-select)</label>
                <div class="checkbox-group">
                    <?php foreach ($skills as $skill): ?>
                        <label>
                            <input type="checkbox" name="skills[]" value="<?php echo $skill; ?>"
                                <?php echo (in_array($skill, $userData['skills']) ? 'checked' : ''); ?>>
                            <?php echo $skill; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Interests</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="interests[]" value="Sports" 
                        <?php echo (in_array('Sports', $userData['interests'])) ? 'checked' : ''; ?>> Sports</label>
                    <label><input type="checkbox" name="interests[]" value="Music" 
                        <?php echo (in_array('Music', $userData['interests'])) ? 'checked' : ''; ?>> Music</label>
                    <label><input type="checkbox" name="interests[]" value="Reading" 
                        <?php echo (in_array('Reading', $userData['interests'])) ? 'checked' : ''; ?>> Reading</label>
                    <label><input type="checkbox" name="interests[]" value="Travel" 
                        <?php echo (in_array('Travel', $userData['interests'])) ? 'checked' : ''; ?>> Travel</label>
                </div>
            </div>

            <div class="form-group">
                <label>Favorite Color</label>
                <input type="color" name="favorite_color" 
                    value="<?php echo htmlspecialchars($userData['favorite_color'] ?? '#000000'); ?>">
            </div>

            <div class="form-group">
    <label>Profile Pictures (Multiple)</label>
    <input type="file" name="profile_pictures[]" accept="image/*" multiple id="imageInput">
    <div id="imagePreviewContainer" class="image-preview-container">
        <?php 
        // Check if profile_photos is a string and convert it to array if needed
        if (is_string($userData['profile_photos'])) {
            $userData['profile_photos'] = array_filter(explode(',', trim($userData['profile_photos'], '{}')));
        }
        
        if (!empty($userData['profile_photos'])):
            foreach ($userData['profile_photos'] as $photo): 
                if (!empty($photo)):
        ?>
            <div class="image-preview-wrapper">
                <img src="<?php echo htmlspecialchars(trim($photo)); ?>" class="image-preview" alt="Profile Picture">
                <input type="hidden" name="existing_photos[]" value="<?php echo htmlspecialchars(trim($photo)); ?>">
                <button type="button" class="remove-image" onclick="markImageForDeletion(this, '<?php echo htmlspecialchars(trim($photo)); ?>')">×</button>
            </div>
        <?php 
                endif;
            endforeach;
        endif;
        ?>
    </div>
</div>

            <div class="form-group">
                <label>About Me</label>
                <textarea name="about_me" id="about_me"><?php echo htmlspecialchars($userData['about_me'] ?? ''); ?></textarea>
                <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
                <script>
                    CKEDITOR.replace('about_me');
                </script>
            </div>

            <div id="deletedImagesContainer"></div>

            <button type="submit" class="submit-btn">Update User</button>
        </form>
    </div>
</body>
</html>