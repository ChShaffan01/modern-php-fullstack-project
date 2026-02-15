<?php
session_start();
if (isset($_POST['submit'])) {
    require "db_con.php";

    $sid = $_POST['sid'];

    if (empty($sid) || !is_numeric($sid)) {
        $_SESSION['error'] = "Invalid ID";
        header("location:../frontend/delete.php");
        exit;
    }

    $sid = intval($sid);

    $sql = $con->prepare("DELETE FROM crud1 WHERE S_ID = ?");
    $sql->bind_param("i", $sid);
    $sql->execute();

    if (mysqli_affected_rows($con) > 0) {
        mysqli_close($con);
        header("location:../index.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid ID";
        header("location:../frontend/delete.php");
        exit;
    }
}
?>
