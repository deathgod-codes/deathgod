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
    
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(!empty($fname) && !empty($lname) && !empty($email) && !empty($password)){
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            // Validate password strength
            $passwordValidation = $security->validatePasswordStrength($password);
            if ($passwordValidation !== true) {
                echo $passwordValidation;
                exit();
            }
            
            // Check if email already exists using prepared statement
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0){
                echo $security->sanitizeOutput($email) . " - This email already exist!";
                $stmt->close();
            }else{
                $stmt->close();
                if(isset($_FILES['image'])){
                    $img_name = $_FILES['image']['name'];
                    $img_type = $_FILES['image']['type'];
                    $tmp_name = $_FILES['image']['tmp_name'];
                    
                    $img_explode = explode('.',$img_name);
                    $img_ext = end($img_explode);
    
                    $extensions = ["jpeg", "png", "jpg"];
                    if(in_array($img_ext, $extensions) === true){
                        $types = ["image/jpeg", "image/jpg", "image/png"];
                        if(in_array($img_type, $types) === true){
                            $time = time();
                            $new_img_name = $time.$img_name;
                            if(move_uploaded_file($tmp_name,"images/".$new_img_name)){
                                $ran_id = rand(time(), 100000000);
                                $status = "Active now";
                                // Use bcrypt instead of MD5
                                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                                
                                // Use prepared statement
                                $stmt = $conn->prepare("INSERT INTO users (unique_id, fname, lname, email, password, img, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("issssss", $ran_id, $fname, $lname, $email, $hashed_password, $new_img_name, $status);
                                
                                if($stmt->execute()){
                                    // Get the inserted user
                                    $select_stmt = $conn->prepare("SELECT unique_id FROM users WHERE email = ?");
                                    $select_stmt->bind_param("s", $email);
                                    $select_stmt->execute();
                                    $select_result = $select_stmt->get_result();
                                    
                                    if($select_result->num_rows > 0){
                                        $result = $select_result->fetch_assoc();
                                        $_SESSION['unique_id'] = $result['unique_id'];
                                        $_SESSION['last_activity'] = time();
                                        echo "success";
                                    }else{
                                        echo "This email address not Exist!";
                                    }
                                    $select_stmt->close();
                                }else{
                                    echo "Something went wrong. Please try again!";
                                }
                                $stmt->close();
                            }
                        }else{
                            echo "Please upload an image file - jpeg, png, jpg";
                        }
                    }else{
                        echo "Please upload an image file - jpeg, png, jpg";
                    }
                }
            }
        }else{
            echo $security->sanitizeOutput($email) . " is not a valid email!";
        }
    }else{
        echo "All input fields are required!";
    }
?>