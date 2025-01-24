<?php
require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

// Handle delete request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);
    if ($user->delete($deleteId)) {
        header("Location: index.php?success=deleted");
        exit();
    } else {
        header("Location: index.php?error=delete_failed");
        exit();
    }
}

$result = $user->read();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User List</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f4f4f4;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .error-message {
            background-color: #f2dede;
            color: #a94442;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .actions {
            display: flex;
            justify-content: space-around;
        }

        .actions a {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .actions a.edit {
            background-color: #4CAF50;
            color: white;
        }

        .actions a.delete {
            background-color: #f44336;
            color: white;
        }

        .create-btn {
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

        .create-btn:hover {
            background-color: #3a7bd5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User List</h2>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
            <div class="message success-message">User successfully deleted.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'delete_failed'): ?>
            <div class="message error-message">Failed to delete user.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="message success-message">User successfully updated.</div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Blood Group</th>
                    <th>Gender</th>
                    <th>Interests</th>
                    <th>Skills</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                while($row = pg_fetch_assoc($result)): 
                    // Handle interests and skills
                    $interests = !empty($row['interests']) ? explode(',', $row['interests']) : [];
                    $skills = !empty($row['skills']) ? explode(',', $row['skills']) : [];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo !empty($interests) ? htmlspecialchars(implode(', ', $interests)) : 'None'; ?></td>
                    <td><?php echo !empty($skills) ? htmlspecialchars(implode(', ', $skills)) : 'None'; ?></td>
                    <td class="actions">
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="edit">Edit</a>
                        <a href="index.php?action=delete&id=<?php echo $row['id']; ?>" 
                           class="delete"
                           onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="create.php" class="create-btn">Create New User</a>
    </div>
</body>
</html>