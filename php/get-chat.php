<?php 
    session_start();
    if(isset($_SESSION['unique_id'])){
        include_once "config.php";
        $outgoing_id = $_SESSION['unique_id'];
        $incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
        $output = "";
        $sql = "SELECT * FROM messages LEFT JOIN users ON users.unique_id = messages.outgoing_msg_id
                WHERE (outgoing_msg_id = {$outgoing_id} AND incoming_msg_id = {$incoming_id})
                OR (outgoing_msg_id = {$incoming_id} AND incoming_msg_id = {$outgoing_id}) ORDER BY msg_id";
        $query = mysqli_query($conn, $sql);
        if(mysqli_num_rows($query) > 0){
            while($row = mysqli_fetch_assoc($query)){
                $messageContent = '';
                
                // Check if message has file attachment
                if (!empty($row['file_url'])) {
                    $fileUrl = htmlspecialchars($row['file_url']);
                    $fileType = $row['file_type'];
                    
                    if ($fileType === 'image') {
                        $messageContent = '<div class="file-attachment">
                            <img src="'.$fileUrl.'" alt="Image" onclick="openImageLightbox(\''.$fileUrl.'\')">
                        </div>';
                    } elseif ($fileType === 'video') {
                        $messageContent = '<div class="file-attachment">
                            <video controls><source src="'.$fileUrl.'" type="video/mp4"></video>
                        </div>';
                    } elseif ($fileType === 'audio') {
                        $messageContent = '<div class="file-attachment">
                            <audio controls><source src="'.$fileUrl.'"></audio>
                        </div>';
                    } else {
                        $fileName = basename($fileUrl);
                        $fileSize = !empty($row['file_size']) ? $row['file_size'] : 0;
                        $fileSizeStr = formatFileSize($fileSize);
                        $messageContent = '<a href="api/v1/files/download.php?file='.urlencode($fileUrl).'" class="file-document" target="_blank">
                            <span class="file-icon">📄</span>
                            <div class="file-info">
                                <div class="name">'.$fileName.'</div>
                                <div class="size">'.$fileSizeStr.'</div>
                            </div>
                        </a>';
                    }
                } else {
                    // Regular text message
                    $messageContent = '<p>'. htmlspecialchars($row['msg']) .'</p>';
                }
                
                if($row['outgoing_msg_id'] === $outgoing_id){
                    $output .= '<div class="chat outgoing">
                                <div class="details">
                                    '. $messageContent .'
                                </div>
                                </div>';
                }else{
                    $output .= '<div class="chat incoming">
                                <img src="php/images/'.$row['img'].'" alt="">
                                <div class="details">
                                    '. $messageContent .'
                                </div>
                                </div>';
                }
            }
        }else{
            $output .= '<div class="text">No messages are available. Once you send message they will appear here.</div>';
        }
        echo $output;
    }else{
        header("location: ../login.php");
    }
    
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 Bytes';
        $k = 1024;
        $sizes = array('Bytes', 'KB', 'MB', 'GB');
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

?>