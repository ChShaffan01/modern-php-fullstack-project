
<?php 
session_start();
if(!isset($_SESSION['loggedin'])) {
    header("Location: auth/login.php");
    exit;
}
else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    
    <div class="header">
     <div class="head">
        <h1>CS_CRUD</h1>
     </div>
    <nav >
        <ul>
        <li>
            <a href="index.php">HOME</a>
        </li>
        <li>
        <a href="frontend/add.php">ADD</a>
        </li>
        <li>
            <a href="frontend/update.php">UPDATE</a>
        </li>
        <li>
            <a href="frontend/delete.php">DELETE</a>
        </li>

        <li style="float:right; margin-right: 10px; ;">
            <a style="color: #ff9595ff;" href="auth/logout.php">LOGOUT</a>
        </li>
    </ul>

    </nav>
    <?php 
    require "backend/db_con.php";
    $query = "SELECT * FROM `crud1`";
    $sql = mysqli_query($con , $query);
     
  
    ?>
<div class='main-content'>
<h2>All Record</h2>
<table cellpadding="7px"> 

    <thead>
        
        <th>Id</th>
        <th>Name</th>
        <th>Address</th>
        <th>Class</th>
        <th>Phone</th>
        <th>Action</th>
        </thead>
    
   <tbody>
    <?php if (mysqli_num_rows($sql)> 0) {
        $count = 1;
                while ($row = mysqli_fetch_assoc($sql)) { ?>
                    <tr>
                        <td><?php echo $count;?></td>
                        <td><?php echo htmlspecialchars($row['NAME']);?></td>
                        <td><?php echo htmlspecialchars($row['ADDRESS']);?></td>
                        <td><?php echo htmlspecialchars($row['CLASS']);?></td>
                        <td><?php echo htmlspecialchars($row['PHONE_NO']);?></td>
                        <td class="td1">
                            <a class="td-a1" href="backend/edit-inline.php?id=<?php echo $row['S_ID'];?>">Edit</a>
                            <a class="td-a2" href="backend/delete-inline.php?id=<?php echo $row['S_ID'];?>" onclick="return confirm('Are you sure you want to delete?');">Delete</a>
                        </td>
                    </tr>
               <?php
            $count++;   }
} else {
                echo "<tr><td colspan='6'>No records found.</td></tr>";
}?>
    

   </tbody>
</table>
</div>

</div>
</body>
</html>
<?php } ?>
