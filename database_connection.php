<?php
$connect = mysqli_connect('localhost', 'root', '', 'inventory', 3306);

// Check connection
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

session_start();

// Register session variables
$_SESSION['type'] = 'master'; // Set initial value
$_SESSION['user_name'] = 'john_smith@example.com'; // Set initial value
$_SESSION['password'] = 'password';
?>
