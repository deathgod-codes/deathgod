<?php
/**
 * Message Delete API Endpoint
 * Allows users to delete their own messages (soft delete)
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

if(!isset($input['msg_id'])){
    echo json_encode(['success' => false, 'error' => 'Missing message ID']);
    exit();
}

$msg_id = intval($input['msg_id']);
$user_id = $_SESSION['unique_id'];

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

// Soft delete the message
$delete_stmt = $conn->prepare("UPDATE messages SET deleted_at = NOW(), msg = '[Message deleted]' WHERE msg_id = ?");
$delete_stmt->bind_param("i", $msg_id);

if($delete_stmt->execute()){
    echo json_encode([
        'success' => true, 
        'message' => 'Message deleted successfully'
    ]);
}else{
    echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
}

$delete_stmt->close();
$conn->close();
?>
