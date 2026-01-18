<?php
    session_start();
    include_once "config.php";
    include_once "Security.php";
    
    $security = new Security($conn);
    
    // Validate session
    if (!$security->validateSession()) {
        echo "Session expired";
        exit();
    }
    
    $outgoing_id = $_SESSION['unique_id'];
    
    // Use prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE NOT unique_id = ? ORDER BY user_id DESC");
    $stmt->bind_param("i", $outgoing_id);
    $stmt->execute();
    $query = $stmt->get_result();
    
    $output = "";
    if($query->num_rows == 0){
        $output .= "No users are available to chat";
    }elseif($query->num_rows > 0){
        include_once "data.php";
    }
    $stmt->close();
    echo $output;
?>