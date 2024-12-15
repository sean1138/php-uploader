<?php
session_start();
require_once 'uploader-creds.php'; // Adjust the path if needed

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
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>File Uploader</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
		}
		dialog {
			border: none;
			border-radius: 10px;
			padding: 20px;
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
			max-width: 300px;
			text-align: center;
		}
		dialog form {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}
		dialog form input {
			padding: 10px;
			font-size: 1rem;
			border: 1px solid #ccc;
			border-radius: 5px;
		}
		dialog form button {
			padding: 10px;
			font-size: 1rem;
			background-color: #007bff;
			color: white;
			border: none;
			border-radius: 5px;
			cursor: pointer;
		}
		dialog form button:hover {
			background-color: #0056b3;
		}
		header {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.logout-btn {
			color: white;
			background-color: red;
			padding: 10px 15px;
			text-decoration: none;
			border-radius: 5px;
		}
		header{
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.upload-zone {
			border: 2px dashed #ccc;
			border-radius: 10px;
			padding: 20px;
			text-align: center;
			color: #888;
			cursor: pointer;
		}
		.upload-zone:focus-visible{
			border-color:orange;
			outline:none;
		}
		.upload-zone.highlight {
			background-color: #e0ffe0;
			border-color: #00a000;
			transition: background-color 0.5s, border-color 0.5s;
		}
		.upload-zone.dragover {
			border-color: #00aaff;
			color: #00aaff;
		}
		#fileList {
			margin-top: 20px;
		}
		.fileCard {
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 5px;
			margin-bottom: 10px;
		}
		.fileCard span{
			margin-left:1em;
		}
		.progress {
			height: 5px;
			background-color: #00aaff;
			margin-top: 5px;
			border-radius: 2px;
		}
		.results{
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.copy-link {
			cursor: pointer;
			color: #007bff;
			text-decoration: underline;
		}
		.preview{
			margin-left:1em;
			width: auto;
			height: auto;
			max-height: 80px;
		}
		.settings {
			margin-bottom: 20px;
		}
		.d-none{
			display:none;
		}
	</style>
</head>
<body>
<?php if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true): ?>
<!-- Login Dialog -->
	<dialog id="loginModal" open>
		<form method="POST" action="">
			<h3>Login</h3>
			<?php if (!empty($error)): ?>
				<p style="color: red;"><?= htmlspecialchars($error) ?></p>
			<?php endif; ?>
			<input type="text" name="username" placeholder="Username" required>
			<input type="password" name="password" placeholder="Password" required>
			<button type="submit">Login</button>
		</form>
	</dialog>
<?php else: ?>
	<!-- File Uploader -->
	<header>
		<h1>File Uploader</h1>
		<a href="?logout" class="logout-btn">Logout</a>
	</header>
	<div class="settings">
		<label for="fileExpiry">File Expiration: </label>
		<select id="fileExpiry">
			<option value="never">Never</option>
			<option value="1h">1 Hour</option>
			<option value="12h">12 Hours</option>
			<option value="1d">1 Day</option>
			<option value="3d">3 Days</option>
			<option value="7d">7 Days</option>
		</select>
	</div>
	<div class="upload-zone" id="uploadZone" tabindex="0">
		CTRL+V to paste screenshot, drag & drop files here, or click to upload a file.
		<input type="file" id="fileInput" style="display: none;" multiple>
	</div>
	<div id="fileList"></div>
	<script>
		const uploadZone = document.getElementById('uploadZone');
		const fileInput = document.getElementById('fileInput');
		const fileList = document.getElementById('fileList');
		const fileExpiry = document.getElementById('fileExpiry');

		const handleFiles = (files) => {
			Array.from(files).forEach(file => uploadFile(file));
		};

		const uploadFile = (file, source) => {
			const fileCard = document.createElement('div');
			fileCard.className = 'fileCard';

			const fileName = document.createElement('p');
			fileName.textContent = `Uploading: ${file.name || 'Clipboard Image'}`;
			fileName.className = 'uploading';
			fileCard.appendChild(fileName);

			const progressBar = document.createElement('div');
			progressBar.className = 'progress';
			fileCard.appendChild(progressBar);

			fileList.prepend(fileCard);

			const formData = new FormData();
			formData.append('expiry', fileExpiry.value);

			if (source === 'clipboard') {
				// Handle clipboard image upload
				const reader = new FileReader();
				reader.onload = (e) => {
					formData.append('clipboardData', e.target.result); // Send as base64
					sendRequest(formData, fileCard, fileName, progressBar);
				};
				reader.readAsDataURL(file);
			} else {
				// Handle regular file upload
				formData.append('file', file);
				sendRequest(formData, fileCard, fileName, progressBar);
			}
		};

		const sendRequest = (formData, fileCard, fileName, progressBar) => {
			const xhr = new XMLHttpRequest();
			xhr.open('POST', 'upload.php', true);

			xhr.upload.addEventListener('progress', (e) => {
				if (e.lengthComputable) {
					const percentComplete = (e.loaded / e.total) * 100;
					progressBar.style.width = `${percentComplete}%`;
				}
			});

			xhr.onload = () => {
				if (xhr.status === 200) {
					const response = JSON.parse(xhr.responseText);

					// fileName.textContent = `Uploaded: ${response.fileName} (${response.fileSize})`;
					// make this P disappear after upload finshes
					fileCard.removeChild(fileName);

					// remove progress bar
					fileCard.removeChild(progressBar);

					const fileLink = document.createElement('p');
					fileLink.className = 'results';
					const link = document.createElement('a');

					// Construct the full URL dynamically
					const fullUrl = `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, '/')}${response.fileUrl}`;
					link.href = fullUrl;
					link.textContent = response.fileName;
					link.target = '_blank';

					const copyLink = document.createElement('span');
					copyLink.textContent = '[Copy Link]';
					copyLink.className = 'copy-link';
					copyLink.onclick = () => navigator.clipboard.writeText(fullUrl);

					const fileSize = document.createElement('span');
					fileSize.textContent = `File Size: ${response.fileSize}`;
					fileSize.className = 'file-size';

					const fileDims = document.createElement('span');
					fileDims.textContent = `File Dimensions: ${response.fileDimensions}`;
					fileDims.className = 'file-dims';

					const fileEmbed = document.createElement('img');
					fileEmbed.src = fullUrl;
					fileEmbed.className = 'preview';

					fileLink.appendChild(link);
					fileLink.appendChild(copyLink);
					fileLink.appendChild(fileSize);
					fileLink.appendChild(fileDims);
					fileLink.appendChild(fileEmbed);

					fileCard.appendChild(fileLink);
				} else {
					const response = JSON.parse(xhr.responseText);

					fileName.textContent = `Error: ${response.error}`;
					progressBar.style.backgroundColor = 'red';

					if (response.existingFileUrl) {
						const fileLink = document.createElement('p');
						const link = document.createElement('a');

						// Construct the full URL dynamically for the duplicate file
						const fullUrl = `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, '/')}${response.existingFileUrl}`;
						link.href = fullUrl;
						link.textContent = response.existingFileUrl;
						link.target = '_blank';

						const copyLink = document.createElement('span');
						copyLink.textContent = '[Copy Link]';
						copyLink.className = 'copy-link';
						copyLink.onclick = () => navigator.clipboard.writeText(fullUrl);

						fileLink.appendChild(link);
						fileLink.appendChild(copyLink);

						fileCard.appendChild(fileLink);
					}
				}
			};

			xhr.onerror = () => {
				fileName.textContent = 'Error uploading file.';
				progressBar.style.backgroundColor = 'red';
			};

			xhr.send(formData);
		};

		// paste from clipboard
		document.addEventListener('DOMContentLoaded', () => {
			const uploadZone = document.getElementById('uploadZone');
			const fileList = document.getElementById('fileList');
			const fileExpiry = document.getElementById('fileExpiry');

			// Listen for paste events globally
			document.addEventListener('paste', async (event) => {
				const items = event.clipboardData.items;
				let foundImage = false;

				for (const item of items) {
					if (item.type.startsWith('image/')) {
						const blob = item.getAsFile();
						console.log("Clipboard image detected:", blob); // Debugging log
						if (blob) {
								foundImage = true;
								uploadFile(blob, 'clipboard');
						}
					}
				}

				if (foundImage) {
					uploadZone.classList.add('highlight');
					setTimeout(() => uploadZone.classList.remove('highlight'), 1000);
				}
			});

			// Drag-and-drop and file input remain unchanged
			uploadZone.addEventListener('dragover', (e) => {
				e.preventDefault();
				uploadZone.classList.add('dragover');
			});

			uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
			uploadZone.addEventListener('drop', (e) => {
				e.preventDefault();
				uploadZone.classList.remove('dragover');
				handleFiles(e.dataTransfer.files);
			});

			const fileInput = document.getElementById('fileInput');
			uploadZone.addEventListener('click', () => fileInput.click());
			fileInput.addEventListener('change', () => handleFiles(fileInput.files));

			const handleFiles = (files) => {
				Array.from(files).forEach(file => uploadFile(file, 'file'));
			};
		});

		// display file dimensions
		function displayFileInfo(fileInfo) {
			const fileCard = document.createElement('div');
			fileCard.className = 'fileCard';

			fileCard.innerHTML = `
					<p><strong>Filename:</strong> ${fileInfo.fileName}</p>
					<p><strong>File Size:</strong> ${fileInfo.fileSize}</p>
					<p><strong>Dimensions:</strong> ${fileInfo.fileDimensions || 'N/A'}</p>
					<p><strong>URL:</strong> <a href="${fileInfo.fileUrl}" target="_blank">${fileInfo.fileUrl}</a></p>
					<p class="copy-link" onclick="navigator.clipboard.writeText('${fileInfo.fileUrl}')">Copy Link</p>
			`;

			fileList.prepend(fileCard);
		}
	</script>
<?php endif; ?>
</body>
</html>