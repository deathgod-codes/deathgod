<?php
/**
 * File Download API Endpoint
 * Handles secure file downloads with access control
 */

session_start();

// Check authentication
if (!isset($_SESSION['unique_id'])) {
    http_response_code(401);
    die('Not authenticated');
}

// Database connection
require_once __DIR__ . '/../../php/config.php';

$userId = $_SESSION['unique_id'];

// Validate request
if (!isset($_GET['file'])) {
    http_response_code(400);
    die('File parameter missing');
}

$requestedFile = $_GET['file'];

// Security: Prevent directory traversal
if (strpos($requestedFile, '..') !== false || strpos($requestedFile, '/') === 0) {
    http_response_code(403);
    die('Invalid file path');
}

// Construct full file path
$filePath = __DIR__ . '/../../' . $requestedFile;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Check access control: Verify user is part of the conversation
$stmt = $conn->prepare("SELECT m.* FROM messages m 
                        WHERE (m.file_url = ? OR m.thumbnail_url = ?)
                        AND (m.incoming_msg_id = ? OR m.outgoing_msg_id = ?)
                        LIMIT 1");
$stmt->bind_param("ssii", $requestedFile, $requestedFile, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    die('Access denied');
}

$stmt->close();

// Get file info
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

$fileSize = filesize($filePath);
$fileName = basename($filePath);

// Set headers for file download
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Output file
readfile($filePath);
exit;
?>
