<?php
session_start();
require_once 'uploader-creds-multi-user-roles.php'; // Adjust the path if needed

// Check if login form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
	$username = $_POST['username']; // Get username from the login form
	$password = $_POST['password']; // Get password from the login form

	// Validate username
	if (!isset($credentials[$username])) {
		$error = "The username you entered does not exist.";
	} else {
		// Validate password
		if ($credentials[$username]['password'] !== $password) {
			$error = "The password you entered is incorrect.";
		} else {
			// If both are correct, log the user in
			$_SESSION['authenticated'] = true;
			$_SESSION['username'] = $username;
			$_SESSION['role'] = $credentials[$username]['role']; // Store the user's role in the session
			header('Location: uploader.php'); // Redirect to uploader
			exit;
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
