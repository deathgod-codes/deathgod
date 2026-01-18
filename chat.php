<?php 
  session_start();
  include_once "php/config.php";
  include_once "php/Security.php";
  
  // Set secure headers
  Security::setSecureHeaders();
  
  $security = new Security($conn);
  
  if(!isset($_SESSION['unique_id'])){
    header("location: login.php");
    exit();
  }
  
  // Validate session timeout
  if (!$security->validateSession()) {
    header("location: login.php");
    exit();
  }
?>
<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="chat-area">
      <header>
        <?php 
          $user_id = intval($_GET['user_id']);
          $stmt = $conn->prepare("SELECT * FROM users WHERE unique_id = ?");
          $stmt->bind_param("i", $user_id);
          $stmt->execute();
          $result = $stmt->get_result();
          
          if($result->num_rows > 0){
            $row = $result->fetch_assoc();
          }else{
            header("location: users.php");
            exit();
          }
          $stmt->close();
        ?>
        <a href="users.php" class="back-icon"><i class="fas fa-arrow-left"></i></a>
        <img src="php/images/<?php echo $security->sanitizeOutput($row['img']); ?>" alt="">
        <div class="details">
          <span><?php echo $security->sanitizeOutput($row['fname']. " " . $row['lname']); ?></span>
          <p><?php echo $security->sanitizeOutput($row['status']); ?></p>
        </div>
      </header>
      <div class="chat-box">

      </div>
      <form action="#" class="typing-area">
        <input type="text" class="incoming_id" name="incoming_id" value="<?php echo $user_id; ?>" hidden>
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <button><i class="fab fa-telegram-plane"></i></button>
      </form>
    </section>
  </div>

  <script src="javascript/chat.js"></script>

</body>
</html>
