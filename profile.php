
<?php
session_start();

if (!isset($_SESSION['Aid'])) {
    header("Location: Final/login.php");
    exit();
}

$armyid = $_SESSION['Aid'];

include 'dbconnection.php';
// Query for personal and military info from soldetails table
$sql = "SELECT Aid,nic,fullName,dob,rsDivition,bGroup, regiment_id, battalion_id ,rank,joinDate,position,assignDate,profile
        FROM soldetails 
        WHERE Aid = '$armyid'";
$result = mysqli_query($con, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    // Personal Info
    $profileImg = "/Final/Profile/" . $row['profile'];
    $fullname = $row['fullName'];
    $dob = $row['dob'];
    $rsd = $row['rsDivition'];
    $nic = $row['nic'];
    
    // Military Info
    $armyId = $row['Aid'];
    $joinedDate = $row['joinDate'];
    $rank = $row['rank'];
    $position = $row['position'];
    $positionAssignedDate = $row['assignDate'];
    $regiment = $row['regiment_id'];
    $battalion = $row['battalion_id'];
    $bloodGroup = $row['bGroup'];
} else {
    
     $positionAssignedDate= "Not Found";
}

// Query for education qualifications from soleducation table
$education_sql = "SELECT education FROM soleducation WHERE Aid = '$armyid'";
$education_result = mysqli_query($con, $education_sql);
$educationalQualifications = [];

if ($education_result) {
    while ($education_row = mysqli_fetch_assoc($education_result)) {
        $educationalQualifications[] = $education_row['education'];
    }
}

// Initialize dependent variables
$mother_name = $mother_nic = "Not Found";
$father_name = $father_nic = "Not Found";
$spouse_name = $spouse_nic = "Not Found";

// Query for dependent details
$dependent_sql = "SELECT * FROM dependent WHERE Aid = '$armyid'";
$dependent_result = mysqli_query($con, $dependent_sql);

if ($dependent_result) {
    while ($dependent_row = mysqli_fetch_assoc($dependent_result)) {
        $relationship = strtolower($dependent_row['dependent_type']);
        $dependent_name = $dependent_row['name'];
        $dependent_nic = $dependent_row['NIC'];
        
        switch ($relationship) {
            case 'mother':
                $mother_name = $dependent_name;
                $mother_nic = $dependent_nic;
                break;
            case 'father':
                $father_name = $dependent_name;
                $father_nic = $dependent_nic;
                break;
            case 'spouse':
                $spouse_name = $dependent_name;
                $spouse_nic = $dependent_nic;
                break;
        }
    }
}

mysqli_close($con);
?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
    <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
    <style>
        header{
            background-image: url(photo/headerbg.jpg);
            background-size: cover;
            background-attachment: fixed;
            padding: 300px;
        }
        
        #sideTab{
            position: fixed;
            top: 50%;
            left: 0;
            background-color: skyblue;
            color: black;
            padding: 10px;
            cursor: auto;
            z-index: 100;
            transform: translateY(-50%);
            border-radius: 0 5px 5px 0;
        }

        .side-menu{
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #444;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 60px;
            z-index: 1000;
        }

        .side-menu a{
            padding: 10px 20px;
            display: block;
            color: white;
            font-family: 'Helvetica';
            font-weight: 500;
            text-decoration: none;
            font-size: 18px;
        }

        .side-menu a:hover{
            background-color: #575757;
        }

        .close-btn{
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            color: white;
        }

        .container{
            margin-top: 150px;
        }

        h1{
            color: white;
            font-family: 'Recoleta Regular';
            font-size: 50px;
        }

        .card{
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .card:hover{
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        #card-top{
            width: 700;
            height: 800;
            border-color: greenyellow;
            border-width: 8px;
        }
        
        #card-center{
            width: 700;
            height: 1000;
            border-color: maroon;
            border-width: 8px;
        }

        #card-bottom{
            width: 700;
            height: 800;
            border-color: green;
            border-width: 8px;
        }

        #card1-title{
            font-family: 'Recoleta Regular';
            font-size: 60px;
            color: darkblue;
        }

        #card2-title{
            font-family: 'Recoleta Regular';
            font-size: 60px;
            color: darkblue;
        }

        #card3-title{
            font-family: 'Recoleta Regular';
            font-size: 60px;
            color: darkblue;
        }


        .profile-img{
            text-align: center;
            width: 150;
            height: 150;
            border-radius: 50px;
            object-fit: cover;
            background-color: silver;
        }

        .label{
            text-align: left;
            font-size: 20px;
            font-weight: bold;
            font-family: 'Roboto';
        }

        .data{
            font-family: 'Tahoma';
        }
    </style>
</head>
<body>
    <header>
        <h1>Soldier's Portal</h1>
        <div id="sideTab" onclick="openMenu()">☰</div>  
        
        <div id="sideMenu" class="side-menu">
            <a href="javascript:void(0)" class="close-btn" onclick="closeMenu()">×</a>
            <a href="../Final/home.html">Home</a>
            <a href="../Final/regiment.php">Regiment</a>
            <a href="../Final/battalion.php">Battalion</a>
                  
            <?php
                // this is only visible to admins because they only can insert data to the database 
                if (isset($_SESSION['position'])) {
                    if ($_SESSION['position'] == 'Admin') {
                        echo '<a href="../Final/adminPanel.html">Admin Panel</a>';
                        echo '<a href="../Final/report.html">View Report</a>';
                    }
                }
            ?>
            <a href="../Final/logout.php">Log Out</a>

        </div>
    </header>

    <div class="container">
       <center><div class="card" id="card-top">
            <div class="card-body">
                <h2 id="card1-title">Personal Info</h2>
                <img src="<?php echo $profileImg; ?>" alt="Profile Image" width="150" height="150">
                <br>

                <label class="label">Full Name</label>
                <p class="data" id="full-name"><?php echo htmlspecialchars($fullname); ?></p>

                <br>
                <label class="label">Birth Date</label>
                <p class="data" id="dob"><?php echo htmlspecialchars($dob); ?></p>

                <br>
                <label class="label">Regional Secretariat Division</label>
                <p class="data" id="rsd"><?php echo htmlspecialchars($rsd); ?></p>

                <br>
                <label class="label">NIC</label>
                <p class="data" id="nic"><?php echo htmlspecialchars($nic); ?></p>

                <br>
            </div> 
        </div>
        </center>
    </div>
    <div class="container">
    <center><div class="card" id="card-center">
        <div class="card-body">
            <h2 id="card2-title">Military Info</h2>
            <br>
            <label class="label">Army ID</label> 
            <p class="data" id="armyId"><?php echo htmlspecialchars($armyId); ?></p>
            <br>
            <label class="label">Joined Date</label> 
            <p class="data" id="joinedDate"><?php echo htmlspecialchars($joinedDate); ?></p>
            <br>
            <label class="label">Rank</label> 
            <p class="data" id="rank"><?php echo htmlspecialchars($rank); ?></p>
            <br>
            <label class="label">Position</label> 
            <p class="data" id="position"><?php echo htmlspecialchars($position); ?></p>
            <br>
            <label class="label">Position Assigned Date</label> 
            <p class="data" id="assignedDate"><?php echo htmlspecialchars($positionAssignedDate); ?></p>
            <br>
            <label class="label">Regiment</label> 
            <p class="data" id="regiment"><?php echo htmlspecialchars($regiment); ?></p>
            <br>
            <label class="label">Battalion</label> 
            <p class="data" id="battalion"><?php echo htmlspecialchars($battalion); ?></p>
            <br>
            <label class="label">Blood Group</label> 
            <p class="data" id="bg"><?php echo htmlspecialchars($bloodGroup); ?></p>
            <br>
            <label class="label">Educational Qualifications</label> 
            <p class="data" id="edu">
                <?php 
                if (!empty($educationalQualifications)) {
                    //implode use to join the arry elements with string
                    echo htmlspecialchars(implode(", ", $educationalQualifications));
                } else {
                    echo "Not Found";
                }
                ?>
            </p>
        </div>
    </div></center>
</div>
    <div class="container">
    <center>
        <div class="card" id="card-bottom">
            <div class="card-body">
                <h2 id="card3-title">Dependents' Details</h2>
                <br>
                <label class="label">Mother's Name</label> 
                <p class="data" id="mother-name"><?php echo htmlspecialchars($mother_name); ?></p>
                <br>
                <label class="label">Mother's NIC</label> 
                <p class="data" id="mother-nic"><?php echo htmlspecialchars($mother_nic); ?></p>
                <br>
                <label class="label">Father's Name</label> 
                <p class="data" id="father-name"><?php echo htmlspecialchars($father_name); ?></p>
                <br>
                <label class="label">Father's NIC</label> 
                <p class="data" id="father-nic"><?php echo htmlspecialchars($father_nic); ?></p>
                <br>
                <label class="label">Spouse's Name</label> 
                <p class="data" id="spouse-name"><?php echo htmlspecialchars($spouse_name); ?></p>
                <br>
                <label class="label">Spouse's NIC</label> 
                <p class="data" id="spouse-id"><?php echo htmlspecialchars($spouse_nic); ?></p> 
                <br>
            </div>
        </div>
    </center>
</div>
    <script type="text/javascript">
        function openMenu(){
            document.getElementById("sideMenu").style.width = "250px";
        }

        function closeMenu(){
            document.getElementById("sideMenu").style.width = "0";
        }
    </script>
</body>
</html>