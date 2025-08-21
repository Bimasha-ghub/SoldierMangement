<!doctype html>
<html lang="en">
<head>
    <title>Insert Soldier</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="style.css">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
        <header>
            <!-- place navbar here -->
            <nav class="bg-dark "  style="height: 10px; width: 100%;">
            </nav>
             <nav class=" bg-dark" data-bs-theme="dark" style="height: 60px; width: 100%;">
                <ul class="nav justify-content-center mb-2 nav-tabs"><br>
                    <li class="nav-item">
                        <a class="nav-link" href="adminPanel.html">Home</a>
                    </li> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp;    
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="AdminInsert.php"><b>Insert</b></a>
                    </li> &nbsp; &nbsp; &nbsp; &nbsp;
                    <li class="nav-item">
                        <a class="nav-link" href="AdminUpdate.php">Update</a>
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
                            <h3 class="pb-md-0 mb-md-5 px-md-2">Insert New Soldier</h3>
<?php
include 'dbconnection.php';
$regiments = [];
$regQuery = "SELECT regiment_id, regiment_name FROM regiment";
$regResult = mysqli_query($con, $regQuery);
if ($regResult) {
    while ($row = mysqli_fetch_assoc($regResult)) {
        $regiments[$row['regiment_id']] = $row['regiment_name'];
    }
}

// Fetch battalions grouped by regiment_id
$battalions = [];
$batQuery = "SELECT battalion_id, battalion_name FROM battalion";
$batResult = mysqli_query($con, $batQuery);
if ($batResult) {
    while ($row = mysqli_fetch_assoc($batResult)) {
        if (!isset($battalions[$row['battalion_id']])) {
            $battalions[$row['battalion_id']] = [];
        }
        $battalions[$row['battalion_id']]= $row['battalion_name'];
    }
}

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
    $ran = $_POST["comboR"];
    $jdate = $_POST["txtJoinDate"];
    $position = $_POST["comboP"];
    $asdate = $_POST["txtAssingDate"];
    $eduq = $_POST["textEQ"];
    $image = $_FILES["txtphoto"]["name"];
    $status = $_POST["comboStatus"];

    $birthYear = date('Y', strtotime($dob));
    $nicYear = substr($nic, 0, 4);
    $birthDate = new DateTime($dob);
    $joinDate = new DateTime($jdate);
    $ageAtJoin = $birthDate->diff($joinDate)->y;                                   

    if ($nicYear != $birthYear || $ageAtJoin < 18 || $ageAtJoin > 25) {
        echo "<div class='alert alert-danger'>NIC and DOB year mismatch or age at join is not between 18 and 25.</div>";
    } else {
        // If position is not "none", validate assign date
        if ($position != "none") {
            $assignDate = new DateTime($asdate);
            $diff = $joinDate->diff($assignDate)->y;
            
            if ($diff < 1) {
                echo "<div class='alert alert-danger'>Assign date must be at least 1 year after join date.</div>";
                goto skipInsertion;
            }
        }
        
        // Start transaction
        mysqli_begin_transaction($con);
        
        try {
            // Insert into soldetails table
            $sql1 = "INSERT INTO soldetails (
                Aid, nic, fullName, dob, gender, address, email, contact, rsDivition,
                maritalStatus, bGroup, password, regiment_id, battalion_id, rank, joinDate, position, assignDate, profile, status
            ) VALUES (
                '$Aid', '$nic', '$name', '$dob', '$gender', '$address', '$mail', '$tele', '$rsdiv',
                '$marry', '$bgroup', '$password', '$regiment', '$battalion', '$ran', '$jdate', '$position', " . 
                ($asdate ? "'$asdate'" : "NULL") . ", '$image', '$status'
            )";
            
            if (!mysqli_query($con, $sql1)) {
                throw new Exception(mysqli_error($con));
            }
            
            // Insert into soleducation table
            $sql2 = "INSERT INTO soleducation (Aid, education) 
                    VALUES ('$Aid', '$eduq')";
            
            if (!mysqli_query($con, $sql2)) {
                throw new Exception(mysqli_error($con));
            }

                // Insert into appropriate position table based on position type
            if (trim($position) != "none") {
                $position = trim($position); // Trim whitespace

                switch ($position) {
                    case 'Normal Soldier':
                        $sql3 = "INSERT INTO normalsoldier (Aid, starteddate) 
                                VALUES ('$Aid', '$asdate')";
                        break;
                    case 'Colonel of the Regiment':
                        // Insert regiment_id along with Aid and starteddate for Colonel of the Regiment
                        $sql3 = "INSERT INTO colonelofregiment (Aid, starteddate, regiment_id) 
                                VALUES ('$Aid', '$asdate', '$regiment')";
                        break;
                    case 'Battalion Officer':
                        $sql3 = "INSERT INTO battalionofficer (Aid, starteddate, battalion_id) 
                                VALUES ('$Aid', '$asdate', ''$battalion)";
                        break;
                    default:
                        throw new Exception("Invalid position type: " . htmlspecialchars($position));
                }

                if (!mysqli_query($con, $sql3)) {
                    throw new Exception(mysqli_error($con));
                }
            }

            
            // Commit transaction if all queries succeed
            mysqli_commit($con);
            
            // Upload picture
            $uploadfilepath = "Profile/" . basename($_FILES["txtphoto"]["name"]);
            if (move_uploaded_file($_FILES['txtphoto']['tmp_name'], $uploadfilepath)) {
                echo "<div class='alert alert-info'>Image uploaded successfully.</div>";
            } else {
                echo "<div class='alert alert-warning'>Image upload failed.</div>";
            }
            
            echo "<div class='alert alert-success'>Record inserted successfully.</div>";
            echo "<script>document.querySelector('form').reset();</script>";
            
        } catch (Exception $e) {
            // Rollback transaction if any query fails
            mysqli_rollback($con);
            
            if (mysqli_errno($con) == 1062) {
                preg_match("/Duplicate entry '(\d+)'/", mysqli_error($con), $matches);
                $duplicateId = isset($matches[1]) ? $matches[1] : 'unknown';
                echo "<div class='alert alert-danger'>Last entered Army ID is $duplicateId</div>";
            } else {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
    skipInsertion:
}
?>
                        <div class="abc">
                            <form method="post" action="#" enctype="multipart/form-data" onsubmit="return validation()">

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="txtArmyid" class="form-control" placeholder="Enter Army ID" required  />
                                        <label>Army ID</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="txtnic" class="form-control" placeholder="Enter NIC" required  />
                                        <label>NIC</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <input type="text" name="txtFname" class="form-control" placeholder="Enter Full Name" required />
                                    <label>Full Name</label>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="date" name="txtbirth" class="form-control" required />
                                        <label>Date of Birth</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div>
                                            <input type="radio" name="gender" value="Male" required /> Male &nbsp;
                                            <input type="radio" name="gender" value="Female" required /> Female
                                        </div>
                                        <label>Gender</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <input type="text" name="txtadd" class="form-control" placeholder="Enter Address" required />
                                    <label>Address</label>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="email" name="txtmail" class="form-control" placeholder="Enter Email Address" required />
                                        <label>Email</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="txtnumber" class="form-control" placeholder="Enter Contact Number" required />
                                        <label>Contact Number</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="txtgram" class="form-control" placeholder="Regional Secratariat Division" required />
                                        <label>Regional Secratariat Division</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div>
                                            <input type="radio" name="marry" value="Married" required /> Married &nbsp;
                                            <input type="radio" name="marry" value="Unmarried" required /> Unmarried
                                        </div>
                                        <label>Marital Status</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="combo" id="combo" class="form-control" required>
                                            <option value="">--- Select Blood Group ---</option>
                                            <option value="O-">O-</option>
                                            <option value="O+">O+</option>
                                            <option value="B-">B-</option>
                                            <option value="B+">B+</option>
                                            <option value="A-">A-</option>
                                            <option value="A+">A+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="AB+">AB+</option>
                                        </select>
                                        <label id="lblcombo">Blood Group</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="password" name="txtpassword" class="form-control" placeholder="Enter Password" required />
                                        <label>Password</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <textarea id="textEQ" name="textEQ" rows="2" cols="60" required></textarea>
                                        <label>Education Qualification</label>
                                    </div>
                                </div>

                                <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <select name="comboRegiment" id="comboRegiment" class="form-control" required onChange="updateBattalions()">
                                                <option value="">--- Select Regiment ---</option>
                                                <?php
                                                    foreach($regiments as $regId => $regName) {
                                                        echo "<option value=\"$regId\">$regName</option>";
                                                    }
                                                ?>
                                            </select>
                                            <label id="lblRegiment">Regiment</label>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <select name="comboBattalion" id="comboBattalion" class="form-control" required>
                                                <option value="">--- Select Battalion ---</option>
                                                <?php
                                                    foreach($battalions as $batid => $batName) {
                                                        echo "<option value=\"$batid\">$batName</option>";
                                                    }
                                                ?>
                                                <!-- Battalion options will be populated by JavaScript based on selected regiment -->
                                            </select>
                                            <label id="lblBattalion">Battalion</label>
                                        </div>
                                    </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboR" id="comboR" class="form-control" required>
                                            <option value="">--- Select Rank ---</option>
                                            <option value="Private (ordinary soldier)">Private (ordinary soldier)</option>
                                            <option value="Lance Corporal">Lance Corporal</option>
                                            <option value="Corporal">Corporal</option>
                                            <option value="Sergeant">Sergeant</option>
                                            <option value="Staff Sergeant">Staff Sergeant</option>
                                            <option value="Warrant Officer 2nd Class">Warrant Officer 2nd Class</option>
                                            <option value="Warrant Officer 1st Class">Warrant Officer 1st Class</option>
                                            <option value="Second Lieutenant">Second Lieutenant</option>
                                            <option value="Lieutenant">Lieutenant</option>
                                            <option value="Captain">Captain</option>
                                            <option value="Major">Major</option>
                                            <option value="Lieutenant Colonel">Lieutenant Colonel</option>
                                            <option value="Colonel">Colonel</option>
                                            <option value="Brigadier">Brigadier</option>
                                            <option value="Major General">Major General</option>
                                            <option value="Lieutenant General">Lieutenant General</option>
                                            <option value="General">General</option>
                                        </select>
                                        <label id="lblcomboR">Rank</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="date" name="txtJoinDate" id="txtJoinDate" class="form-control" required onChange="updateAssignDateMin()" />
                                        <label>Join Date</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboP" id="comboP" class="form-control" required onChange="toggleAssignDate()">
                                            <option value="">--- Select Position ---</option>
                                            <option value="Normal Soldier">Normal Soldier</option>
                                            <option value="Battalion Officer">Battalion Officer</option>
                                            <option value="Colonel of the Regiment">Colonel of the Regiment</option>
                                            <option value="Admin">Admin</option>
                                            
                                        </select>
                                        <label id="lblcomboP">Position</label>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="date" name="txtAssingDate" id="txtAssingDate" class="form-control" />
                                        <label>Assign Date</label>
                                    </div>
                                    <div class="mb-4">
                                        <input type="file" name="txtphoto" class="form-control" placeholder="Uplode Photo" required />
                                        <label>Profile Picture</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboStatus" id="comboStatus" class="form-control" required>
                                            <option value="">--- Select Status ---</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <label class="form-label" id="lblcomboStatus">Status</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success" name="btnSubmit">Submit</button>
                            </form>
                        </div>

                            <script>
                                // Battalion data from PHP
                                const battalionData = <?php echo json_encode($battalions); ?>;
                                
                                // Toggle assign date field based on position selection
                                function toggleAssignDate() {
                                    const positionSelect = document.getElementById('comboP');
                                    const assignDateInput = document.getElementById('txtAssingDate');
                                    
                                    if (positionSelect.value === "none") {
                                        assignDateInput.disabled = true;
                                        assignDateInput.value = "";
                                        assignDateInput.required = false;
                                    } else {
                                        assignDateInput.disabled = false;
                                        assignDateInput.required = true;
                                        updateAssignDateMin();
                                    }
                                }
                                
                                // Set minimum assign date to 1 year after join date
                                function updateAssignDateMin() {
                                    const joinDateInput = document.getElementById('txtJoinDate');
                                    const assignDateInput = document.getElementById('txtAssingDate');
                                    
                                    if (joinDateInput.value) {
                                        const joinDate = new Date(joinDateInput.value);
                                        const minAssignDate = new Date(joinDate);
                                        minAssignDate.setFullYear(joinDate.getFullYear() + 1);
                                        
                                        // Format date as YYYY-MM-DD for input min attribute
                                        const year = minAssignDate.getFullYear();
                                        const month = String(minAssignDate.getMonth() + 1).padStart(2, '0');
                                        const day = String(minAssignDate.getDate()).padStart(2, '0');
                                        assignDateInput.min = `${year}-${month}-${day}`;
                                        
                                        // If current value is before min, reset it
                                        if (assignDateInput.value && new Date(assignDateInput.value) < minAssignDate) {
                                            assignDateInput.value = "";
                                        }
                                    }
                                }
                                
                                // Call toggle function on page load to set initial state
                                document.addEventListener('DOMContentLoaded', function() {
                                    toggleAssignDate();
                                });
                                
                                function validation() {
                                    let combo = document.getElementById('combo');
                                    let comboR = document.getElementById('comboR');
                                    let comboP = document.getElementById('comboP');
                                    let regiment = document.getElementById('comboRegiment');
                                    let battalion = document.getElementById('comboBattalion');
                                    let dob = document.querySelector('input[name="txtbirth"]').value;
                                    let nic = document.querySelector('input[name="txtnic"]').value;
                                    let joinDate = document.querySelector('input[name="txtJoinDate"]').value;
                                    let assignDate = document.querySelector('input[name="txtAssingDate"]').value;

                                    let valid = true;

                                    document.getElementById("lblcombo").innerHTML = "Blood Group";
                                    document.getElementById("lblcomboR").innerHTML = "Rank";
                                    document.getElementById("lblcomboP").innerHTML = "Position";
                                    document.getElementById("lblRegiment").innerHTML = "Regiment";
                                    document.getElementById("lblBattalion").innerHTML = "Battalion";
                                    
                                    document.getElementById("lblcombo").style.color = "";
                                    document.getElementById("lblcomboR").style.color = "";
                                    document.getElementById("lblcomboP").style.color = "";
                                    document.getElementById("lblRegiment").style.color = "";
                                    document.getElementById("lblBattalion").style.color = "";

                                    // Required dropdowns
                                    if (combo.value === "") {
                                        document.getElementById("lblcombo").innerHTML = "Please select Blood Group";
                                        document.getElementById("lblcombo").style.color = "red";
                                        valid = false;
                                    }

                                    if (comboR.value === "") {
                                        document.getElementById("lblcomboR").innerHTML = "Please select Rank";
                                        document.getElementById("lblcomboR").style.color = "red";
                                        valid = false;
                                    }

                                    if (comboP.value === "") {
                                        document.getElementById("lblcomboP").innerHTML = "Please select Position";
                                        document.getElementById("lblcomboP").style.color = "red";
                                        valid = false;
                                    }
                                    
                                    if (regiment.value === "") {
                                        document.getElementById("lblRegiment").innerHTML = "Please select Regiment";
                                        document.getElementById("lblRegiment").style.color = "red";
                                        valid = false;
                                    }
                                    
                                    if (battalion.value === "") {
                                        document.getElementById("lblBattalion").innerHTML = "Please select Battalion";
                                        document.getElementById("lblBattalion").style.color = "red";
                                        valid = false;
                                    }

                                    // NIC year vs DOB year
                                    if (dob !== "" && nic !== "") {
                                        let nicYear = nic.substr(0, 4);
                                        let birthYear = new Date(dob).getFullYear().toString();
                                        if (nicYear !== birthYear) {
                                            alert("NIC number's birth year doesn't match Date of Birth.");
                                            valid = false;
                                        }
                                    }

                                    // Age at join check
                                    if (dob !== "" && joinDate !== "") {
                                        let dobDate = new Date(dob);
                                        let join = new Date(joinDate);
                                        let ageAtJoin = (join - dobDate) / (1000 * 60 * 60 * 24 * 365.25);

                                        if (ageAtJoin < 18 || ageAtJoin > 25) {
                                            alert("Join Date must be between 18 and 25 years after Date of Birth.");
                                            valid = false;
                                        }
                                    }
                                    
                                    
                                    // Assign date check (if position is not "none")
                                    if (comboP.value !== "none" && comboP.value !== "" && joinDate !== "" && assignDate !== "") {
                                        let joinDateObj = new Date(joinDate);
                                        let assignDateObj = new Date(assignDate);
                                        
                                        // Calculate difference in years
                                        let diffTime = assignDateObj - joinDateObj;
                                        let diffYears = diffTime / (1000 * 3600 * 24 * 365.25);
                                        
                                        if (diffYears < 1) {
                                            alert("Assign Date must be at least 1 year after Join Date.");
                                            valid = false;
                                        }
                                    }
                                    
                                    return valid;
                                }
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer></footer>

<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>