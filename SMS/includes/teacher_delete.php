<?php

include 'db.php';

if(isset($_GET['id'])){
  $id = (int)$_GET['id'];

  $stmt = $conn->prepare("DELETE FROM teachers WHERE id=?");
  $stmt->bind_param("i",$id);

  if($stmt->execute()){
    header("Location: ../admin/teachers.php?deleted=1");
    exit;
  } else {
    echo "Error: " . $stmt->error;
  }
}

?>
