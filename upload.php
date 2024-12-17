<?php
// upload.php
// use uploader-validate-multi-user.php or uploader-validate-single-user.php per your requirements
require_once 'uploader-validate-multi-user-roles.php';
require_once 'uploader-config.php';

// debug
function debugLog($message) {
	file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

/**
 * Converts a size in bytes to a human-readable format.
 *
 * @param int $size Size in bytes.
 * @return string Human-readable size (e.g., "5 MB", "1 GB").
 */
function formatFileSize($size) {
	$units = ['B', 'KB', 'MB', 'GB', 'TB'];
	$unitIndex = 0;
	while ($size >= 1024 && $unitIndex < count($units) - 1) {
		$size /= 1024;
		$unitIndex++;
	}
	return round($size, 2) . ' ' . $units[$unitIndex];
}


// paste from clipboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['clipboardData'])) {
		debugLog("Clipboard upload detected.");

		$data = $_POST['clipboardData'];
		if (preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
			$extension = strtolower($matches[1]);
			debugLog("Detected extension: " . $extension);

			if (!in_array("image/$extension", $allowedFileTypes)) {
				debugLog("Invalid image type: image/$extension");
				http_response_code(400);
				echo json_encode(['error' => 'Invalid image type.']);
				exit;
			}

			$data = substr($data, strpos($data, ',') + 1);
			$data = base64_decode($data);
			if ($data === false) {
				debugLog("Base64 decoding failed.");
				http_response_code(400);
				echo json_encode(['error' => 'Invalid base64 data.']);
				exit;
			}

			// Force custom filename for clipboard uploads
			$uniqueName = date('Y.m.d_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
			$destination = $uploadDir . $uniqueName;
			debugLog("Generated unique name: " . $uniqueName);

			if (file_put_contents($destination, $data) === false) {
				debugLog("Failed to save file: " . $destination);
				http_response_code(500);
				echo json_encode(['error' => 'Failed to save image data.']);
				exit;
			}

			debugLog("File saved successfully: " . $destination);

			$fileSize = strlen($data);
			[$width, $height] = getimagesize($destination);
			$dimensions = "{$width}x{$height}";
			debugLog("File dimensions: " . $dimensions);

			// Handle expiration
			$expiry = $_POST['expiry'] ?? 'never';

			$logEntry = [
				'fileName' => $uniqueName,
				'fileSize' => $fileSize,
				'fileDimensions' => $dimensions,
				'uploadDate' => date('Y-m-d H:i:s'),
				'uploaderIP' => $_SERVER['REMOTE_ADDR'],
				'expiry' => $expiry,
			];
			file_put_contents(__DIR__ . $uploadDir . 'log.json', json_encode($logEntry) . PHP_EOL, FILE_APPEND);
			debugLog("Log entry written.");

			// Respond to the frontend
			echo json_encode([
				'fileName' => $uniqueName,
				'fileSize' => round($fileSize / 1024, 2) . ' KB',
				'fileDimensions' => $dimensions,
				'fileUrl' => 'uploads/' . $uniqueName,
			]);
			debugLog("Response sent to frontend.");
			exit;
		} else {
			debugLog("Invalid clipboard data format.");
			http_response_code(400);
			echo json_encode(['error' => 'Invalid clipboard data format.']);
		}
	}

	// Handle regular file uploads
	if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
		http_response_code(400);
		echo json_encode(['error' => 'File upload failed.']);
		exit;
	}

	$file = $_FILES['file'];

	// Validate file size
	if ($file['size'] > $maxFileSize) {
		http_response_code(400);
		echo json_encode(['error' => 'File exceeds maximum size of ' . formatFileSize($maxFileSize) . '.']);
		exit;
	}

	// Validate file type
	if (!in_array($file['type'], $allowedFileTypes)) {
		http_response_code(400);
		echo json_encode(['error' => 'Invalid file type.']);
		exit;
	}

	// Preserve original filename for regular uploads
	$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
	$safeName = pathinfo($file['name'], PATHINFO_FILENAME);
	$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeName);
	$uniqueName = $safeName . '.' . $extension;

	$destination = $uploadDir . $uniqueName;
	$counter = 1;
	while (file_exists($destination)) {
		$uniqueName = $safeName . "_{$counter}." . $extension;
		$destination = $uploadDir . $uniqueName;
		$counter++;
	}

	// Duplicate file check
	function calculateFileHash($filePath) {
		return hash_file('sha256', $filePath); // Calculate hash of the file
	}

	if (!empty($_FILES['file']['tmp_name'])) {
		// Calculate the hash of the uploaded temporary file
		$hash = calculateFileHash($_FILES['file']['tmp_name']);

		// Compare with existing files in the upload directory
		$existingFiles = glob($uploadDir . '*');
		foreach ($existingFiles as $existingFile) {
			if (calculateFileHash($existingFile) === $hash) {
				// If a duplicate is detected, return the file details

				$existingFileName = basename($existingFile); // Extract the filename
				$existingFileUrl = 'uploads/' . $existingFileName; // Create the URL

				// Get file size
				$fileSize = filesize($existingFile); // Size in bytes
				$fileSizeKb = round($fileSize / 1024, 2); // Convert to KB

				// Get file dimensions (for images)
				$dimensions = null;
				if (in_array(mime_content_type($existingFile), ['image/jpeg', 'image/png', 'image/gif'])) {
					[$width, $height] = getimagesize($existingFile);
					$dimensions = "{$width}x{$height}";
				}

				// Respond with duplicate file info
				http_response_code(400);
				echo json_encode([
					'error' => 'Duplicate file detected',
					'existingFileUrl' => $existingFileUrl,
					'fileSize' => "{$fileSizeKb} KB",
					'fileDimensions' => $dimensions ?: 'N/A'
				]);
				exit;
			}
		}
	}

	if (!move_uploaded_file($file['tmp_name'], $destination)) {
		http_response_code(500);
		echo json_encode(['error' => 'Failed to save uploaded file.']);
		exit;
	}

	// Get file dimensions if it's an image
	$dimensions = null;
	if (in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
		[$width, $height] = getimagesize($destination);
		$dimensions = "{$width}x{$height}";
	}

	// Handle file expiry
	$expiryOptions = [
		'never' => null,
		'1h' => strtotime('+1 hour'),
		'12h' => strtotime('+12 hours'),
		'1d' => strtotime('+1 day'),
		'3d' => strtotime('+3 days'),
		'7d' => strtotime('+7 days'),
	];

	$expiry = $_POST['expiry'] ?? 'never';
	$expiryTimestamp = $expiryOptions[$expiry] ?? null;

	// Log file info for admin
	$logEntry = [
		'fileName' => $uniqueName,
		'fileSize' => $file['size'],
		'fileDimensions' => $dimensions,
		'uploadDate' => date('Y-m-d H:i:s'),
		'uploaderIP' => $_SERVER['REMOTE_ADDR'],
		'expiry' => $expiryTimestamp ? date('Y-m-d H:i:s', $expiryTimestamp) : 'Never',
	];
	file_put_contents(__DIR__ . '/uploads/log.json', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

	// Respond to client
	echo json_encode([
		'fileName' => $uniqueName,
		'fileSize' => round($file['size'] / 1024, 2) . ' KB',
		'fileDimensions' => $dimensions,
		'fileUrl' => 'uploads/' . $uniqueName,
	]);
}
?>
