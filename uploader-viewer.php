<?php
// log-viewer.php
// use uploader-validate-multi-user.php or uploader-validate-single-user.php per your requirements
require_once 'uploader-validate-multi-user-roles.php';

// Path to the log.json file
$logFilePath = __DIR__ . '/uploads/log.json';

// Check if the log file exists
if (!file_exists($logFilePath)) {
	die("Log file not found.");
}

// Read the log file line by line
$logEntries = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$images = [];

// Parse each JSON entry in the log file
foreach ($logEntries as $logEntry) {
	$images[] = json_decode($logEntry, true);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" href="upload-svgrepo-com.svg">
	<link rel="stylesheet" href="uploader.css">
	<title>Uploaded Images</title>
	<style>

	</style>
</head>
<body>

<header>
	<h1>Uploaded Images</h1>
	<span class="uname">
		<?php if (isset($_SESSION['username']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true): ?>
			Logged in as <?= htmlspecialchars($_SESSION['role']) ?>: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
		<?php endif; ?>
	</span>
	<a href="?logout" class="logout-btn">Logout</a>
</header>
<main>
	<div class="image-container">
		<?php foreach ($images as $image): ?>
			<div class="image-card" data-filename="<?= htmlspecialchars($image['fileName']) ?>" data-filesize="<?= htmlspecialchars($image['fileSize']) ?>" data-dimensions="<?= htmlspecialchars($image['fileDimensions']) ?>" data-upload-date="<?= htmlspecialchars($image['uploadDate']) ?>">
				<img class="big-image" src="uploads/<?= htmlspecialchars($image['fileName']) ?>" alt="<?= htmlspecialchars($image['fileName']) ?>">
				<div class="image-info">
					<p><strong>Filename:</strong> <span id="Filename"><?= htmlspecialchars($image['fileName']) ?></span></p>
					<p><strong>File Size:</strong> <span id="FileSize"><?= htmlspecialchars($image['fileSize']) ?></span></p>
					<p><strong>Dimensions:</strong> <span id="Dimensions"><?= htmlspecialchars($image['fileDimensions']) ?></span></p>
					<p><strong>Upload Date:</strong> <span id="UploadDate"><?= htmlspecialchars($image['uploadDate']) ?></span></p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</main>
<dialog id="imageDialog" class="image-dialog">
	<div class="controls">
		<img src="uploader-svgrepo-com-arrow-right.svg" alt="Previous Image" title="Previous image" class="previous-img" id="dialogPrevious">
		<img src="uploader-svgrepo-com-close.svg" alt="close dialog" title="Close Dialog" class="close-icon" id="closeDialog">
		<img src="uploader-svgrepo-com-arrow-right.svg" alt="Next Image" title="Next image" class="next-img" id="dialogNext">
	</div>
	<img id="dialogImage" src="" alt="" title="click to enlarge/shrink">
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
	// Select all image cards
	const imageCards = document.querySelectorAll('.image-card');
	const dialog = document.getElementById('imageDialog');
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
		const imageUrl = card.querySelector('img').src;
		const imageAlt = card.querySelector('img').alt;

		// Populate the dialog with image and details
		dialogImage.src = imageUrl;
		dialogImage.alt = imageAlt;
		dialogFilename.textContent = filename;
		dialogFileSize.textContent = filesize;
		dialogDimensions.textContent = dimensions;
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
	// image enbiggening
	dialogImage.addEventListener("click", function() {
		this.classList.toggle("bigger");
	});
	// Navigate to the next or previous image using arrow keys
	dialog.addEventListener('keydown', event => {
	    if (event.key === 'ArrowRight') {
	        goToNextImage();
	    } else if (event.key === 'ArrowLeft') {
	        goToPreviousImage();
	    }
	});
	// Add click event listeners to the navigation buttons
	dialogNext.addEventListener('click', goToNextImage);
	dialogPrevious.addEventListener('click', goToPreviousImage);
	function goToNextImage() {
	    currentIndex = (currentIndex + 1) % imageCards.length; // Wrap around to the start
	    openDialogWithCardData(imageCards[currentIndex]);
	}
	function goToPreviousImage() {
	    currentIndex = (currentIndex - 1 + imageCards.length) % imageCards.length; // Wrap around to the end
	    openDialogWithCardData(imageCards[currentIndex]);
	}


</script>
</body>
</html>
