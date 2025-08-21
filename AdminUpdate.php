<?php
include 'dbconnection.php';

$row = null;
$Aid = "";
$educationData = [];

// Function to calculate service period
function calculateServicePeriod($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    
    $years = $interval->y;
    $months = $interval->m;
    $days = $interval->d;
    
    $period = "";
    if ($years > 0) {
        $period .= $years . " year" . ($years > 1 ? "s" : "");
    }
    if ($months > 0) {
        if ($period != "") $period .= ", ";
        $period .= $months . " month" . ($months > 1 ? "s" : "");
    }
    if ($days > 0) {
        if ($period != "") $period .= ", ";
        $period .= $days . " day" . ($days > 1 ? "s" : "");
    }
    
    return $period != "" ? $period : "0 days";
}

// Get regiments data
$regiments = [];
$regQuery = "SELECT regiment_id, regiment_name FROM regiment";
$regResult = mysqli_query($con, $regQuery);
if ($regResult) {
    while ($regRow = mysqli_fetch_assoc($regResult)) {
        $regiments[$regRow['regiment_id']] = $regRow['regiment_name'];
    }
}

// Get battalions data
$battalions = [];
$batQuery = "SELECT regiment_id, battalion_id, battalion_name FROM battalion";
$batResult = mysqli_query($con, $batQuery);
if ($batResult) {
    while ($row = mysqli_fetch_assoc($batResult)) {
        if (!isset($battalions[$row['regiment_id']])) {
            $battalions[$row['battalion_id']] = [];
        }
        $battalions[$row['battalion_id']] = $row['battalion_name'];
    }
}

// Handle search request
if (isset($_POST['search'])) {
    $Aid = $_POST['search_id'];

    $stmt = $con->prepare("SELECT * FROM soldetails WHERE Aid=?");
    $stmt->bind_param("s", $Aid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Get education qualifications
        $eduStmt = $con->prepare("SELECT * FROM soleducation WHERE Aid=?");
        $eduStmt->bind_param("s", $Aid);
        $eduStmt->execute();
        $eduResult = $eduStmt->get_result();
        
        while ($eduRow = $eduResult->fetch_assoc()) {
            $educationData[] = $eduRow;
        }
        $eduStmt->close();
    } else {
        echo "<script>alert('ID not found in the database!');</script>";
    }
    $stmt->close();
}

// Handle update request
if (isset($_POST['btnSubmit'])) {
    $Aid = $_POST["txtArmyid"];
    $nic = $_POST["txtnic"];
    $name = $_POST["txtFname"];
    $dob = $_POST["txtbirth"];
    $gender = $_POST["gender"];
    $address = $_POST["txtadd"];
    $mail = $_POST["txtmail"];
    $tele = $_POST["txtnumber"];
    $rsdiv = $_POST["txtgram"];
    $marry = $_POST["marry"];
    $bgroup = $_POST["combo"];
    $password = $_POST["txtpassword"];
    $regiment = $_POST["comboRegiment"];
    $battalion = $_POST["comboBattalion"];
    $rank = $_POST["comboR"];
    $jdate = $_POST["txtJoinDate"];
    $position = $_POST["comboP"];
    $asdate = $_POST["txtAssingDate"];
    $eduq = $_POST["textEQ"];
    $status = $_POST["comboStatus"];
    
    // Get current position before update
    $currentPositionStmt = $con->prepare("SELECT position FROM soldetails WHERE Aid=?");
    $currentPositionStmt->bind_param("s", $Aid);
    $currentPositionStmt->execute();
    $currentPositionResult = $currentPositionStmt->get_result();
    $currentPosition = "";
    if ($currentPositionResult && $currentPositionResult->num_rows > 0) {
        $currentPositionData = $currentPositionResult->fetch_assoc();
        $currentPosition = $currentPositionData['position'];
    }
    $currentPositionStmt->close();
    
    // Default to the current profile picture if not changed
    $stmt = $con->prepare("SELECT profile FROM soldetails WHERE Aid=?");
    $stmt->bind_param("s", $Aid);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = '';
   
    if ($result && $result->num_rows > 0) {
        $currentData = $result->fetch_assoc();
        $profile = $currentData['profile'];
    }
    $stmt->close();
   
    // Handle file upload if a new file is selected
    if (isset($_FILES["txtphoto"]) && $_FILES["txtphoto"]["error"] == 0) {
        $targetDir = "Profile/";
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = basename($_FILES["txtphoto"]["name"]);
        $targetFile = $targetDir . $fileName;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["txtphoto"]["tmp_name"]);
        if ($check !== false) {
            // Try to upload file
            if (move_uploaded_file($_FILES["txtphoto"]["tmp_name"], $targetFile)) {
                $profile = $fileName;
            } else {
                echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
            }
        } else {
            echo "<script>alert('File is not an image.');</script>";
        }
    }

    // Check if all required fields are set
    if(empty($Aid)) {
        echo "<script>alert('Army ID is required!');</script>";
    } else {
        // Start transaction
        $con->begin_transaction();
        
        try {
            // Update soldetails table
            $stmt = $con->prepare("UPDATE soldetails SET 
                nic=?, fullName=?, dob=?, gender=?, address=?, email=?, rsDivition=?, maritalStatus=?, contact=?, 
                bGroup=?, password=?, regiment_id=?, battalion_id=?, rank=?, joinDate=?, position=?, assignDate=?, profile=?, status=?
                WHERE Aid=?");
                
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $con->error);
            }

            $bind = $stmt->bind_param("ssssssssssssssssssss", 
                $nic, $name, $dob, $gender, $address, $mail, $rsdiv, $marry, $tele, 
                $bgroup, $password, $regiment, $battalion, $rank, $jdate, $position, $asdate, $profile, $status, $Aid);

            if ($bind === false) {
                throw new Exception("Bind failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();

            // Handle education info
            if (!empty($eduq)) {
                // First check if education record exists
                $checkEdu = $con->prepare("SELECT * FROM soleducation WHERE Aid=?");
                $checkEdu->bind_param("s", $Aid);
                $checkEdu->execute();
                $eduExists = $checkEdu->get_result();
                
                if ($eduExists->num_rows > 0) {
                    // Update existing education record
                    $eduUpdate = $con->prepare("UPDATE soleducation SET education=? WHERE Aid=?");
                    $eduUpdate->bind_param("ss", $eduq, $Aid);
                    if (!$eduUpdate->execute()) {
                        throw new Exception("Education update failed: " . $eduUpdate->error);
                    }
                    $eduUpdate->close();
                } else {
                    // Insert new education record
                    $eduInsert = $con->prepare("INSERT INTO soleducation (Aid, education) VALUES (?, ?)");
                    $eduInsert->bind_param("ss", $Aid, $eduq);
                    if (!$eduInsert->execute()) {
                        throw new Exception("Education insert failed: " . $eduInsert->error);
                    }
                    $eduInsert->close();
                }
                $checkEdu->close();
            }

            // Handle position changes
            if (!empty($currentPosition) && $currentPosition !== $position) {
                // Update end date for the previous position and calculate service period
                switch ($currentPosition) {
                    case 'Normal Soldier':
                        // Get start date for service period calculation
                        $getStartStmt = $con->prepare("SELECT starteddate FROM normalsoldier WHERE Aid=? AND enddate IS NULL");
                        $getStartStmt->bind_param("s", $Aid);
                        $getStartStmt->execute();
                        $startResult = $getStartStmt->get_result();
                        
                        if ($startResult && $startResult->num_rows > 0) {
                            $startData = $startResult->fetch_assoc();
                            $startDate = $startData['starteddate'];
                            $servicePeriod = calculateServicePeriod($startDate, $asdate);
                            
                            $updateEndStmt = $con->prepare("UPDATE normalsoldier SET enddate=?, serviceperiod=? WHERE Aid=? AND enddate IS NULL");
                            $updateEndStmt->bind_param("sss", $asdate, $servicePeriod, $Aid);
                            if (!$updateEndStmt->execute()) {
                                throw new Exception("Failed to update normalsoldier end date: " . $updateEndStmt->error);
                            }
                            $updateEndStmt->close();
                        }
                        $getStartStmt->close();
                        break;
                        
                    case 'Colonel Of the Regiment':
                        // Get start date for service period calculation
                        $getStartStmt = $con->prepare("SELECT starteddate FROM colonelofregiment WHERE Aid=? AND enddate IS NULL");
                        $getStartStmt->bind_param("s", $Aid);
                        $getStartStmt->execute();
                        $startResult = $getStartStmt->get_result();
                        
                        if ($startResult && $startResult->num_rows > 0) {
                            $startData = $startResult->fetch_assoc();
                            $startDate = $startData['starteddate'];
                            $servicePeriod = calculateServicePeriod($startDate, $asdate);
                            
                            $updateEndStmt = $con->prepare("UPDATE colonelofregiment SET enddate=?, serviceperiod=? WHERE Aid=? AND enddate IS NULL");
                            $updateEndStmt->bind_param("sss", $asdate, $servicePeriod, $Aid);
                            if (!$updateEndStmt->execute()) {
                                throw new Exception("Failed to update colonelofregiment end date: " . $updateEndStmt->error);
                            }
                            $updateEndStmt->close();
                        }
                        $getStartStmt->close();
                        break;

                    case 'Battalion Officer':
                        // Get start date for service period calculation
                        $getStartStmt = $con->prepare("SELECT starteddate FROM battalionofficer WHERE Aid=? AND enddate IS NULL");
                        $getStartStmt->bind_param("s", $Aid);
                        $getStartStmt->execute();
                        $startResult = $getStartStmt->get_result();
                        
                        if ($startResult && $startResult->num_rows > 0) {
                            $startData = $startResult->fetch_assoc();
                            $startDate = $startData['starteddate'];
                            $servicePeriod = calculateServicePeriod($startDate, $asdate);
                            
                            $updateEndStmt = $con->prepare("UPDATE battalionofficer SET enddate=?, serviceperiod=? WHERE Aid=? AND enddate IS NULL");
                            $updateEndStmt->bind_param("sss", $asdate, $servicePeriod, $Aid);
                            if (!$updateEndStmt->execute()) {
                                throw new Exception("Failed to update battalionofficer end date: " . $updateEndStmt->error);
                            }
                            $updateEndStmt->close();
                        }
                        $getStartStmt->close();
                        break;

                    case 'Admin':
                        // Get start date for service period calculation
                        $getStartStmt = $con->prepare("SELECT starteddate FROM admin WHERE Aid=? AND enddate IS NULL");
                        $getStartStmt->bind_param("s", $Aid);
                        $getStartStmt->execute();
                        $startResult = $getStartStmt->get_result();
                        
                        if ($startResult && $startResult->num_rows > 0) {
                            $startData = $startResult->fetch_assoc();
                            $startDate = $startData['starteddate'];
                            $servicePeriod = calculateServicePeriod($startDate, $asdate);
                            
                            $updateEndStmt = $con->prepare("UPDATE admin SET enddate=?, serviceperiod=? WHERE Aid=? AND enddate IS NULL");
                            $updateEndStmt->bind_param("sss", $asdate, $servicePeriod, $Aid);
                            if (!$updateEndStmt->execute()) {
                                throw new Exception("Failed to update admin end date: " . $updateEndStmt->error);
                            }
                            $updateEndStmt->close();
                        }
                        $getStartStmt->close();
                        break;
                }
            }

            // Insert new position record with start date
            switch ($position) {
                case 'Normal Soldier':
                    $insertStmt = $con->prepare("INSERT INTO normalsoldier (Aid, starteddate) VALUES (?, ?)");
                    $insertStmt->bind_param("ss", $Aid, $asdate);
                    if (!$insertStmt->execute()) {
                        throw new Exception("Failed to insert normalsoldier record: " . $insertStmt->error);
                    }
                    $insertStmt->close();
                    break;
                    
                case 'Colonel Of the Regiment':
                    $insertStmt = $con->prepare("INSERT INTO colonelofregiment (Aid, starteddate, regiment_id) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("sss", $Aid, $asdate, $regiment);
                    if (!$insertStmt->execute()) {
                        throw new Exception("Failed to insert colonelofregiment record: " . $insertStmt->error);
                    }
                    $insertStmt->close();
                    break;

                 case 'Battalion Officer':
                    $insertStmt = $con->prepare("INSERT INTO battalionofficer (Aid, starteddate, battalion_id) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("sss", $Aid, $asdate, $battalion);
                    if (!$insertStmt->execute()) {
                        throw new Exception("Failed to insert battalionofficer record: ".$insertStmt->error);
                    }
                    $insertStmt->close();
                    break;
                    
                case 'Admin':
                    $insertStmt = $con->prepare("INSERT INTO admin (Aid, starteddate) VALUES (?, ?)");
                    $insertStmt->bind_param("ss", $Aid, $asdate);
                    if (!$insertStmt->execute()) {
                        throw new Exception("Failed to insert admin record: " . $insertStmt->error);
                    }
                    $insertStmt->close();
                    break;
                    
                default:
                    throw new Exception("Invalid position type: " . htmlspecialchars($position));
            }
            
            // Commit transaction
            $con->commit();
            
            echo "<script>alert('Record updated successfully!');</script>";
            
            // Refresh the data after update
            $stmt = $con->prepare("SELECT * FROM soldetails WHERE Aid=?");
            $stmt->bind_param("s", $Aid);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Get updated education qualifications
            $eduStmt = $con->prepare("SELECT * FROM soleducation WHERE Aid=?");
            $eduStmt->bind_param("s", $Aid);
            $eduStmt->execute();
            $eduResult = $eduStmt->get_result();
            $educationData = [];
            
            while ($eduRow = $eduResult->fetch_assoc()) {
                $educationData[] = $eduRow;
            }
            $eduStmt->close();
            $stmt->close();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $con->rollback();
            echo "<script>alert('Error updating record: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User Details</title>
    <!-- Bootstrap CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        
        // Initialize the form when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Set the initial regiment and battalion values if they exist
            const regimentSelect = document.getElementById('comboRegiment');
            const battalionSelect = document.getElementById('comboBattalion');
            const selectedRegiment = '<?php echo isset($row['regiment_id']) ? $row['regiment_id'] : ''; ?>';
            const selectedBattalion = '<?php echo isset($row['battalion_id']) ? $row['battalion_id'] : ''; ?>';
            
            if (selectedRegiment) {
                regimentSelect.value = selectedRegiment;
                updateBattalions(); // This will populate the battalion dropdown
                
                // After populating, set the selected battalion
                if (selectedBattalion) {
                    setTimeout(function() {
                        battalionSelect.value = selectedBattalion;
                    }, 100);
                }
            }
            
            // Set the selected status
            const statusSelect = document.getElementById('comboStatus');
            const selectedStatus = '<?php echo isset($row['status']) ? $row['status'] : ''; ?>';
            if (selectedStatus) {
                statusSelect.value = selectedStatus;
            }
        });
    </script>
</head>
<body>
    
<header>
    <!-- place navbar here -->
    <nav class="bg-dark" style="height: 10px; width: 100%;">
    </nav>
    <nav class="bg-dark" data-bs-theme="dark" style="height: 60px; width: 100%;">
        <ul class="nav justify-content-center mb-2 nav-tabs"><br>
            <li class="nav-item">
                <a class="nav-link" href="adminPanel.html">Home</a>
            </li> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;    
            <li class="nav-item">
                <a class="nav-link" href="AdminInsert.php">Insert</a>
            </li> &nbsp; &nbsp; &nbsp; &nbsp;
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="AdminUpdate.php"><b>Update</b></a>
            </li> &nbsp; &nbsp; &nbsp; &nbsp;
            <li class="nav-item">
                <a class="nav-link" href="AdminView.php">View</a>
            </li> &nbsp; &nbsp; &nbsp; 
        </ul>
    </nav>
</header>

<main>
    <section class="regform">
        <div class="container py-5">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-lg-8 col-xl-6">
                    <div class="card rounded-3">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="pb-md-0 mb-md-5 px-md-2">Update User Details</h3>
                        <div class ="abc">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="search_id" class="form-label">Enter Army ID to Search</label>
                                    <input type="text" name="search_id" id="search_id" class="form-control" required value="<?php echo isset($Aid) ? $Aid : ''; ?>">
                                </div>
                                <button type="submit" name="search" class="btn btn-primary mb-4">Search</button>
                            </form>

                            <?php if ($row): ?>
                            <form method="post" action="" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="text" name="txtArmyid2" disabled class="form-control" placeholder="Enter Army ID" required value="<?php echo isset($row['Aid']) ? $row['Aid'] : ''; ?>" />
                                            <label class="form-label">Army ID</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="txtArmyid" class="form-control" value="<?php echo isset($row['Aid']) ? $row['Aid'] : ''; ?>" />
                                
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="text" name="txtnic" class="form-control" placeholder="Enter NIC" required value="<?php echo isset($row['nic']) ? $row['nic'] : ''; ?>" />
                                            <label class="form-label">NIC</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-outline">
                                        <input type="text" name="txtFname" class="form-control" placeholder="Enter Full Name" required value="<?php echo isset($row['fullName']) ? $row['fullName'] : ''; ?>" />
                                        <label class="form-label">Full Name</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="date" name="txtbirth" class="form-control" required value="<?php echo isset($row['dob']) ? $row['dob'] : ''; ?>" />
                                            <label class="form-label">Date of Birth</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div>
                                            <input type="radio" name="gender" value="Male" <?php echo (isset($row['gender']) && $row['gender'] == 'Male') ? 'checked' : ''; ?> required /> Male &nbsp;
                                            <input type="radio" name="gender" value="Female" <?php echo (isset($row['gender']) && $row['gender'] == 'Female') ? 'checked' : ''; ?> required /> Female
                                        </div>
                                        <label class="form-label">Gender</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-outline">
                                        <input type="text" name="txtadd" class="form-control" placeholder="Enter Address" required value="<?php echo isset($row['address']) ? $row['address'] : ''; ?>" />
                                        <label class="form-label">Address</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="email" name="txtmail" class="form-control" placeholder="Enter Email Address" required value="<?php echo isset($row['email']) ? $row['email'] : ''; ?>" />
                                            <label class="form-label">Email</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="text" name="txtnumber" class="form-control" placeholder="Enter Contact Number" required value="<?php echo isset($row['contact']) ? $row['contact'] : ''; ?>" />
                                            <label class="form-label">Contact Number</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="text" name="txtgram" class="form-control" placeholder="Regional Secratariat Division" required value="<?php echo isset($row['rsDivition']) ? $row['rsDivition'] : ''; ?>" />
                                            <label class="form-label">Regional Secratariat Division</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div>
                                            <input type="radio" name="marry" value="Married" <?php echo (isset($row['maritalStatus']) && $row['maritalStatus'] == 'Married') ? 'checked' : ''; ?> required /> Married &nbsp;
                                            <input type="radio" name="marry" value="Unmarried" <?php echo (isset($row['maritalStatus']) && $row['maritalStatus'] == 'Unmarried') ? 'checked' : ''; ?> required /> Unmarried
                                        </div>
                                        <label class="form-label">Marital Status</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="combo" id="combo" class="form-control" required>
                                            <option value="">--- Select Blood Group ---</option>
                                            <option value="O-" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                            <option value="O+" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                            <option value="B-" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                            <option value="B+" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                            <option value="A-" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                            <option value="A+" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                            <option value="AB-" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                            <option value="AB+" <?php echo (isset($row['bGroup']) && $row['bGroup'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        </select>
                                        <label class="form-label" id="lblcombo">Blood Group</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="password" name="txtpassword" class="form-control" placeholder="Enter Password" required value="<?php echo isset($row['password']) ? $row['password'] : ''; ?>" />
                                            <label class="form-label">Password</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Education fields -->
                                <div class="mb-4">
                                    <div class="form-outline">
                                        <textarea id="textEQ" name="textEQ" class="form-control" rows="3" placeholder="Enter Education Qualifications"><?php 
                                            echo !empty($educationData) ? $educationData[0]['education'] : ''; 
                                        ?></textarea>
                                        <label class="form-label">Education Qualification</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboRegiment" id="comboRegiment" class="form-control" required onchange="updateRegiment()">
                                            <option value="">--- Select Regiment ---</option>
                                            <?php
                                                foreach($regiments as $regId => $regName) {
                                                    $selected = (isset($row['regiment_id']) && $row['regiment_id'] == $regId) ? 'selected' : '';
                                                    echo "<option value=\"$regId\" $selected>$regName</option>";
                                                }
                                            ?>
                                        </select>
                                        <label class="form-label" id="lblRegiment">Regiment</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <select name="comboBattalion" id="comboBattalion" class="form-control" required onchange="updateBattalions()">
                                            <option value="">--- Select Battalion ---</option>
                                            <?php
                                                foreach($battalions as $batId => $batName) {
                                                    $selected = (isset($row['battalion_id']) && $row['battalion_id'] == $batId) ? 'selected' : '';
                                                    echo "<option value=\"$batId\" $selected>$batName</option>";
                                                }
                                            ?>
                                        </select>
                                        <label class="form-label" id="lblBattalion">Battalion</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboR" id="comboR" class="form-control" required>
                                            <option value="">--- Select Rank ---</option>
                                            <option value="Private (ordinary soldier)" <?php echo (isset($row['rank']) && $row['rank'] == 'Private (ordinary soldier)') ? 'selected' : ''; ?>>Private (ordinary soldier)</option>
                                            <option value="Lance Corporal" <?php echo (isset($row['rank']) && $row['rank'] == 'Lance Corporal') ? 'selected' : ''; ?>>Lance Corporal</option>
                                            <option value="Corporal" <?php echo (isset($row['rank']) && $row['rank'] == 'Corporal') ? 'selected' : ''; ?>>Corporal</option>
                                            <option value="Sergeant" <?php echo (isset($row['rank']) && $row['rank'] == 'Sergeant') ? 'selected' : ''; ?>>Sergeant</option>
                                            <option value="Staff Sergeant" <?php echo (isset($row['rank']) && $row['rank'] == 'Staff Sergeant') ? 'selected' : ''; ?>>Staff Sergeant</option>
                                            <option value="Warrant Officer 2nd Class" <?php echo (isset($row['rank']) && $row['rank'] == 'Warrant Officer 2nd Class') ? 'selected' : ''; ?>>Warrant Officer 2nd Class</option>
                                            <option value="Warrant Officer 1st Class" <?php echo (isset($row['rank']) && $row['rank'] == 'Warrant Officer 1st Class') ? 'selected' : ''; ?>>Warrant Officer 1st Class</option>
                                            <option value="Second Lieutenant" <?php echo (isset($row['rank']) && $row['rank'] == 'Second Lieutenant') ? 'selected' : ''; ?>>Second Lieutenant</option>
                                            <option value="Lieutenant" <?php echo (isset($row['rank']) && $row['rank'] == 'Lieutenant') ? 'selected' : ''; ?>>Lieutenant</option>
                                            <option value="Captain" <?php echo (isset($row['rank']) && $row['rank'] == 'Captain') ? 'selected' : ''; ?>>Captain</option>
                                            <option value="Major" <?php echo (isset($row['rank']) && $row['rank'] == 'Major') ? 'selected' : ''; ?>>Major</option>
                                            <option value="Lieutenant Colonel" <?php echo (isset($row['rank']) && $row['rank'] == 'Lieutenant Colonel') ? 'selected' : ''; ?>>Lieutenant Colonel</option>
                                            <option value="Colonel" <?php echo (isset($row['rank']) && $row['rank'] == 'Colonel') ? 'selected' : ''; ?>>Colonel</option>
                                            <option value="Brigadier" <?php echo (isset($row['rank']) && $row['rank'] == 'Brigadier') ? 'selected' : ''; ?>>Brigadier</option>
                                            <option value="Major General" <?php echo (isset($row['rank']) && $row['rank'] == 'Major General') ? 'selected' : ''; ?>>Major General</option>
                                            <option value="Lieutenant General" <?php echo (isset($row['rank']) && $row['rank'] == 'Lieutenant General') ? 'selected' : ''; ?>>Lieutenant General</option>
                                            <option value="General" <?php echo (isset($row['rank']) && $row['rank'] == 'General') ? 'selected' : ''; ?>>General</option>
                                        </select>
                                        <label class="form-label" id="lblcomboR">Rank</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="date" name="txtJoinDate" class="form-control" required value="<?php echo isset($row['joinDate']) ? $row['joinDate'] : ''; ?>" />
                                            <label class="form-label">Join Date</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboP" id="comboP" class="form-control" required>
                                            <option value="">--- Select Position ---</option>
                                            <option value="Normal Soldier" <?php echo (isset($row['position']) && $row['position'] == 'Normal Soldier') ? 'selected' : ''; ?>>Normal Soldier</option>
                                            <option value="Battalion Officer" <?php echo (isset($row['position']) && $row['position'] == 'Battalion Officer') ? 'selected' : ''; ?>>Battalion Officer</option>
                                            <option value="Colonel Of the Regiment" <?php echo (isset($row['position']) && $row['position'] == 'Colonel Of the Regiment') ? 'selected' : ''; ?>>Colonel of the Regiment</option>
                                            <option value="Admin" <?php echo (isset($row['position']) && $row['position'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <label class="form-label" id="lblcomboP">Position</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="form-outline">
                                            <input type="date" name="txtAssingDate" class="form-control" value="<?php echo isset($row['assignDate']) ? $row['assignDate'] : ''; ?>" />
                                            <label class="form-label">Assign Date</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-8 mb-4">
                                        <div class="form-outline">
                                            <input type="file" name="txtphoto" class="form-control" accept="image/*" />
                                            <label class="form-label">Profile Picture (Leave empty to keep current)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-4 text-center">
                                        <?php if (!empty($row['profile'])): ?>
                                            <img src="Profile/<?php echo htmlspecialchars($row['profile']); ?>" alt="Profile" class="img-fluid rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboStatus" id="comboStatus" class="form-control" required>
                                            <option value="">--- Select Status ---</option>
                                            <option value="Yes" <?php echo (isset($row['status']) && $row['status'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="No" <?php echo (isset($row['status']) && $row['status'] == 'No') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                        <label class="form-label" id="lblcomboStatus">Status</label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-lg" name="btnSubmit">Update Record</button>
                            </form>
                        </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

</body>
</html>

<script src="bootstrap/js/bootstrap.min.js"></script>