<?php
$uploadDir = __DIR__ . '/uploads/'; // Local file system path, ensure this directory is writable
// 1 = S:/site//uploads/2024_12_15_031339.jpg - absolute file system path
// $uploadUrl = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";

// 2 = JS error: Uncaught ReferenceError: $uploadUrl is not defined
$uploadUrl = 'uploads/'; // Webserver path (relative to the document root)

// 3 http://localhost/uploads/2024_12_15_031339.jpg
// 3 does NOT include the current subdir we have the files in = absolute path
// $uploadUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/uploads/'; // Absolute web path

// 4 Dynamically build the relative web path to the uploads folder
// 4 http://localhostS:/php-uploader/uploads/2024_12_14_185058.jpg
// $relativePath = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__));
// $uploadUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
//            . $_SERVER['HTTP_HOST']
//            . $relativePath
//            . '/uploads/';

// 5 http://localhostS:/php-uploader/uploads/2024_12_14_185058.jpg - same as above
// Build the relative web path
// $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', __DIR__));

// // Dynamically generate the absolute web URL
// $uploadUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
//            . $_SERVER['HTTP_HOST']
//            . $relativePath
//            . '/uploads/';

$maxFileSize = 1 * 1024 * 1024; // 1 MB
$allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif', 'text/plain', 'application/pdf', 'video/mp4', 'video/quicktime', 'video/webm', 'video/mpeg', 'audio/mpeg', 'image/svg+xml'];
?>

