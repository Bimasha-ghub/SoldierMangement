<?php
$host = "localhost";
$user = "root"; // your MySQL username
$password = "200310"; // your MySQL password
$database = "militarydb"; // your actual database name

$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
