 <?php 

    if(isset($_POST['login'])){

        include 'db.php';

        $email = $_POST['email'];
        $password = $_POST['pwd'];
        $role = $_POST['role'];

        // Prepare and execute the query
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? AND roles = ?");
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
          $user = $result->fetch_assoc();

          if(password_verify($password, $user['pasword'])){

            $_SESSION['user_id'] = $user['sid'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['roles'];
           
                header("Location: admin/dashboard.php");
                exit();} else { $_SESSION['error'] = "Invalid password.";} 
            }
        else {$_SESSION['error'] = "No user found with the provided email and role.";}
        $stmt->close();
        $conn->close();
        
     }
    ?>