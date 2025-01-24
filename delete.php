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

// Attempt to delete user
if ($user->delete($id)) {
    header("Location: index.php");
    exit();
} else {
    // If deletion fails, show error
    die("Failed to delete user.");
}
?>