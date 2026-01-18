<?php
/**
 * Message Reaction API Endpoint
 * Allows users to add emoji reactions to messages
 */
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['unique_id'])){
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

include_once "../../../php/config.php";
include_once "../../../php/Security.php";
include_once "../../../php/Config.php";

$security = new Security($conn);

// Validate session
if (!$security->validateSession()) {
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['msg_id']) || !isset($input['reaction'])){
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$msg_id = intval($input['msg_id']);
$reaction = $input['reaction'];
$user_id = $_SESSION['unique_id'];

// Validate reaction (use Config class)
if(!in_array($reaction, Config::ALLOWED_REACTIONS)){
    echo json_encode(['success' => false, 'error' => 'Invalid reaction']);
    exit();
}

// Check if message exists
$stmt = $conn->prepare("SELECT msg_id FROM messages WHERE msg_id = ?");
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    $stmt->close();
    exit();
}
$stmt->close();

// Check if user already reacted with this emoji
$check_stmt = $conn->prepare("SELECT reaction_id FROM message_reactions WHERE msg_id = ? AND user_id = ? AND reaction = ?");
$check_stmt->bind_param("iis", $msg_id, $user_id, $reaction);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if($check_result->num_rows > 0){
    // Remove reaction if already exists (toggle)
    $delete_stmt = $conn->prepare("DELETE FROM message_reactions WHERE msg_id = ? AND user_id = ? AND reaction = ?");
    $delete_stmt->bind_param("iis", $msg_id, $user_id, $reaction);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    echo json_encode([
        'success' => true, 
        'action' => 'removed',
        'message' => 'Reaction removed'
    ]);
}else{
    // Add new reaction
    $insert_stmt = $conn->prepare("INSERT INTO message_reactions (msg_id, user_id, reaction) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iis", $msg_id, $user_id, $reaction);
    
    if($insert_stmt->execute()){
        echo json_encode([
            'success' => true, 
            'action' => 'added',
            'message' => 'Reaction added successfully'
        ]);
    }else{
        echo json_encode(['success' => false, 'error' => 'Failed to add reaction']);
    }
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>
