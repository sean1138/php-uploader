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
			margin: 0;
			/* remove empty space below footer on short content pages 1/2 */
			display: flex;
			flex-direction: column;
			min-height: 100vh;
			/* blue */
			--color1: hsl(200 100% 32%);
			--color1light: hsl(200 100% 94%);
			/* green */
			--color2: hsl(120 100% 32%);
			--color2light: hsl(120 100% 94%);
			/* orange */
			--color3: hsl(30 100% 32%);
			--color3light: hsl(30 100% 94%);
			--white: hsl(0 0% 94%);
			--black: hsl(0 0% 32%);
			--grey: hsl(30 0% 50%);
			background: var(--white);
			color: var(--black);
			--borderRad: .25rem;
		}
		header,
		main,
		footer {
			padding: 1rem;
		}
		footer {
			/* remove empty space below footer on short content pages 2/2 */
			margin-top: auto;
		}
		dialog {
			place-self: anchor-center;
			border: none;
			border-radius: var(--borderRad);
			padding: 2rem;
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
			padding: .5em;
			font-size: 1rem;
			border: 1px solid var(--grey);
			border-radius: var(--borderRad);
		}
		dialog form button {
			padding: .5em 1em;
			font-size: 1rem;
			background-color: var(--color1light);
			border:1px solid var(--color1);
			border-radius: var(--borderRad);
			text-decoration: none;
			color: var(--color1);
			transition: all 0.5s;
			cursor: pointer;
		}
		dialog form button:hover {
			background-color: var(--color1);
			color: var(--white);
		}
		header {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.logout-btn {
			padding: .5em 1em;
			background-color: var(--color1light);
			border:1px solid var(--color1);
			border-radius: var(--borderRad);
			text-decoration: none;
			color: var(--color1);
			transition: all 0.5s;
		}
		.logout-btn:hover{
			background: var(--color1);
			color: var(--white);
		}
		header{
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.upload-zone {
			border: 2px dashed var(--black);
			border-radius: var(--borderRad);
			padding: 2rem;
			text-align: center;
			color: var(--black);
			cursor: pointer;
			transition: all 0.5s;
		}
		.upload-zone:hover{
			border-color: var(--white);
			color: var(--white);
			background: var(--black);
		}
		.upload-zone:focus-visible{
			background-color: var(--color3light);
			border-color: var(--color3);
			outline:none;
		}
		.upload-zone.highlight {
			background-color: var(--color3light);
			border-color: var(--color3);
			color: var(--color3);
		}
		.upload-zone.dragover {
			background-color: var(--color2light);
			border-color: var(--color2);
		}
		#fileList {
			margin-top: 2rem;
		}
		.fileCard {
			padding: 1rem;
			border: 1px solid var(--grey);
			border-radius: var(--borderRad);
			margin-bottom: 1rem;
		}
		.fileCard span{
			margin-left:1em;
		}
		.progress {
			height: .25rem;
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
			border: 1px solid var(--grey);
		}
		.settings {
			margin-bottom: 2rem;
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
	<main>
		<!-- this functionality would require cron job and further extensive testing but don't want to strip out everything the relies on it -->
		<div class="settings d-none">
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
	</main>
	<footer>
		<p style="margin:0;text-align:center;">VSXD 2024.12.15</p>
	</footer>
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
					uploadZone.classList.add('highlight');
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
					uploadZone.classList.remove('highlight');

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
					// duplicate file detected
					const response = JSON.parse(xhr.responseText);

					fileName.textContent = `Error: ${response.error}`;
					progressBar.style.backgroundColor = 'red';

					if (response.existingFileUrl) {
						uploadZone.classList.remove('highlight');
						const fileLink = document.createElement('p');
						fileLink.className = 'results';
						const link = document.createElement('a');

						// Extract the file name from the existingFileUrl
						const fileName = response.existingFileUrl.split('/').pop(); // Get the last part of the URL (the file name)

						// Construct the full URL dynamically for the duplicate file
						const fullUrl = `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, '/')}${response.existingFileUrl}`;
						link.href = fullUrl;
						link.textContent = fileName; // Display just the file name
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
					// setTimeout(() => uploadZone.classList.remove('highlight'), 1000);
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