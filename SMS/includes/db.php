<?php

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sms';

// Create a new MySQLi connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if ($conn->error) {
    die("Connection failed: " . $conn->connect_error);
}





?>