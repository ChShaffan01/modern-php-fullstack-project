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
<link rel="stylesheet" href="assets/css/style.css">
<title>Form</title>
</head>
<body>
    <!-- _____________________________________________________________________________________________ -->
  
    <div class="container-fluid">
    <div style="margin-top: 40px;">
    <h1 style="text-align: center; color: #6A33D3; margin: 0; padding: 0;">SignUp</h1> <br>
    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 6px 6px 10px 0px #6A33D3; max-width: 500px ; margin: auto;" >
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
    <div class="form-group">
    <input class="form-control" type="text" placeholder="First Name" name="first_name">
    </div>

    <div class="form-group">
    <input class="form-control" type="text" placeholder="Last Name" name="last_name">
    </div>
   
    <div class="form-group">
    <input class="form-control" type="email" placeholder="E-mail" name="email">
    </div>
    <div class="form-group">
    <input class="form-control" type="password" placeholder="Password" name="pwd">
    </div>
    <div class="form-group">
    <input  class="form-control" type="number" placeholder="Mobile Number" name="phone">
    </div>
    <div class="form-group">
    <input style = "background-color: #d9eafaff;" type="file" placeholder="Choose File" name="myfile">
    </div>
    <div class="form-check form-check-inline">
    <input class="form-check-input" type="radio" name="gender" id="inlineRadio1" value="Male">
    <label class="form-check-label" for="inlineRadio1">Male</label>
    </div> 
    <div class="form-check form-check-inline">
    <input class="form-check-input" type="radio" name="gender" id="inlineRadio1" value="Female">
    <label class="form-check-label" for="inlineRadio1">Female</label>
    </div>
        <div class="form-group">
    <button class="btn btn-success btn-lg" type="submit" name="submit">Next</button> <a href="login.php"> Login</a>
    </div>

<?php
    if(isset($_POST['submit'])){
    require 'db.php';
    $first_name = $last_name = $email = $password = $gender = "";
    $file_name = $_FILES["myfile"]["name"];
    $temp_name = $_FILES["myfile"]["tmp_name"];
    $file_size = $_FILES["myfile"]["size"];
    $unique_name = time()."_".$file_name ;
    $folder = "uploads/". $unique_name;
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allow = ['jpg', 'png', 'gif', 'pdf', 'docx'];
    $error = [];

    function clean_input($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    if(empty(clean_input($_POST['first_name'])) || strlen(clean_input($_POST['first_name'])) < 3){
    $error = ["First Name is required and should be greater than 3 characters"];
    }
    elseif(empty(clean_input($_POST['last_name'])) || strlen(clean_input($_POST['last_name'])) < 3){
        $error = ["Last Name is required and should be greater than 3 characters"];
    }
    elseif(empty(clean_input($_POST['email'])) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        $error = [ "Valid Email is required"];
    }
    elseif(empty(clean_input($_POST['pwd'])) || strlen(clean_input($_POST['pwd'])) < 6){
        $error = ["Password is required and should be greater than 6 characters"];
    }
    elseif(empty(clean_input($_POST['phone'])) || !preg_match('/^[0-9]{11}+$/',clean_input($_POST['phone']))){
    $error[] = "Valid phone number is required";
    $error[] = "phone number is required";
    }
    elseif(empty($_POST["gender"])){
        $error = ["Gender must be require"];
    }
    elseif(!in_array($file_ext , $allow)){
        $error = ["File type not allowed."];
    }
    elseif($file_size > 2 * 1024 * 1024){
        $error = ["File Size must be check file size less then 2mb"];
    } 
    if(count($error) > 0 ){
        foreach ($error as  $value) {
            echo "<h5 style='color:red;'>".$value."</h5>";
        }
    }
    else{
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['pwd']);
    $phone = clean_input($_POST['phone']);
    $gender = $_POST['gender'];
    move_uploaded_file($temp_name, $folder);
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);


    $verify_query = "SELECT * FROM `auth` WHERE Email= '$email'";
    $res = mysqli_query($con, $verify_query);


    if(mysqli_num_rows($res) > 0){
        echo "<h5 style='color:red;'>Email already exists</h5>";
        mysqli_close($con);
        exit();
    }
    else{

    $sql = $con->prepare("INSERT INTO `auth`( `Name`, `Lname`, `Email`, `Pasword`, `Phone`, `File`, `Gender`) VALUES (?,?,?,?,?,?,?)");
    
    $sql->bind_param("sssssss", $first_name, $last_name, $email, $hashed_password, $phone, $folder, $gender);
    $sql->execute();
    header("location:login.php");
    exit();
    }
    mysqli_connection_close();
    }
    }

?>

    </form>
    </div>
    </div>
    </div>











    <!-- _____________________________________________________________________________________________ -->


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.min.js"></script>
</body>
</html>