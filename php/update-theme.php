<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['unique_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['theme'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['unique_id'];
$theme = $_POST['theme'];

// Validate theme value
if (!in_array($theme, ['light', 'dark', 'auto'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid theme value']);
    exit;
}

// Check if theme_preference column exists, if not, it will fail gracefully
$stmt = $conn->prepare("UPDATE users SET theme_preference = ? WHERE unique_id = ?");
if ($stmt) {
    $stmt->bind_param("si", $theme, $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'theme' => $theme]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update theme preference']);
    }
    
    $stmt->close();
} else {
    // Column might not exist yet - return success anyway
    echo json_encode(['success' => true, 'theme' => $theme, 'note' => 'Database column not available']);
}
?>
