<?php
include 'db.php';

if(isset($_GET['id'])){
  $id = (int)$_GET['id'];

  $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
  $stmt->bind_param("i",$id);

  if($stmt->execute()){
    header("Location: ../admin/students.php?deleted=1");
    exit;
  } else {
    echo "Error: " . $stmt->error;
  }
}
?>

                       