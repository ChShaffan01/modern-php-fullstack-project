<?php require "header.php";
 session_start();?>

<div class="main-content">
    <h2>DELETE Record</h2>
<form class="post-form" action="../backend/delete-id.php" method="post">
         <div class="form-group">
            <label>Id</label>
            <input type="number" name="sid">
         </div>
        <?php 
     if(isset($_SESSION['error'])){
         echo "<p style='color:red; margin-left:220px'>".$_SESSION['error']."</p>";
         unset($_SESSION['error']);
        }
     else;
     ?>
        <input class="submit" type="submit" name="submit">
</form>
</div>
</div>
</body>
</html>
