<?php
// use uploader-validate-multi-user.php or uploader-validate-single-user.php per your requirements
require_once 'uploader-validate-multi-user-roles.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" href="uploader-svgrepo-com-upload.svg">
	<link rel="stylesheet" href="uploader.css">
	<title>File Uploader</title>
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
		<span class="uname">
			<?php if (isset($_SESSION['username']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
				Logged in as <?= htmlspecialchars($_SESSION['role']) ?>: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
			<?php endif; ?>
		</span>
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
		<button class="upload-zone" id="uploadZone" tabindex="0">
			CTRL+V to paste screenshot, drag & drop files here, or click to upload a file.
			<input type="file" id="fileInput" style="display: none;" multiple>
		</button>
		<div class="fileCards" id="fileList"></div>
	</main>
	<footer>
		<p style="margin:0;text-align:center;">VSXD 2024.12.15 <br><a href="uploader-viewer.php">Viewer</a></p>
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
					fileEmbed.alt = response.fileName;
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
						fileEmbed.alt = fileName;
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