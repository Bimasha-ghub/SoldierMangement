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

// Get battalion information
function getBattalionInfo($conn, $battalionId) {
    $sql = "SELECT * FROM battalion WHERE battalion_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $battalionId);
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

// Get battalion officer information from battalionofficer table with soldier details
function getBattalionOfficer($conn, $battalionId) {
    $sql = "SELECT bo.Aid, bo.starteddate, bo.enddate, bo.serviceperiod, 
                   s.fullName, s.rank, s.joinDate, s.profile
            FROM battalionofficer bo
            LEFT JOIN soldetails s ON bo.Aid = s.Aid
            WHERE bo.battalion_id = ?
            ORDER BY bo.starteddate DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $battalionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

// Get personnel count in battalion
function getPersonnelCount($conn, $battalionId) {
    $sql = "SELECT COUNT(*) as count FROM soldetails WHERE battalion_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $battalionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'];
    } else {
        return 0;
    }
}

// Function to update activePersonnel in battalion table
function updateActivePersonnel($conn, $battalionId, $count) {
    // First check if the battalion exists
    $checkSql = "SELECT battalion_id FROM battalion WHERE battalion_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $battalionId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Battalion exists, update it
        $sql = "UPDATE battalion SET active_personnel = ? WHERE battalion_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $count, $battalionId);
        $result = $stmt->execute();
        return $result;
    } else {
        // Battalion doesn't exist, insert it
        $sql = "INSERT INTO battalion (battalion_id, active_personnel) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $battalionId, $count);
        $result = $stmt->execute();
        return $result;
    }
}

// Get battalion number from battalion ID
function getBattalionNumber($battalionId) {
    if (preg_match('/^b(\d+)$/', $battalionId, $matches)) {
        return $matches[1];
    }
    return "";
}

// Get battalion name based on number and regiment
function getBattalionName($battalionNumber, $regimentName) {
    return $battalionNumber . " " . $regimentName;
}

// Main code
$conn = connectDB();

// Get user info based on logged in Army ID
$userInfo = getUserInfo($conn, $armyId);

if ($userInfo) {
    $battalionId = $userInfo['battalion_id'];
    $regimentId = $userInfo['regiment_id'];
    
    // Get personnel count in battalion and update activePersonnel
    $personnelCount = getPersonnelCount($conn, $battalionId);
    
    // Update the activePersonnel in the database
    $updateResult = updateActivePersonnel($conn, $battalionId, $personnelCount);
    
    // Get battalion and regiment info AFTER updating the database
    $battalionInfo = getBattalionInfo($conn, $battalionId);
    $regimentInfo = getRegimentInfo($conn, $regimentId);
    $officerInfo = getBattalionOfficer($conn, $battalionId);
    
    // Extract battalion number from ID (e.g., b1 -> 1)
    $battalionNumber = getBattalionNumber($battalionId);
    
    // Generate battalion name
    $battalionName = getBattalionName($battalionNumber, isset($regimentInfo['regiment_name']) ? $regimentInfo['regiment_name'] : 'Commando Regiment');
    
    // Verify that we have the updated personnel count
    $activePersonnel = isset($battalionInfo['active_personnel']) ? $battalionInfo['active_personnel'] : $personnelCount;
    
    // Update the battalion name in the database if needed
    if (isset($battalionInfo) && (!isset($battalionInfo['battalion_name']) || $battalionInfo['battalion_name'] != $battalionName)) {
        $updateNameSql = "UPDATE battalion SET battalion_name = ? WHERE battalion_id = ?";
        $updateNameStmt = $conn->prepare($updateNameSql);
        $updateNameStmt->bind_param("ss", $battalionName, $battalionId);
        $updateNameStmt->execute();
        
        // Get updated battalion info
        $battalionInfo = getBattalionInfo($conn, $battalionId);
    }
    
    // Set default values if no officer found
    if (!$officerInfo) {
        $officerInfo = [
            'fullName' => 'Not Assigned',
            'Aid' => 'N/A',
            'rank' => 'N/A',
            'joinDate' => null,
            'starteddate' => null,
            'enddate' => null,
            'serviceperiod' => 'N/A',
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
    <title>Battalion</title>
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
    <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
    <style>
        header{
            background-image: url('photo/team.jpg');
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
            width: 900px;
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

        #regiment_name{
            font-family: 'Recoleta Regular';
            font-size: 80px;
            text-align: center;
            color: darkblue;
        }

        .regiment_name{
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

        .battalionOfficerImg{
            width: 150px;
            height: 150px;
            border-radius: 50px;
            object-fit: cover;
            background-color: silver
        }

        #card2{
            width: 500px;
            height: 700px;
            border-color: darkgreen;
            border-width: 6px;
            margin-bottom: 100px;
        }

        .label{
            font-family: 'Recoleta Regular';
            font-weight: bold;
            font-size: 20px;
            text-align: left;
        }

        /* Unified data style for all cards */
        .data{
            font-family: 'Tahoma';
            font-size: 18px;
        }

        /* Specific color overrides for regiment data */
        .regiment-data{
            font-family: 'Tahoma';
            font-size: 18px;
            color: blue;
        }

        /* Specific color overrides for battalion data */
        .battalion-data{
            font-family: 'Tahoma';
            font-size: 18px;
            color: green;
        }

        #card3{
            width: 400px;
            height: 250px;
            border-width: 4px;
            border-color: black;
            margin-bottom: 80px;
        }

        #card4{
            width: 400px;
            height: 280px;
            border-width: 4px;
            border-color: black;
            margin-bottom: 150px;
        }
        
        .battalion-name-display {
            font-family: 'Tahoma';
            font-size: 18px;
            color: blue;
        }
    </style>
</head>
<body onload="checkDatabase()">
    <header>  
            <h1>Battalions Of The Warriors</h1>   
    </header>

    <div class="container">
        <center>
        <div class="card" id="card1">
            <div class="card-body">
                <br>
                <p class="regiment_name">
                    <?php 
                        // Format as "Battalion Number + Regiment Name"
                        echo isset($battalionInfo['battalion_name']) ? $battalionInfo['battalion_name'] : $battalionName;
                    ?>
                </p>
            </div>
        </div>
        </center>
        <div class="row">
            <div class="col-lg">
                <div class="card" id="card2">
                    <div class="card-body">
                        <h2 id="card2-title">In Charge Officer Of Battalion</h2>
                        
                        <br>
                        <label class="label">Name</label> 
                        <p class="data" id="iobName"><?php echo htmlspecialchars($officerInfo['fullName']); ?></p>
                        <br>
                        <label class="label">Army ID</label> 
                        <p class="data" id="armyId"><?php echo htmlspecialchars($officerInfo['Aid']); ?></p>
                        <br>
                        <label class="label">Rank</label> 
                        <p class="data" id="rank"><?php echo htmlspecialchars($officerInfo['rank']); ?></p>
                        <br>
                        <label class="label">Service Started</label> 
                        <p class="data" id="startDate"><?php 
                            if (!empty($officerInfo['starteddate'])) {
                                echo date('Y-m-d', strtotime($officerInfo['starteddate']));
                            } else {
                                echo 'N/A';
                            }
                        ?></p>
                        <br>
                        <label class="label">Service End</label> 
                        <p class="data" id="endDate"><?php 
                            if (!empty($officerInfo['enddate'])) {
                                echo date('Y-m-d', strtotime($officerInfo['enddate']));
                            } else {
                                echo 'Ongoing';
                            }
                        ?></p>
                        <br>
                        <label class="label">Service Period</label> 
                        <p class="data" id="servicePeriod"><?php echo htmlspecialchars($officerInfo['serviceperiod']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg">
                <div class="card" id="card3">
                    <div class="card-body">
                        <label class="label">Regiment ID</label> 
                        <p class="regiment-data" id="regiment-id"><?php echo isset($regimentInfo['regiment_id']) ? htmlspecialchars($regimentInfo['regiment_id']) : htmlspecialchars($regimentId); ?></p>
                        <br>
                        <label class="label">Regiment Name</label> 
                        <p class="regiment-data" id="regimentName"><?php echo isset($regimentInfo['regiment_name']) ? htmlspecialchars($regimentInfo['regiment_name']) : 'Commando Regiment'; ?></p>
                    </div>
                </div>
                <div class="card" id="card4">
                    <div class="card-body">
                        <label class="label">Battalion ID</label> 
                        <p class="battalion-data" id="battalion_id"><?php echo isset($battalionInfo['battalion_id']) ? htmlspecialchars($battalionInfo['battalion_id']) : htmlspecialchars($battalionId); ?></p>
                        <br>
                        <label class="label">Battalion Name</label> 
                        <p class="battalion-name-display">
                            <?php 
                                // Format as "Battalion Number + Regiment Name"
                                echo isset($battalionInfo['battalion_name']) ? htmlspecialchars($battalionInfo['battalion_name']) : htmlspecialchars($battalionName);
                            ?>
                        </p>
                        <br>
                        <label class="label">Active Personnel</label> 
                        <p class="battalion-data" id="bt_personnel"><?php echo htmlspecialchars($activePersonnel); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to check if database was updated successfully
        function checkDatabase() {
            <?php if($updateResult): ?>
                console.log("Database updated successfully with active personnel: <?php echo $activePersonnel; ?>");
            <?php else: ?>
                console.log("Database update failed or no changes needed");
            <?php endif; ?>
        }
    </script>
</body>
</html>