<?php
// uploader-viewer.php
// use uploader-validate-multi-user.php or uploader-validate-single-user.php per your requirements
require_once 'uploader-validate-multi-user-roles.php';
require_once 'uploader-config.php';

// Path to the log.json file
$logFilePath = $uploadDir . 'log.json';

// Check if the log file exists
if (!file_exists($logFilePath)) {
	echo $logFilePath;
	die("Log file not found.");
}

// Read the log file line by line
$logEntries = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$images = [];

// Parse each JSON entry in the log file
foreach ($logEntries as $logEntry) {
	$images[] = json_decode($logEntry, true);
}

// Pagination variables
$perPageOptions = [8, 16, 32, 64, 'all']; // Available per-page options
$perPage = isset($_GET['per_page']) && in_array($_GET['per_page'], $perPageOptions) ? $_GET['per_page'] : 8;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if ($perPage !== 'all') {
	$perPage = intval($perPage); // Convert to an integer
	$totalItems = count($images);
	$totalPages = ceil($totalItems / $perPage);
	$offset = ($currentPage - 1) * $perPage;
	$paginatedFiles = array_slice($images, $offset, $perPage);
} else {
	$paginatedFiles = $images; // Show all items
	$totalPages = 1;
	$currentPage = 1;
}

// pagination function
function renderPagination($currentPage, $totalPages, $perPage, $range = 1) {
	if ($totalPages > 1): ?>
		<div class="pagination">
			<ul>
				<?php
				$start = max(1, $currentPage - $range);
				$end = min($totalPages, $currentPage + $range);

				// "Previous" link
				if ($currentPage > 1): ?>
					<li class="previous-page">
						<a class="btn" href="?page=<?= $currentPage - 1 ?>&per_page=<?= $perPage ?>" class="prev">&laquo; Previous</a>
					</li>
				<?php endif; ?>

				<!-- First page link if needed -->
				<?php if ($start > 1): ?>
					<li><a class="btn" href="?page=1&per_page=<?= $perPage ?>">1</a></li>
					<?php if ($start > 2): ?>
						<li class="ellipsis">...</li>
					<?php endif; ?>
				<?php endif; ?>

				<!-- Display range of pages -->
				<?php for ($i = $start; $i <= $end; $i++): ?>
					<li>
						<a class="btn <?= $i === $currentPage ? 'active' : '' ?>" href="?page=<?= $i ?>&per_page=<?= $perPage ?>">
							<?= $i ?>
						</a>
					</li>
				<?php endfor; ?>

				<!-- Last page link if needed -->
				<?php if ($end < $totalPages): ?>
					<?php if ($end < $totalPages - 1): ?>
						<li class="ellipsis">...</li>
					<?php endif; ?>
					<li><a class="btn" href="?page=<?= $totalPages ?>&per_page=<?= $perPage ?>"><?= $totalPages ?></a></li>
				<?php endif; ?>

				<!-- "Next" link -->
				<?php if ($currentPage < $totalPages): ?>
					<li class="next-page">
						<a class="btn" href="?page=<?= $currentPage + 1 ?>&per_page=<?= $perPage ?>" class="next">Next &raquo;</a>
					</li>
				<?php endif; ?>
			</ul>
		</div>
	<?php endif;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" href="uploader-svgrepo-com-upload.svg">
	<link rel="stylesheet" href="uploader.css">
	<title>Uploaded Files Viewer</title>
	<style>

	</style>
</head>
<body>

<header>
	<h1>Uploaded Files</h1>
	<span class="uname">
		<?php if (isset($_SESSION['username']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
			Logged in as <?= htmlspecialchars($_SESSION['role']) ?>: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
		<?php endif; ?>
	</span>
	<a href="?logout" class="logout-btn">Logout</a>
</header>
<main>
	<!-- Per-Page Selection -->
	<div class="per-page-selector">
		<form method="GET">
			<label for="per_page">Items per page:</label>
			<select name="per_page" id="per_page" onchange="this.form.submit()">
				<?php foreach ($perPageOptions as $option): ?>
					<option value="<?= $option ?>" <?= $option == $perPage ? 'selected' : '' ?>><?= $option ?></option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="page" value="1">
		</form>
	</div>
	<!-- Pagination Controls -->
	<?php renderPagination($currentPage, $totalPages, $perPage); ?>
	<!-- Cards Container -->
	<div class="cards-container">
		<!-- START for each file -->
		<?php foreach ($paginatedFiles as $file):
			$filename = htmlspecialchars($file['fileName']);
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$fileUrl = $uploadUrl . $filename;

			// Determine file type for rendering
			if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])):
		?>
		<!-- Image Card -->
		<div class="image-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-dimensions="<?= htmlspecialchars($file['fileDimensions']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
			<img src="<?= $fileUrl ?>" alt="<?= $filename ?>">
			<div class="card-info">
				<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
				<p><strong>File Size:</strong> <span id="FileSize"><?= isset($file['fileSize']) ? htmlspecialchars($file['fileSize']) : 'N/A'; ?></span></p>
				<p><strong>Dimensions:</strong> <span id="Dimensions"> <?= isset($file['fileDimensions']) ? htmlspecialchars($file['fileDimensions']) : 'N/A'; ?></span></p>
				<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
			</div>
		</div>
			<?php elseif ($extension === 'txt'): ?>
				<!-- Text File Card -->
				<div class="image-card txt-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
					<pre><?= htmlspecialchars(file_get_contents($fileUrl)) ?></pre>
					<div class="card-info">
						<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
						<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
					</div>
				</div>
			<?php elseif ($extension === 'pdf'): ?>
				<!-- PDF File Card -->
				<div class="image-card pdf-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
					<iframe src="<?= $fileUrl ?>#zoom=FitH" width="100%" height="200px"></iframe>
					<div class="card-info">
						<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
						<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
					</div>
				</div>
			<?php elseif (in_array($extension, ['mp4', 'webm', 'mov'])): ?>
				<!-- Video File Card -->
				<div class="image-card video-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
					<video controls width="100%">
						<source src="<?= $fileUrl ?>" type="video/<?= $extension ?>">
						Your browser does not support the video tag.
					</video>
					<div class="card-info">
						<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
						<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
					</div>
				</div>
			<?php elseif (in_array($extension, ['mp3'])): ?>
				<!-- Audio File Card -->
				<div class="image-card audio-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
					<audio controls>
						<source src="<?= $fileUrl ?>" type="audio/mpeg">
						Your browser does not support the audio tag.
					</audio>
					<div class="card-info">
						<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
						<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
					</div>
				</div>
			<?php else: ?>
				<!-- Unsupported File Card -->
				<div class="image-card unsupported-card" data-filename="<?= $filename ?>" data-filesize="<?= htmlspecialchars($file['fileSize']) ?>" data-upload-date="<?= htmlspecialchars($file['uploadDate']) ?>">
					<img src="uploader-svgrepo-com-debug-breakpoint-unsupported.svg" alt="unsupported file type" alt="Unsupported File Type">
					<div class="card-info">
						<p><strong>Filename:</strong> <span id="Filename"><?= $filename ?></span></p>
						<p><strong>Upload Date:</strong> <span id="UploadDate"> <?= isset($file['uploadDate']) ? htmlspecialchars($file['uploadDate']) : 'Unknown'; ?></span></p>
						<p>Preview not available for this file type.</p>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
		<!-- END for each file -->
	</div>
	<!-- end .cards-container -->
	<!-- Pagination Controls -->
	<?php renderPagination($currentPage, $totalPages, $perPage); ?>
</main>
<dialog id="viewerDialog" class="viewer-dialog">
	<div class="controls">
		<img src="uploader-svgrepo-com-arrow-right.svg" alt="Previous Image" title="Previous image" class="previous-img" id="dialogPrevious">
		<img src="uploader-svgrepo-com-close.svg" alt="close dialog" title="Close Dialog" class="close-icon" id="closeDialog">
		<img src="uploader-svgrepo-com-arrow-right.svg" alt="Next Image" title="Next image" class="next-img" id="dialogNext">
	</div>
	<img id="dialogImage" src="" alt="">
	<div id="dynamicContent" class="dynamic-content"></div>
	<div class="details d-none">
		<p><strong>Filename:</strong> <span id="dialogFilename"></span></p>
		<p><strong>File Size:</strong> <span id="dialogFileSize"></span></p>
		<p><strong>Dimensions:</strong> <span id="dialogDimensions"></span></p>
		<p><strong>Upload Date:</strong> <span id="dialogUploadDate"></span></p>
	</div>
	<!-- <button class="close-btn" id="closeDialog">Close</button> -->
</dialog>
<footer>
	<p style="margin:0;text-align:center;">VSXD 2024.12.15
		<?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor'): ?>
			<br><a href="uploader.php">Uploader</a>
		<?php endif; ?>
	</p>
</footer>
<script>
	const imageCards = document.querySelectorAll('.image-card');
	const dialog = document.getElementById('viewerDialog');
	const dialogImage = document.getElementById('dialogImage');
	const dialogFilename = document.getElementById('dialogFilename');
	const dialogFileSize = document.getElementById('dialogFileSize');
	const dialogDimensions = document.getElementById('dialogDimensions');
	const dialogUploadDate = document.getElementById('dialogUploadDate');
	const dialogNext = document.getElementById('dialogNext');
	const dialogPrevious = document.getElementById('dialogPrevious');
	const closeDialog = document.getElementById('closeDialog');
	const ENTER = 13;
	const SPACE = 32;
	const uploadUrl = '<?= $uploadUrl ?>';
	let currentIndex = -1; // Track the currently open image

	// Make image cards focusable for tab navigation
	imageCards.forEach(card => card.setAttribute('tabindex', '0'));

	// Attach click and keyboard event listeners to image cards
	imageCards.forEach((card, index) => {
		// Add click event listener
		card.addEventListener('click', () => {
			currentIndex = index; // Set the current index
			openDialogWithCardData(card);
		});

		// Add keyboard accessibility
		card.addEventListener('keydown', event => {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault(); // Prevent default scrolling for space or enter key
				currentIndex = index; // Set the current index
				openDialogWithCardData(card);
			}
		});
	});

	/**
	 * Opens the dialog and populates it with data from the given image card.
	 * @param {HTMLElement} card The image card element.
	 */
	function openDialogWithCardData(card) {
		// Get data attributes from the card
		const filename = card.getAttribute('data-filename');
		const filesize = card.getAttribute('data-filesize');
		const dimensions = card.getAttribute('data-dimensions');
		const uploadDate = card.getAttribute('data-upload-date');

		// Safely get the file URL and file extension
		const fileUrl = uploadUrl + card.getAttribute('data-filename');
		const fileExtension = filename.split('.').pop().toLowerCase();
		// Debugging
		console.log("File URL:", fileUrl);

		// Clear existing content in the dialog
		dialogImage.style.display = 'none'; // Hide the image element initially
		const dynamicContent = document.getElementById('dynamicContent');
		dynamicContent.innerHTML = ''; // Clear any previously added content

		// Determine how to display the file based on its type
		if (['jpeg', 'jpg', 'png', 'gif'].includes(fileExtension)) {
			dialogImage.src = fileUrl;
			dialogImage.alt = filename;
			dialogImage.style.display = 'block';
		} else if (['txt'].includes(fileExtension)) {
			fetch(fileUrl)
				.then(response => response.text())
				.then(content => {
					const pre = document.createElement('pre');
					pre.textContent = content;
					pre.className = 'txt';
					dynamicContent.appendChild(pre);
				});
		} else if (['pdf'].includes(fileExtension)) {
			const iframe = document.createElement('iframe');
			const fitH = '#view=FitH';
			iframe.src = fileUrl + fitH;
			iframe.style.width = '100%';
			iframe.style.height = '600px';
			dynamicContent.appendChild(iframe);
		} else if (['mp4', 'webm', 'mpeg', 'mov'].includes(fileExtension)) {
			const video = document.createElement('video');
			video.src = fileUrl;
			video.controls = true;
			video.style.width = '100%';
			dynamicContent.appendChild(video);
		} else if (['mp3'].includes(fileExtension)) {
			const audio = document.createElement('audio');
			audio.src = fileUrl;
			audio.controls = true;
			dynamicContent.appendChild(audio);
		} else {
			const dialogImage = document.createElement('img');
			dialogImage.src = 'uploader-svgrepo-com-debug-breakpoint-unsupported.svg';
			dialogImage.alt = 'unsupported file type';
			dialogImage.style.height = '256px'
			dialogImage.style.display = 'block';
			const message = document.createElement('p');
			message.textContent = 'Preview not available for .' + fileExtension + ' files.';
			dynamicContent.appendChild(dialogImage);
			dynamicContent.appendChild(message);
		}

		// Populate the file details
		dialogFilename.textContent = filename;
		dialogFileSize.textContent = filesize;
		dialogDimensions.textContent = dimensions || 'N/A';
		dialogUploadDate.textContent = uploadDate;

		// Show the dialog
		dialog.showModal();
	}

	// Close the dialog when the close button is clicked
	closeDialog.addEventListener('click', () => {
		dialog.close();
	});

	// Close the dialog when clicking outside of it
	dialog.addEventListener('click', event => {
		if (event.target === dialog) {
			dialog.close();
		}
	});

	// focus to close button
	dialog.addEventListener('show', () => {
		closeDialog.focus();
	});

	// return focus to card
	dialog.addEventListener('close', () => {
		const focusedCard = document.querySelector('.image-card:focus');
		if (focusedCard) {
			focusedCard.focus();
		}
	});

	// next/prev activation highlighting funcs
	function nextAct() {
		dialogNext.classList.add("activated");
	};
	function nextDeAct() {
		setTimeout(() => {
			dialogNext.classList.remove("activated");
		}, 125);
	};
	function prevAct() {
		dialogPrevious.classList.add("activated");
	};
	function prevDeAct() {
		setTimeout(() => {
			dialogPrevious.classList.remove("activated");
		}, 125);
	};

	// Navigate to the next or previous image using arrow keys
	dialog.addEventListener('keydown', event => {
		if (event.key === 'ArrowRight') {
			nextAct();
			goToNextImage();
			nextDeAct();
		} else if (event.key === 'ArrowLeft') {
			prevAct();
			goToPreviousImage();
			prevDeAct();
		}
	});

	// Add click event listeners to the navigation buttons
	dialogNext.addEventListener('click', goToNextImage);
	dialogPrevious.addEventListener('click', goToPreviousImage);
	function goToNextImage() {
		nextAct();
		currentIndex = (currentIndex + 1) % imageCards.length; // Wrap around to the start
		openDialogWithCardData(imageCards[currentIndex]);
		nextDeAct();
	}
	function goToPreviousImage() {
		prevAct();
		currentIndex = (currentIndex - 1 + imageCards.length) % imageCards.length; // Wrap around to the end
		openDialogWithCardData(imageCards[currentIndex]);
		prevDeAct();
	}

</script>
</body>
</html>
