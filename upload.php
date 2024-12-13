<?php
// upload.php

// Configuration
$uploadDir = __DIR__ . '/uploads/'; // Ensure this directory is writable
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clipboardData'])) {
        // Handle clipboard image data
        $data = $_POST['clipboardData'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
            $extension = strtolower($matches[1]);
            if (!in_array("image/$extension", $allowedFileTypes)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid image type.']);
                exit;
            }

            $data = substr($data, strpos($data, ',') + 1);
            $data = base64_decode($data);
            if ($data === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid base64 data.']);
                exit;
            }

            // Generate a unique filename
            $uniqueName = date('Y.m.d_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $destination = $uploadDir . $uniqueName;

            if (file_put_contents($destination, $data) === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save image data.']);
                exit;
            }

            $fileSize = strlen($data);
            [$width, $height] = getimagesize($destination);
            $dimensions = "{$width}x{$height}";

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

            $logEntry = [
                'fileName' => $uniqueName,
                'fileSize' => $fileSize,
                'fileDimensions' => $dimensions,
                'uploadDate' => date('Y-m-d H:i:s'),
                'uploaderIP' => $_SERVER['REMOTE_ADDR'],
                'expiry' => $expiryTimestamp ? date('Y-m-d H:i:s', $expiryTimestamp) : 'Never',
            ];
            file_put_contents(__DIR__ . '/uploads/log.json', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

            echo json_encode([
                'fileName' => $uniqueName,
                'fileSize' => round($fileSize / 1024, 2) . ' KB',
                'fileDimensions' => $dimensions,
                'fileUrl' => '/uploads/' . $uniqueName,
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid clipboard data format.']);
        }
        exit;
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
        echo json_encode(['error' => 'File exceeds maximum size of 5 MB.']);
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
