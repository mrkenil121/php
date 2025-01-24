<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

// Fetch user details
$query = "SELECT id FROM users WHERE email = $1";
$result = pg_query_params($db, $query, [$_SESSION['user_email']]);
$userRow = pg_fetch_assoc($result);

// Use readById method to get full user details
$userData = $user->readById($userRow['id']);

$userData['interests'] = str_replace(['{', '}'], '', $userData['interests'] ?? '');
$userData['skills'] = str_replace(['{', '}'], '', $userData['skills'] ?? '');

// Convert to arrays
$userData['interests'] = !empty($userData['interests']) ? explode(',', $userData['interests']) : [];
$userData['skills'] = !empty($userData['skills']) ? explode(',', $userData['skills']) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        :root {
    --primary-color: #4a90e2;
    --secondary-color: #f4f4f4;
    --text-color: #333;
    --border-color: #ddd;
}

body {
    font-family: 'Arial', 'Helvetica Neue', sans-serif;
    background-color: var(--secondary-color);
    margin: 0;
    padding: 20px;
    line-height: 1.6;
}

.dashboard-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px;
    max-width: 800px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 15px;
}

.profile-picture {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 30px;
    border: 4px solid var(--primary-color);
}

.user-info {
    flex-grow: 1;
}

.dashboard-title {
    color: var(--primary-color);
    margin: 0 0 10px 0;
}

.user-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.detail-card {
    background-color: #f9f9f9;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 15px;
}

.detail-card h4 {
    margin: 0 0 10px 0;
    color: var(--primary-color);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 5px;
}

.skills-interests {
    display: flex;
    gap: 15px;
}

.skills, .interests {
    flex: 1;
    background-color: #f9f9f9;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 15px;
}

.badge {
    background-color: var(--primary-color);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
    font-size: 0.8em;
}

.logout-btn {
    display: block;
    width: 200px;
    margin: 20px auto;
    padding: 10px;
    background-color: var(--primary-color);
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.logout-btn:hover {
    background-color: #3a7bd5;
}

.edit-link {
    display: inline-block;
    padding: 8px 15px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s ease;
    margin: 5px;
    font-size: 14px;
}

.edit-link:hover {
    background-color: #45a049;
}

.edit-link.delete {
    background-color: #f44336;
}

.edit-link.delete:hover {
    background-color: #d32f2f;
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <?php if (!empty($userData['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
            <?php endif; ?>
            <div class="user-info">
                <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                <p>Email: <?php echo htmlspecialchars($userData['email']); ?></p>
            </div>
        </div>

        <div class="user-details">
            <div class="detail-card">
                <h4>Personal Information</h4>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($userData['phone']); ?></p>
                <p><strong>Age:</strong> <?php echo $userData['age']; ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($userData['gender']); ?></p>
                <p><strong>Birthdate:</strong> <?php echo htmlspecialchars($userData['birthdate']); ?></p>
                <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($userData['blood_group']); ?></p>
            </div>

            <div class="detail-card">
                <h4>Contact Information</h4>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($userData['address']); ?></p>
                <p><strong>Favorite Color:</strong> <?php echo htmlspecialchars($userData['favorite_color']); ?></p>
            </div>
        </div>

        <div class="skills-interests">
            <div class="skills">
                <h4>Skills</h4>
                <?php if (!empty($userData['skills'])): ?>
                    <?php foreach ($userData['skills'] as $skill): ?>
                        <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No skills listed</p>
                <?php endif; ?>
            </div>

            <div class="interests">
                <h4>Interests</h4>
                <?php if (!empty($userData['interests'])): ?>
                    <?php foreach ($userData['interests'] as $interest): ?>
                        <span class="badge"><?php echo htmlspecialchars($interest); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No interests listed</p>
                <?php endif; ?>
            </div>
        </div>

        <a href="logout.php" class="logout-btn">Logout</a>
        <a href="edit.php?id=<?php echo $userData['id']; ?>" class="edit-link">Edit Profile</a>
        <a href="delete.php?id=<?php echo $userData['id']; ?>" class="edit-link delete" onclick="return confirm('Are you sure you want to delete your account?');">Delete Account</a>
        <a href="index.php" class="edit-link">Back to User List</a>
    </div>
</body>
</html>