<?php 
    session_start();
    if(isset($_SESSION['unique_id'])){
        include_once "config.php";
        include_once "Security.php";
        
        $security = new Security($conn);
        
        // Validate session
        if (!$security->validateSession()) {
            header("location: ../login.php");
            exit();
        }
        
        $outgoing_id = $_SESSION['unique_id'];
        $incoming_id = intval($_POST['incoming_id']);
        $message = trim($_POST['message']);
        
        if(!empty($message)){
            // Sanitize message for XSS protection (will be escaped on output)
            $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            
            // Use prepared statement
            $stmt = $conn->prepare("INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $incoming_id, $outgoing_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }else{
        header("location: ../login.php");
    }
?>