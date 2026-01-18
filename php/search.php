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
    $searchTerm = trim($_POST['searchTerm']);

    // Use prepared statement with LIKE
    $searchParam = "%{$searchTerm}%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE NOT unique_id = ? AND (fname LIKE ? OR lname LIKE ?)");
    $stmt->bind_param("iss", $outgoing_id, $searchParam, $searchParam);
    $stmt->execute();
    $query = $stmt->get_result();
    
    $output = "";
    if($query->num_rows > 0){
        include_once "data.php";
    }else{
        $output .= 'No user found related to your search term';
    }
    $stmt->close();
    echo $output;
?>