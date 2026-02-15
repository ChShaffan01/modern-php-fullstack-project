<?php session_start();?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
    
    <div class="header">
     <div class="head">
        <h1>CS_CRUD</h1>
     </div>
    <nav>
        <ul>
        <li>
            <a href="../index.php">HOME</a>
        </li>
        <li>
        <a href="../frontend/add.php">ADD</a>
        </li>
        <li>
            <a href="../frontend/update.php">UPDATE</a>
        </li>
        <li>
            <a href="../frontend/delete.php">DELETE</a>
        </li>
    </ul>
    </nav>

<?php

require "db_con.php";


if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $_SESSION['id'] = $id;
    $query = $con->prepare("SELECT * FROM `crud1` WHERE S_ID = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $sql = $query->get_result();
    $row = mysqli_fetch_assoc($sql);

?>

<div class="main-content">
    <h2>Edit Record</h2>
    <form class="post-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
         <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?php echo $row['NAME']?>" />
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" value="<?php echo $row['ADDRESS']?>"  />
        </div>
        <div class="form-group">
            <label>Class</label>
            <select name="class">
                <option value="<?php echo $row['CLASS']?>"  selected ><?php echo $row['CLASS']?></option>
                <option value="B.TECH">B.TECH</option>
                <option value="B.CS">B.CS</option>
                <option value="BA">BA</option>
            </select>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo $row['PHONE_NO']?>"  />
        </div>
        <input class="submit" type="submit" value="Update" name="submit" />
    </form>

</div>
<?php
}

if(isset($_POST['submit'])){
    function clean_data($data){
        $data = trim($data);
        $data = htmlspecialchars($data);
        $data = stripslashes($data);
        return $data;
    }
    $id = $_SESSION['id'];
    unset($_SESSION['id']);
    $name = clean_data($_POST['name']);
    $address = clean_data($_POST['address']);
    $class = clean_data($_POST['class']);
    $phone = clean_data($_POST['phone']);

    $query = $con->prepare("UPDATE `crud1` SET `NAME`='$name',`ADDRESS`='$address',`CLASS`='$class',`PHONE_NO`='$phone' WHERE `S_ID` = ?");
    $query->bind_param("i", $id);
    $query->execute();

     mysqli_close($con);
     header("location:../index.php");
    
}
?>
</div>
</body>
</html>
