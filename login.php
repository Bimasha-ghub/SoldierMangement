<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'dbconnection.php';

    $armyid = mysqli_real_escape_string($con, $_POST["txtarmyid"]);
    $password = mysqli_real_escape_string($con, $_POST["txtpass"]);

    $sql = "SELECT * FROM soldetails WHERE Aid = '$armyid' AND password = '$password'";
    $result = mysqli_query($con, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['Aid'] = $armyid;
        $_SESSION['position'] = $row['position'];
        header("Location: profile.php");


        exit();
    } else {
        echo "<script>alert('Invalid Army ID or Password.'); window.location.href='login.php';</script>";
    }

    mysqli_close($con);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Official Portal - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('photo/loginbg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .login-container {
            position: relative;
            z-index: 2;
            width: 400px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: -10px;
        }
        
        .logo img {
            max-width: 150px;
        }
        
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px;
            background: #f5f5f5;
            border: none;
            outline: none;
            border-radius: 4px;
            font-size: 16px;
            transition: 0.3s;
        }
        
        .input-group input:focus {
            background: #e8f0fe;
        }
        
        .input-group label {
            position: absolute;
            top: 15px;
            left: 15px;
            color: #7f8c8d;
            pointer-events: none;
            transition: 0.3s;
            font-size: 16px;
            
        }
        
        .input-group input:focus + label,
        .input-group input:valid + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            background: transparent;
            padding: 0 5px;
            color: #288ccf;
            font-family: Arial;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        
        .forgot-password a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            color: #3498db;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <!-- Replace with your organization's logo -->
            <img src="photo/ARMY.png" alt="Company Logo" >
        </div>
        
        <h2>OFFICIAL PORTAL LOGIN</h2>
        
        <form name="frmlogin" method="post" action="#">


            <div class="input-group">
                <input type="text" id="txtarmyid" name="txtarmyid" required>
                <label>Username</label>
            </div>
            
            <div class="input-group">
                <input type="password"  id="txtpass" name="txtpass" required>
                <label>Password</label>
            </div>
            
            <button type="submit" id="btnlogin">LOGIN</button>
            
            <div class="footer">
                 2025 ARMY. All Rights Reserved.
            </div>
        </form>
    </div>
</body>
</html>

