<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['Aid'])) {
    // Redirect to login page if not logged in
    header("Location: /Final/login.php");
    exit();
}
$armyId = $_SESSION['Aid'];

include 'dbconnection.php';
// Get logged in user's information
function getUserInfo($conn, $armyId) {
    $sql = "SELECT * FROM soldetails WHERE Aid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $armyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get regiment information
function getRegimentInfo($conn, $regimentId) {
    $sql = "SELECT * FROM regiment WHERE regiment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $regimentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get colonel of the regiment
function getColonelOfRegiment($conn, $regimentId) {
    $sql = "SELECT s.* FROM soldetails s 
            WHERE s.position = 'Colonel of the regiment' 
            AND s.regiment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $regimentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get personnel count in regiment
function getPersonnelCount($conn, $regimentId) {
    $sql = "SELECT COUNT(*) as count FROM soldetails WHERE regiment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $regimentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    } else {
        return 0;
    }
}

// Function to update activePersonnel in regiment table
function updateActivePersonnel($conn, $regimentId, $count) {
    $sql = "UPDATE regiment SET active_personnel = ? WHERE regiment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $count, $regimentId);
    return $stmt->execute();
}

// Main code
$conn = connectDB();

// Get user info based on logged in Army ID
$userInfo = getUserInfo($conn, $armyId);

if ($userInfo) {
    $regimentId = $userInfo['regiment_id'];
    
    // Get personnel count in regiment and update activePersonnel
    $personnelCount = getPersonnelCount($conn, $regimentId);
    
    // Update the activePersonnel in the database
    $updateResult = updateActivePersonnel($conn, $regimentId, $personnelCount);
    
    // Get regiment info AFTER updating the database
    $regimentInfo = getRegimentInfo($conn, $regimentId);
    $colonelInfo = getColonelOfRegiment($conn, $regimentId);
    
    // Set default values if no regiment info found
    if (!$regimentInfo) {
        $regimentInfo = [
            'regiment_name' => 'Unknown Regiment',
            'regiment_type' => 'Unknown',
            'active_personnel' => $personnelCount
        ];
    }
    
    // Set default values if no colonel found
    if (!$colonelInfo) {
        $colonelInfo = [
            'fullName' => 'Not Assigned',
            'Aid' => 'N/A',
            'joinDate' => null,
            'profile' => ''
        ];
    }
} else {
    // Handle case where user info can't be found
    echo "<script>alert('User information could not be found.'); window.location.href='login.html';</script>";
    exit();
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($regimentInfo['regiment_name']); ?></title>
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
    <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
    <style>
        header{
            background-image: url('sniper.jpg');
            background-size: cover;
            background-attachment: fixed;
            padding: 300px;
        }

        header h1{
            color: white;
            font-family: 'Recoleta Regular';
            font-size: 60px;
            text-align: right;
        }

        .container{
            margin-top: 150px;
        }

        #card1{
            width: 900;
            height: auto;
            border: hidden;
            margin-bottom: 100px;
        }

        .regimentImg{
            width: 200px;
            height: 200px;
            background-color: silver;
            margin-bottom: 40px;
        }

        #regimentName{
            font-family: 'Recoleta Regular';
            font-size: 80px;
            text-align: center;
            color: darkblue;
        }

        #card2-title{
            font-family: 'Recoleta Regular';
            font-size: 40px;
            color: maroon;
            text-align: center;
            margin-bottom: 20px;
        }

        .colonelOfRegimentImg{
            width: 150px;
            height: 150px;
            border-radius: 50px;
            object-fit: cover;
            background-color: silver;
        }

        #card2{
            width: 500px;
            height: 550px;
            border-color: darkgreen;
            border-width: 6px;
            margin-bottom: 100px;
        }

        #card3{
            width: 400px;
            height: 250px;
            border-width: 4px;
            border-color: black;
        }

        label{
            font-family: 'Roboto';
            font-weight: bold;
            font-size: 20px;
            text-align: left;
        }

        .data{
            font-family: 'Tahoma';
        }

        .data-rg{
            font-family: 'Tahoma';
            color: green;
        }

        #sub-title{
            font-family: 'Recoleta Regular';
            font-weight: 500;
            font-size: 50px;
            text-align: center;
            margin-top: 50px;
        }

        #card4{
            width: 400px;
            height: 450px;
            border-color: darkgreen;
            border-width: 2px;
        }

        #card4-title{
            font-family: 'Recoleta Regular';
            font-size: 40px;
            color: maroon;
            text-align: center;
            margin-bottom: 20px;
        }

        #card5{
            width: 400px;
            height: 450px;
            border-color: darkgreen;
            border-width: 2px;
        }

        #card5-title{
            font-family: 'Recoleta Regular';
            font-size: 40px;
            color: maroon;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Home Of The Warriors</h1>
    </header>

    <div class="container">
        <center>
        <div class="card" id="card1">
            <div class="card-body">

                <h1 id="regimentName"><?php echo htmlspecialchars($regimentInfo['regiment_name']); ?></h1>
            </div>
        </div>
        </center>
        <div class="row">
            <div class="col-lg">
                <div class="card" id="card2">
                    <div class="card-body">
                        <h2 id="card2-title">Colonel of The Regiment</h2>
                        <br>
                        <label class="label">Name</label> 
                        <p class="data" id="cogName"><?php echo htmlspecialchars($colonelInfo['fullName']); ?></p>
                        <br>
                        <label class="label">Army ID</label> 
                        <p class="data" id="cogID"><?php echo htmlspecialchars($colonelInfo['Aid']); ?></p>
                        <br>
                        <label class="label">Accepted Year</label> 
                        <p class="data" id="cogDate"><?php 
                            if (!empty($colonelInfo['joinDate'])) {
                                echo date('Y', strtotime($colonelInfo['joinDate']));
                            } else {
                                echo 'N/A';
                            }
                        ?></p>
                        <br>
                    </div>
                </div>
            </div>
            <div class="col-lg">
                <center>
                <div class="card" id="card3">
                    <div class="card-body">
                        <label class="label">Regiment Type</label> 
                        <p class="data-rg" id="regiment-type"><?php echo htmlspecialchars($regimentInfo['regiment_type']); ?></p>
                        <br>
                        <label class="label">Active Personnel</label> 
                        <p class="data-rg" id="active-personnel"><?php echo htmlspecialchars($regimentInfo['active_personnel']); ?></p>
                        <br>
                    </div>
                </div>
                </center>
            </div>
        </div>
    </div>
</body>
</html>