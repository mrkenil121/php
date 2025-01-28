<?php
require_once 'config/database.php';
require_once 'class/User.php';

$database = new Database();
$db = $database->conn;
$user = new User($db);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    header('Content-Type: application/json');
    
    $searchTerm = $_GET['search'];
    $result = $user->search($searchTerm);
    
    if ($result) {
        $users = [];
        while ($row = pg_fetch_assoc($result)) {
            // Process arrays
            $interests = !empty($row['interests']) ? explode(',', $row['interests']) : [];
            $skills = !empty($row['skills']) ? explode(',', $row['skills']) : [];
            
            $row['interests'] = $interests;
            $row['skills'] = $skills;
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Search failed']);
    }
    exit();
}

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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
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

        tbody tr {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #edf2f7 !important;
        }

        .actions {
            cursor: default;
        }

        /* Prevent row click when clicking on actions */
        .actions a {
            position: relative;
            z-index: 2;
        }

        /* Add visual feedback for clickable rows */
        tbody tr td:first-child::before {
            content: '';
            position: absolute;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        tbody tr:hover td:first-child::before {
            opacity: 1;
        }

        /* Style to indicate clickable rows */
        .click-hint {
            display: block;
            text-align: center;
            color: #666;
            margin-bottom: 10px;
            font-style: italic;
        }

        /* Status toggle button base styles */
        .status-toggle {
            display: inline-block;
            width: 100px;
            /* Fixed width */
            padding: 6px 0;
            /* Vertical padding only since width is fixed */
            text-align: center;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        /* Deactivate button style (for active users) */
        .status-toggle-deactivate {
            background-color: #dc3545;
            /* Red color */
            color: white;
            border: 1px solid #dc3545;
        }

        .status-toggle-deactivate:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        /* Activate button style (for inactive users) */
        .status-toggle-activate {
            background-color: #28a745;
            /* Green color */
            color: white;
            border: 1px solid #28a745;
        }

        .status-toggle-activate:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        /* Disabled state */
        .status-toggle:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .search-container {
    margin-bottom: 20px;
    text-align: center;
}

.search-input {
    width: 300px;
    padding: 10px;
    border: 2px solid var(--primary-color);
    border-radius: 4px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
    width: 320px;
}

.highlight {
    background-color: #fff3cd;
    padding: 2px;
    border-radius: 2px;
}
    </style>
</head>

<body>
    <div class="container">
        <div class="nav-buttons">
            <a href="dashboard.php" class="nav-btn dashboard-btn">Dashboard</a>
            <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
        <div class="search-container">
    <input type="text" id="searchInput" placeholder="Search users..." class="search-input">
</div>
        <h2>User List</h2>

        <span class="click-hint">Click on any row to view user's dashboard</span>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success-message">
                <?php
                switch ($_GET['success']) {
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
                switch ($_GET['error']) {
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
                while ($row = pg_fetch_assoc($result)):
                    $interests = !empty($row['interests']) ? explode(',', $row['interests']) : [];
                    $skills = !empty($row['skills']) ? explode(',', $row['skills']) : [];
                    $isActive = $row['is_active'] === 't';
                    ?>
                    <tr onclick="redirectToDashboard(<?php echo $row['id']; ?>, event)">
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                        <td><?php echo !empty($interests) ? htmlspecialchars(implode(', ', $interests)) : 'None'; ?></td>
                        <td><?php echo !empty($skills) ? htmlspecialchars(implode(', ', $skills)) : 'None'; ?></td>
                        <td class="actions" onclick="event.stopPropagation();">
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="edit">Edit</a>
                            <a href="#"
                                onclick="event.preventDefault(); toggleUserStatus(<?php echo $row['id']; ?>, <?php echo $isActive ? 'true' : 'false'; ?>);"
                                class="status-toggle <?php echo $isActive ? 'status-toggle-deactivate' : 'status-toggle-activate'; ?>">
                                <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="create.php" class="create-btn">Create New User</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function redirectToDashboard(userId, event) {
            // Check if the click was on an action button or its container
            if (!event.target.closest('.actions')) {
                window.location.href = `dashboard.php?id=${userId}`;
            }
        }

        function toggleUserStatus(id, currentStatus) {
            const button = event.target;
            const newStatus = !currentStatus;

            // Disable the button during the request
            $(button).prop('disabled', true);

            // Prepare data for the request
            const data = {
                id: id,
                status: newStatus
            };

            // Make AJAX request
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Update button text
                        $(button).text(newStatus ? 'Deactivate' : 'Activate');

                        // Update button classes
                        $(button).removeClass(newStatus ? 'status-toggle-activate' : 'status-toggle-deactivate')
                            .addClass(newStatus ? 'status-toggle-deactivate' : 'status-toggle-activate');

                        // Update the onclick handler
                        $(button).off('click').on('click', function (e) {
                            e.preventDefault();
                            toggleUserStatus(id, newStatus);
                        });

                        // Remove existing message if any
                        $('.message').remove();

                        // Show success message
                        const messageDiv = $('<div>', {
                            class: 'message success-message',
                            text: 'User status updated successfully'
                        });

                        $('.container').find('table').before(messageDiv);

                        // Remove message after 3 seconds
                        setTimeout(function () {
                            messageDiv.fadeOut('slow', function () {
                                $(this).remove();
                            });
                        }, 3000);
                    } else {
                        throw new Error(response.message || 'Failed to update status');
                    }
                },
                error: function (xhr, status, error) {
                    // Remove existing message if any
                    $('.message').remove();

                    // Show error message
                    const messageDiv = $('<div>', {
                        class: 'message error-message',
                        text: error || 'Error updating status'
                    });

                    $('.container').find('table').before(messageDiv);

                    // Remove message after 3 seconds
                    setTimeout(function () {
                        messageDiv.fadeOut('slow', function () {
                            $(this).remove();
                        });
                    }, 3000);
                },
                complete: function () {
                    // Re-enable the button
                    $(button).prop('disabled', false);
                }
            });
        }

        let searchTimeout;
const searchInput = document.getElementById('searchInput');

searchInput.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    
    // Add loading indicator to input
    this.style.backgroundColor = '#f8f9fa';
    
    searchTimeout = setTimeout(() => {
        const searchTerm = e.target.value.trim();
        
        if (searchTerm.length > 0) {
            fetchSearchResults(searchTerm);
        } else {
            // If search is empty, fetch all users
            fetchSearchResults('');
        }
    }, 300); // Debounce for 300ms
});

function fetchSearchResults(searchTerm) {
    $.ajax({
        url: 'index.php',
        type: 'GET',
        data: { search: searchTerm },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateTable(response.users, searchTerm);
            } else {
                console.error('Search failed:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
        },
        complete: function() {
            // Remove loading indicator
            searchInput.style.backgroundColor = '';
        }
    });
}

function updateTable(users, searchTerm) {
    const tbody = document.querySelector('tbody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.setAttribute('onclick', `redirectToDashboard(${user.id}, event)`);
        
        // Highlight matching text if there's a search term
        const highlightText = (text, term) => {
            if (!term) return text;
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        };
        
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${highlightText(user.name, searchTerm)}</td>
            <td>${highlightText(user.email, searchTerm)}</td>
            <td>${highlightText(user.phone, searchTerm)}</td>
            <td>${highlightText(user.blood_group || 'N/A', searchTerm)}</td>
            <td>${highlightText(user.gender, searchTerm)}</td>
            <td>${user.interests ? highlightText(user.interests.join(', '), searchTerm) : 'None'}</td>
            <td>${user.skills ? highlightText(user.skills.join(', '), searchTerm) : 'None'}</td>
            <td class="actions" onclick="event.stopPropagation();">
                <a href="edit.php?id=${user.id}" class="edit">Edit</a>
                <a href="#" 
                   onclick="event.preventDefault(); toggleUserStatus(${user.id}, ${user.is_active === 't'});"
                   class="status-toggle ${user.is_active === 't' ? 'status-toggle-deactivate' : 'status-toggle-activate'}">
                    ${user.is_active === 't' ? 'Deactivate' : 'Activate'}
                </a>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}
    </script>
</body>

</html>