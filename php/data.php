<?php
    while($row = $query->fetch_assoc()){
        // Get last message using prepared statement
        $stmt2 = $conn->prepare("SELECT * FROM messages WHERE (incoming_msg_id = ? OR outgoing_msg_id = ?) AND (outgoing_msg_id = ? OR incoming_msg_id = ?) ORDER BY msg_id DESC LIMIT 1");
        $stmt2->bind_param("iiii", $row['unique_id'], $row['unique_id'], $outgoing_id, $outgoing_id);
        $stmt2->execute();
        $query2 = $stmt2->get_result();
        $row2 = $query2->fetch_assoc();
        
        ($query2->num_rows > 0) ? $result = $row2['msg'] : $result ="No message available";
        (strlen($result) > 28) ? $msg =  substr($result, 0, 28) . '...' : $msg = $result;
        if(isset($row2['outgoing_msg_id'])){
            ($outgoing_id == $row2['outgoing_msg_id']) ? $you = "You: " : $you = "";
        }else{
            $you = "";
        }
        ($row['status'] == "Offline now") ? $offline = "offline" : $offline = "";
        ($outgoing_id == $row['unique_id']) ? $hid_me = "hide" : $hid_me = "";

        $output .= '<a href="chat.php?user_id='. $security->sanitizeOutput($row['unique_id']) .'">
                    <div class="content">
                    <img src="php/images/'. $security->sanitizeOutput($row['img']) .'" alt="">
                    <div class="details">
                        <span>'. $security->sanitizeOutput($row['fname']. " " . $row['lname']) .'</span>
                        <p>'. $security->sanitizeOutput($you . $msg) .'</p>
                    </div>
                    </div>
                    <div class="status-dot '. $offline .'"><i class="fas fa-circle"></i></div>
                </a>';
        $stmt2->close();
    }
?>