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

// Determine which user's dashboard to show
$dashboard_user_id = null;

// Check if an ID is provided in URL and the viewer is an admin
if (isset($_GET['id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Admin viewing another user's dashboard
    $dashboard_user_id = $_GET['id'];

    // Verify the user exists
    $check_user = $user->readById($dashboard_user_id);
    if (!$check_user) {
        header("Location: index.php?error=invalid_user");
        exit();
    }
} else {
    // User viewing their own dashboard
    $query = "SELECT id FROM users WHERE email = $1";
    $result = pg_query_params($db, $query, [$_SESSION['user_email']]);
    $userRow = pg_fetch_assoc($result);
    if (!$userRow) {
        header("Location: logout.php");
        exit();
    }
    $dashboard_user_id = $userRow['id'];
}

// Use readById method to get full user details
$userData = $user->readById($dashboard_user_id);

// Process the arrays from PostgreSQL format
$userData['interests'] = str_replace(['{', '}'], '', $userData['interests'] ?? '');
$userData['skills'] = str_replace(['{', '}'], '', $userData['skills'] ?? '');

// Convert to arrays
$userData['interests'] = !empty($userData['interests']) ? explode(',', $userData['interests']) : [];
$userData['skills'] = !empty($userData['skills']) ? explode(',', $userData['skills']) : [];

// Add a flag to determine if the viewer is the profile owner or an admin
$isProfileOwner = !isset($_GET['id']) || $dashboard_user_id == $_SESSION['user_id'];
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
        }

        .profile-photos-container {
            width: 120px;
            margin-right: 30px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 10px;
        }

        .photo-gallery {
            display: flex;
            flex-direction: row;
            /* explicitly set horizontal direction */
            flex-wrap: nowrap;
            /* prevent wrapping to new lines */
            gap: 10px;
            margin-top: 15px;
            padding-bottom: 10px;
            /* space for the scrollbar */
        }

        .gallery-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .gallery-photo:hover {
            transform: scale(1.1);
        }

        .user-info {
            flex-grow: 1;
        }

        .dashboard-title {
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }

        .about-me-section {
            background-color: #f9f9f9;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }

        .about-me-section h3 {
            color: var(--primary-color);
            margin-top: 0;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
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

        .skills,
        .interests {
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

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
            width: 150px;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #3a7bd5;
        }

        .btn-success {
            background-color: #4CAF50;
        }

        .btn-success:hover {
            background-color: #45a049;
        }

        .btn-danger {
            background-color: #f44336;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="profile-photos-container">
                <?php if (!empty($userData['profile_photos'])): ?>
                    <img src="<?php echo htmlspecialchars($userData['profile_photos'][0]); ?>" alt="Profile Picture"
                        class="profile-photo">

                    <?php if (count($userData['profile_photos']) > 1): ?>
                        <div class="photo-gallery">
                            <?php foreach (array_slice($userData['profile_photos'], 1) as $photo): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile Picture" class="gallery-photo">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                <p>Email: <?php echo htmlspecialchars($userData['email']); ?></p>
            </div>
        </div>

        <?php if (!empty($userData['about_me'])): ?>
            <div class="about-me-section">
                <h3>About Me</h3>
                <div><?php echo $userData['about_me']; ?></div>
            </div>
        <?php endif; ?>

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

        <div class="action-buttons">
            <a href="logout.php" class="btn btn-primary">Logout</a>
            <a href="edit.php?id=<?php echo $userData['id']; ?>" class="btn btn-success">Edit Profile</a>
            <a href="delete.php?id=<?php echo $userData['id']; ?>" class="btn btn-danger"
                onclick="return confirm('Are you sure you want to delete your account?');">Delete Account</a>
        </div>
    </div>
</body>

</html>