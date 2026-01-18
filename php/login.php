<?php 
    session_start();
    include_once "config.php";
    include_once "Security.php";
    
    // Set secure headers
    Security::setSecureHeaders();
    
    $security = new Security($conn);
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$security->verifyCSRFToken($_POST['csrf_token'])) {
        echo "Invalid security token. Please refresh the page.";
        exit();
    }
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(!empty($email) && !empty($password)){
        // Check rate limiting
        $rateLimitCheck = $security->checkLoginRateLimit($email);
        if ($rateLimitCheck !== true) {
            echo $rateLimitCheck;
            exit();
        }
        
        // Use prepared statement
        $stmt = $conn->prepare("SELECT user_id, unique_id, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            
            // Verify password using password_verify instead of MD5
            if(password_verify($password, $row['password'])){
                $status = "Active now";
                // Update status using prepared statement
                $update_stmt = $conn->prepare("UPDATE users SET status = ? WHERE unique_id = ?");
                $update_stmt->bind_param("si", $status, $row['unique_id']);
                
                if($update_stmt->execute()){
                    $_SESSION['unique_id'] = $row['unique_id'];
                    $_SESSION['last_activity'] = time();
                    
                    // Reset failed login attempts
                    $security->resetFailedLogin($email);
                    
                    echo "success";
                }else{
                    echo "Something went wrong. Please try again!";
                }
                $update_stmt->close();
            }else{
                // Increment failed login attempts
                $security->incrementFailedLogin($email);
                echo "Email or Password is Incorrect!";
            }
        }else{
            echo $security->sanitizeOutput($email) . " - This email does not exist!";
        }
        $stmt->close();
    }else{
        echo "All input fields are required!";
    }
?>