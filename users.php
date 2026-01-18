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
    <section class="users">
      <header>
        <div class="content">
          <?php 
            $stmt = $conn->prepare("SELECT * FROM users WHERE unique_id = ?");
            $stmt->bind_param("i", $_SESSION['unique_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0){
              $row = $result->fetch_assoc();
            }
            $stmt->close();
          ?>
          <img src="php/images/<?php echo $security->sanitizeOutput($row['img']); ?>" alt="">
          <div class="details">
            <span><?php echo $security->sanitizeOutput($row['fname']. " " . $row['lname']); ?></span>
            <p><?php echo $security->sanitizeOutput($row['status']); ?></p>
          </div>
        </div>
        <a href="php/logout.php?logout_id=<?php echo $row['unique_id']; ?>" class="logout">Logout</a>
      </header>
      <div class="search">
        <span class="text">Select an user to start chat</span>
        <input type="text" placeholder="Enter name to search...">
        <button><i class="fas fa-search"></i></button>
      </div>
      <div class="users-list">
  
      </div>
    </section>
  </div>

  <script src="javascript/users.js"></script>

</body>
</html>
