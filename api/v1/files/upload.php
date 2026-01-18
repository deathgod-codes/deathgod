<?php
/**
 * File Upload API Endpoint
 * Handles image, video, audio, and document uploads with validation
 */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['unique_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Database connection
require_once __DIR__ . '/../../php/config.php';

// File upload configuration
$config = [
    'max_sizes' => [
        'image' => 10 * 1024 * 1024,    // 10 MB
        'video' => 50 * 1024 * 1024,    // 50 MB
        'audio' => 20 * 1024 * 1024,    // 20 MB
        'document' => 20 * 1024 * 1024  // 20 MB
    ],
    'allowed_types' => [
        'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'],
        'audio' => ['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav', 'audio/webm'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                       'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                       'text/plain', 'application/zip', 'application/x-rar-compressed']
    ],
    'upload_dirs' => [
        'image' => __DIR__ . '/../../uploads/images/',
        'video' => __DIR__ . '/../../uploads/videos/',
        'audio' => __DIR__ . '/../../uploads/audio/',
        'document' => __DIR__ . '/../../uploads/documents/',
        'thumbnail' => __DIR__ . '/../../uploads/thumbnails/'
    ]
];

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$userId = $_SESSION['unique_id'];
$conversationWith = isset($_POST['conversation_with']) ? (int)$_POST['conversation_with'] : 0;

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload error: ' . $file['error']]);
    exit;
}

// Determine file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$fileCategory = null;
foreach ($config['allowed_types'] as $category => $types) {
    if (in_array($mimeType, $types)) {
        $fileCategory = $category;
        break;
    }
}

if (!$fileCategory) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed: ' . $mimeType]);
    exit;
}

// Check file size
if ($file['size'] > $config['max_sizes'][$fileCategory]) {
    $maxSizeMB = $config['max_sizes'][$fileCategory] / (1024 * 1024);
    http_response_code(400);
    echo json_encode(['error' => "File too large. Maximum size for {$fileCategory}: {$maxSizeMB}MB"]);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$uniqueName = time() . '_' . md5($originalName . uniqid()) . '.' . $extension;

// Ensure upload directory exists
$uploadDir = $config['upload_dirs'][$fileCategory];
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create upload directory']);
        exit;
    }
}

// Move uploaded file
$uploadPath = $uploadDir . $uniqueName;
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Generate thumbnail for images
$thumbnailPath = null;
if ($fileCategory === 'image') {
    $thumbnailPath = generateThumbnail($uploadPath, $config['upload_dirs']['thumbnail'] . $uniqueName);
}

// Generate thumbnail for videos (requires FFmpeg)
if ($fileCategory === 'video' && extension_loaded('ffmpeg')) {
    $thumbnailPath = generateVideoThumbnail($uploadPath, $config['upload_dirs']['thumbnail'] . str_replace($extension, 'jpg', $uniqueName));
}

// Get file info
$fileSize = filesize($uploadPath);
$relativePath = str_replace(__DIR__ . '/../../', '', $uploadPath);
$thumbnailRelativePath = $thumbnailPath ? str_replace(__DIR__ . '/../../', '', $thumbnailPath) : null;

// Save file metadata to database (extend messages table or create files table)
$stmt = $conn->prepare("INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, file_url, file_type, file_size, thumbnail_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
$message = "[{$fileCategory}] {$originalName}.{$extension}";
$stmt->bind_param("iisssss", $conversationWith, $userId, $message, $relativePath, $fileCategory, $fileSize, $thumbnailRelativePath);

if ($stmt->execute()) {
    $fileId = $stmt->insert_id;
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'file_id' => $fileId,
        'file_url' => $relativePath,
        'thumbnail_url' => $thumbnailRelativePath,
        'file_type' => $fileCategory,
        'file_size' => $fileSize,
        'original_name' => $file['name']
    ]);
} else {
    // Delete uploaded file if database insert fails
    unlink($uploadPath);
    if ($thumbnailPath) unlink($thumbnailPath);
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file metadata']);
}

/**
 * Generate thumbnail for image
 */
function generateThumbnail($sourcePath, $destinationPath, $maxWidth = 200, $maxHeight = 200) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return null;
    }
    
    $mimeType = $imageInfo['mime'];
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    
    // Create image from source
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    
    if (!$sourceImage) {
        return null;
    }
    
    // Calculate thumbnail dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $thumbWidth = (int)($sourceWidth * $ratio);
    $thumbHeight = (int)($sourceHeight * $ratio);
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);
    
    // Save thumbnail
    imagejpeg($thumbnail, $destinationPath, 85);
    
    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    return $destinationPath;
}

/**
 * Generate thumbnail for video (requires FFmpeg)
 */
function generateVideoThumbnail($videoPath, $thumbnailPath) {
    if (!file_exists('/usr/bin/ffmpeg')) {
        return null;
    }
    
    $command = sprintf(
        'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf scale=200:-1 %s 2>&1',
        escapeshellarg($videoPath),
        escapeshellarg($thumbnailPath)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($thumbnailPath)) {
        return $thumbnailPath;
    }
    
    return null;
}
?>
