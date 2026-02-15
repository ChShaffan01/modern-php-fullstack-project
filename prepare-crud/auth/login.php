<?php session_start();?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="Description" content="Enter your description here"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

<title>Loggin</title>
<style>
    .container-fluid{
        width: 500px;
    }
</style>
</head>
<body>
<div class="container-fluid">
<div style="margin-top: 40px;">
<h1 style="text-align: center; color: #6A33D3; margin: 0; padding: 0;">Login</h1><br>
<div class=style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 6px 6px 10px 0px #6A33D3; max-width: 500px ; margin: auto;" >
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>" method="POST">
<div class="form-group">
<input class="form-control" type="email" placeholder="email" name="email" required>
</div>
<div class="form-group">
<input class="form-control" type="password" placeholder="Password" name="pwd" required>
</div>
<div class="form-group">
<button class="btn btn-success btn-lg" type="submit" name="login">Login</button> <a href="registration.php">Create new account</a>
</div>
</form>
</div>
</div>
</div>

<?php

if(isset($_POST['login'])){
    require 'db.php';
    function clean_input($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['pwd']);
    $sql = $con->prepare("SELECT * FROM auth WHERE Email = ?");
    $sql->bind_param("s", $email);
    $sql->execute();
    // $result = $sql->bind_result($id, $name, $hashed_password);
    $result = $sql->get_result();

    if($row = $result->fetch_assoc()){
        if(password_verify($password, $row['Pasword'])){
            $_SESSION['user_id'] = $row['Id'];
            $_SESSION['user'] = $row['Name'];
            $_SESSION['loggedin'] = true;
            header("Location:../index.php");
            exit;
        }
        else {
            echo "<p style='color:red; text-align:center;'>Wrong Password</p>";
    }
    }
    else {
        echo "<p style='color:red; text-align:center;'>User not found</p>";
    }
    






    // if(mysqli_num_rows($result) > 0){
    //     $_SESSION['loggedin'] = true;
    //     $_SESSION['email'] = $email;
    //     $_SESSION['password'] = $password;
    //     header("Location:../index.php");
    //     exit;
    // } 
    // else {
    //     echo "<p style='color:red; text-align:center;'>Invalid Login</p>";
    // }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.min.js"></script>
</body>
</html>


