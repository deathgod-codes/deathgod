<?php
/**
 * Message Edit API Endpoint
 * Allows users to edit their own messages
 */
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['unique_id'])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

include_once "../../../php/config.php";
include_once "../../../php/Security.php";

$security = new Security($conn);

// Validate session
if (!$security->validateSession()) {
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['msg_id']) || !isset($input['new_message'])){
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$msg_id = intval($input['msg_id']);
$new_message = trim($input['new_message']);
$user_id = $_SESSION['unique_id'];

if(empty($new_message)){
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit();
}

// Verify the message belongs to the user
$stmt = $conn->prepare("SELECT msg_id FROM messages WHERE msg_id = ? AND outgoing_msg_id = ?");
$stmt->bind_param("ii", $msg_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo json_encode(['success' => false, 'error' => 'Message not found or unauthorized']);
    $stmt->close();
    exit();
}
$stmt->close();

// Update the message
$sanitized_message = htmlspecialchars($new_message, ENT_QUOTES, 'UTF-8');
$update_stmt = $conn->prepare("UPDATE messages SET msg = ?, is_edited = 1, updated_at = NOW() WHERE msg_id = ?");
$update_stmt->bind_param("si", $sanitized_message, $msg_id);

if($update_stmt->execute()){
    echo json_encode([
        'success' => true, 
        'message' => 'Message updated successfully',
        'updated_message' => $sanitized_message
    ]);
}else{
    echo json_encode(['success' => false, 'error' => 'Failed to update message']);
}

$update_stmt->close();
$conn->close();
?>
