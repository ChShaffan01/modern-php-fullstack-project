<?php 
    require "db_con.php";
    if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = $con->prepare("DELETE FROM `crud1` WHERE `S_ID` = ?");
    $sql->bind_param("i",$id);
    if ($sql->execute()) {
        header("location:../index.php");
    } else {
        echo "Error deleting record: " . $con->error;
    }
    mysqli_connection_close();
    }
?>
