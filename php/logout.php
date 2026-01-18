<?php
    session_start();
    if(isset($_SESSION['unique_id'])){
        include_once "config.php";
        $logout_id = intval($_GET['logout_id']);
        if(isset($logout_id)){
            $status = "Offline now";
            // Use prepared statement
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE unique_id = ?");
            $stmt->bind_param("si", $status, $logout_id);
            if($stmt->execute()){
                session_unset();
                session_destroy();
                header("location: ../login.php");
            }
            $stmt->close();
        }else{
            header("location: ../users.php");
        }
    }else{  
        header("location: ../login.php");
    }
?>