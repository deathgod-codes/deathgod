<?php
session_start();
header('Content-Type: application/json');

if(isset($_SESSION['unique_id'])){
    echo json_encode(['unique_id' => $_SESSION['unique_id']]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
}
?>
