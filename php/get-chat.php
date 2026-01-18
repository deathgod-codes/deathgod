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
        $output = "";
        
        // Use prepared statement
        $stmt = $conn->prepare("SELECT m.*, u.img FROM messages m LEFT JOIN users u ON u.unique_id = m.outgoing_msg_id
                WHERE (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?)
                OR (m.outgoing_msg_id = ? AND m.incoming_msg_id = ?) ORDER BY m.msg_id");
        $stmt->bind_param("iiii", $outgoing_id, $incoming_id, $incoming_id, $outgoing_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                // Format timestamp
                $timestamp = '';
                if(isset($row['created_at']) && $row['created_at']) {
                    $time = strtotime($row['created_at']);
                    $now = time();
                    $diff = $now - $time;
                    
                    if($diff < 60) {
                        $timestamp = 'Just now';
                    } elseif($diff < 3600) {
                        $mins = floor($diff / 60);
                        $timestamp = $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
                    } elseif($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $timestamp = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                    } elseif($diff < 172800) {
                        $timestamp = 'Yesterday at ' . date('g:i A', $time);
                    } else {
                        $timestamp = date('M d, Y g:i A', $time);
                    }
                }
                
                $edited_indicator = (isset($row['is_edited']) && $row['is_edited']) ? ' <span class="edited">(edited)</span>' : '';
                
                if($row['outgoing_msg_id'] === $outgoing_id){
                    $output .= '<div class="chat outgoing">
                                <div class="details">
                                    <p>'. $security->sanitizeOutput($row['msg']) . $edited_indicator .'</p>
                                    <span class="timestamp">'. $timestamp .'</span>
                                </div>
                                </div>';
                }else{
                    $output .= '<div class="chat incoming">
                                <img src="php/images/'.$security->sanitizeOutput($row['img']).'" alt="">
                                <div class="details">
                                    <p>'. $security->sanitizeOutput($row['msg']) . $edited_indicator .'</p>
                                    <span class="timestamp">'. $timestamp .'</span>
                                </div>
                                </div>';
                }
            }
        }else{
            $output .= '<div class="text">No messages are available. Once you send message they will appear here.</div>';
        }
        $stmt->close();
        echo $output;
    }else{
        header("location: ../login.php");
    }

?>