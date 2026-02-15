<?php session_start(); require "header.php";?>
<div class="main-content">
    <h2>Update Record</h2>
<form class="post-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
         <div class="form-group">
            <label>Id</label>
            <input type="number" name="sid" />
        </div>
        <input class="submit" type="submit" value="Show" name="show" />
</form>
<?php  
if(isset($_POST['show'])){
    require "../backend/db_con.php";
     if (empty($_POST['sid']) || !is_numeric($_POST['sid'])) {
        echo "Invalid ID";
        exit;
    }
    $id = $_POST['sid'];
    $_SESSION['id'] = $id;

    $query = $con->prepare("SELECT * FROM `crud1` WHERE `S_ID` = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $sql = $query->get_result();
    if(mysqli_num_rows($sql) > 0){ 
    $row = mysqli_fetch_assoc($sql);
?>
    <form class="post-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
         <div class="form-group">
            <label>Name</label>
        <input type="text" name="name" value="<?php echo $row['NAME']?>" />

        </div>
        <div class="form-group">
            <label>Address</label>
       <input type="text" name="name" value="<?php echo $row['ADDRESS']?>" />

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
    <?php }else{ ?>
     <div style="color:red;text-align:center;"> Invalid Id</div>
    <?php
    } } 
    if(isset($_POST['submit'])){
    require "../backend/db_con.php";
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
</div>
</body>
</html>
