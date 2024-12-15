<?php
session_start();
require_once 'uploader-creds-single-user.php'; // Adjust the path if needed

// Check if the user is already authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
		// Handle login form submission
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
				if ($_POST['username'] === UPLOAD_USERNAME && $_POST['password'] === UPLOAD_PASSWORD) {
						$_SESSION['authenticated'] = true;
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