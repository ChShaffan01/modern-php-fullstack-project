<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "crud";

$con = mysqli_connect($host,$user,$pass,$db);

if(!$con){
    die("DB not connected Error : " . mysqli_connect_error());
}
else;

?>