<?php
/**
 * Typing Status API Endpoint
 * Update and retrieve typing indicators
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

$method = $_SERVER['REQUEST_METHOD'];

if($method === 'POST'){
    // Update typing status
    $input = json_decode(file_get_contents('php://input'), true);
    
    if(!isset($input['conversation_id']) || !isset($input['is_typing'])){
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    $user_id = $_SESSION['unique_id'];
    $conversation_id = intval($input['conversation_id']);
    $is_typing = $input['is_typing'] ? 1 : 0;
    $conversation_type = isset($input['conversation_type']) ? $input['conversation_type'] : 'user';
    
    // Check if record exists
    $check_stmt = $conn->prepare("SELECT id FROM typing_status WHERE user_id = ? AND conversation_id = ? AND conversation_type = ?");
    $check_stmt->bind_param("iis", $user_id, $conversation_id, $conversation_type);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if($result->num_rows > 0){
        // Update existing record
        $update_stmt = $conn->prepare("UPDATE typing_status SET is_typing = ?, updated_at = NOW() WHERE user_id = ? AND conversation_id = ? AND conversation_type = ?");
        $update_stmt->bind_param("iiis", $is_typing, $user_id, $conversation_id, $conversation_type);
        $update_stmt->execute();
        $update_stmt->close();
    }else{
        // Insert new record
        $insert_stmt = $conn->prepare("INSERT INTO typing_status (user_id, conversation_id, conversation_type, is_typing) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iisi", $user_id, $conversation_id, $conversation_type, $is_typing);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    echo json_encode(['success' => true, 'message' => 'Typing status updated']);
    
}elseif($method === 'GET'){
    // Get typing status for a conversation
    if(!isset($_GET['conversation_id'])){
        echo json_encode(['success' => false, 'error' => 'Missing conversation_id']);
        exit();
    }
    
    $user_id = $_SESSION['unique_id'];
    $conversation_id = intval($_GET['conversation_id']);
    $conversation_type = isset($_GET['conversation_type']) ? $_GET['conversation_type'] : 'user';
    
    // Get users who are typing (exclude current user, only active in last 5 seconds)
    $stmt = $conn->prepare("SELECT u.fname, u.lname FROM typing_status t 
                            JOIN users u ON t.user_id = u.unique_id 
                            WHERE t.conversation_id = ? 
                            AND t.conversation_type = ? 
                            AND t.user_id != ? 
                            AND t.is_typing = 1 
                            AND t.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
    $stmt->bind_param("isi", $conversation_id, $conversation_type, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $typing_users = [];
    while($row = $result->fetch_assoc()){
        $typing_users[] = $row['fname'] . ' ' . $row['lname'];
    }
    
    $stmt->close();
    echo json_encode([
        'success' => true, 
        'is_typing' => count($typing_users) > 0,
        'typing_users' => $typing_users
    ]);
}else{
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
}

$conn->close();
?>
