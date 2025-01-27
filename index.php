<?php
require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

session_start();

// Enhanced admin check - verify both login status and admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Handle AJAX request for status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    header('Content-Type: application/json');
    
    $id = $_POST['id'];
    $status = $_POST['status'] === 'true';
    
    try {
        if ($user->updateStatus($id, $status)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error occurred while updating status']);
    }
    exit();
}

// Fetch users with error handling
$result = $user->read();
if (!$result) {
    // Handle database error
    $error_message = "Failed to fetch users";
}
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

        .status-active {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .status-inactive {
            background-color: #f44336;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
        }

        select.status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background-color: white;
            cursor: pointer;
        }

        .nav-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .nav-btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
        }

        .dashboard-btn {
            background-color: var(--primary-color);
        }

        .dashboard-btn:hover {
            background-color: #3a7bd5;
        }

        .logout-btn {
            background-color: #f44336;
        }

        .logout-btn:hover {
            background-color: #d32f2f;
        }

    </style>
</head>
<body>
    <div class="container">
    <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn dashboard-btn">Dashboard</a>
            <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
        <h2>User List</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success-message">
                <?php
                switch($_GET['success']) {
                    case 'deleted':
                        echo 'User successfully deleted.';
                        break;
                    case 'status_updated':
                        echo 'User status successfully updated.';
                        break;
                    case '1':
                        echo 'User successfully updated.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message error-message">
                <?php
                switch($_GET['error']) {
                    case 'delete_failed':
                        echo 'Failed to delete user.';
                        break;
                    case 'status_update_failed':
                        echo 'Failed to update user status.';
                        break;
                }
                ?>
            </div>
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
                    $interests = !empty($row['interests']) ? explode(',', $row['interests']) : [];
                    $skills = !empty($row['skills']) ? explode(',', $row['skills']) : [];
                    $isActive = $row['is_active'] === 't';
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
                        <a href="#" 
                           onclick="event.preventDefault(); toggleUserStatus(<?php echo $row['id']; ?>, <?php echo $isActive ? 'true' : 'false'; ?>);" 
                           class="delete">
                            <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="create.php" class="create-btn">Create New User</a>
    </div>

    
    <script>
        function toggleUserStatus(id, currentStatus) {
    const newStatus = !currentStatus;
    const button = event.target;

    // Disable the button during the request
    button.disabled = true;

    // Prepare data for the request
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);

    // Make an AJAX request using Fetch API
    fetch('index.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button text
                button.textContent = newStatus ? 'Deactivate' : 'Activate';

                // Show success message
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message success-message';
                messageDiv.textContent = 'User status updated successfully';
                document.querySelector('.container').insertBefore(messageDiv, document.querySelector('table'));

                // Remove message after 3 seconds
                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            let errorMessage = 'Error updating status';
            if (error.message) {
                errorMessage += `: ${error.message}`;
            }
            alert(errorMessage);
        })
        .finally(() => {
            // Re-enable the button
            button.disabled = false;
        });
}
    
</script>
</body>
</html>