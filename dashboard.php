<?
session_start();

if(!isset($_SESSION['Aid'])){
    header("Location: login.php");
    exit();
}

echo "<h1>Welcome to your Profile</h1>";
echo "<p><a href= 'logout.php'</a></p>";
?>