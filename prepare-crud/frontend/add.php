<?php session_start(); require "header.php";?>
<div class="main-content">
    <h2>Add New Record</h2>
    <form class="post-form" action="../backend/add-data.php" method="post">
    <?php 
     if(isset($_SESSION['error'])){
        foreach($_SESSION['error'] as $value){
         echo "<p style='color:red; text-align:center; font-size:14px; margin:-10px 0 20px 0px;'>".$value."</p>";
         unset($_SESSION['error']);
        }
     }
     else;
    ?>
         <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" />
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" />
        </div>
        <div class="form-group">
            <label>Class</label>
            <select name="class">
                <option value="NOT SELECTED" selected disabled>Select Class</option>
                <option value="B.TECH">B.TECH</option>
                <option value="B.CS">B.CS</option>
                <option value="BA">BA</option>
            </select>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" />
        </div>
        <input class="submit" type="submit" name="submit"  />
    </form>
</div>
</div>
</body>
</html>
