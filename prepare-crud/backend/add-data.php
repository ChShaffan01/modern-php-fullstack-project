<?php
session_start();
if(isset($_POST['submit'])){

function clean_input($data){
    $data = trim($data);
    $data = htmlspecialchars($data);
    $data = stripslashes($data);
    return $data;
}
$name = $address = $class = $phone = "";
$error = [];

if(empty(clean_input($_POST['name'])) || strlen(clean_input($_POST['name'])) < 3){
$error[] = "Invalid name minimum 3 name lenght require";
}
elseif (empty(clean_input($_POST['address'])) || strlen(clean_input($_POST['address'])) < 20) {
$error[] = "Invalid address minimum 20 address lenght require";
}
elseif (empty(clean_input($_POST['class']))) {
$error[] = "Class must be selected";
}
elseif (empty(clean_input($_POST['phone'])) || !preg_match('/^[0-9]{11}$/',clean_input($_POST['phone']))) {
$error[] = "Valid phone number is required";
$error[] = "phone number is required";
}
if(count($error) > 0){
    $_SESSION['error'] = $error;
    header("location:../frontend/add.php");
}
else{
    require "db_con.php";
    $name = clean_input($_POST['name']);
    $address = clean_input($_POST['address']);
    $class = clean_input($_POST['class']);
    $phone = clean_input($_POST['phone']);

    $sql = $con->prepare("INSERT INTO `crud1`(`S_ID`, `NAME`, `ADDRESS`, `CLASS`, `PHONE_NO`) VALUES (NULL , ?,?,?,?)");
    $sql->bind_param("ssss" , $name , $address , $class , $phone);
       if ($sql->execute()) {
        header("location:../index.php");
        mysqli_close($con);
    } else {
        echo "Error deleting record: " . $con->error;
    }
}
}
?>