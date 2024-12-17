<?php
// the folder name below (uploads) should be the same in both places!  the leading / is important to have and not have!
$uploadDir = __DIR__ . '/uploads/'; // Local file system path, ensure this directory is writable
$uploadUrl = 'uploads/'; // Webserver path (relative to the document root)
$maxFileSize = 1 * 1024 * 1024; // 1 MB
$allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif', 'text/plain', 'application/pdf', 'video/mp4', 'video/quicktime', 'video/webm', 'video/mpeg', 'audio/mpeg', 'image/svg+xml'];
?>

