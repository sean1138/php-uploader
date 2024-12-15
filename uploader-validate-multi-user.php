<?php
session_start();
require_once 'uploader-creds-multi-user.php'; // Adjust the path if needed

// Check if the user is already authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Check if the username exists in the credentials array and the password matches
        if (isset($credentials[$username]) && $credentials[$username] === $password) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username; // Store the logged-in user's username in the session
            header('Location: uploader.php'); // Reload the page after successful login
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: uploader.php'); // Reload the page after logout
    exit;
}
?>
