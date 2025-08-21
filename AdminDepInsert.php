<!doctype html>
<html lang="en">
<head>
    <title>Insert Dependent</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <style>


.regform{
    background: linear-gradient(#9cd769,#30b730, #088724);
}
#error{
    color: red;
    font-weight: bold;
}
#checkdep
{
    margin-left: 10px;
    margin-right: 10px;
    margin-bottom:5px;
}
#lbldep
{
    margin-right: 10px;
    margin-bottom:5px;
}
#btnsearch
{
    background-color: blue;
    color: white;
    border-radius:5px;
}
#txtsearch
{
    padding-right: 200px;
}

.search-container {
  display: flex;
  align-items: center;
  margin-bottom:50px;
}


.search-container input[type="text"] {
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin-left: -1px; 
}

.search-container button {
  padding: 8px 12px;
  background-color: #4CAF50;
  color: white;
  border: 1px solid #4CAF50;
  border-radius: 4px 0 0 4px;
  cursor: pointer;
}


.search-container input[type="text"] {
  border-radius: 0 4px 4px 0;
}
    </style>
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
                    <a class="nav-link active" aria-current="page" href="AdminDepInsert.php"><b>Insert</b></a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link" href="AdminDepUpdate.php">Update</a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link" href="AdminDepDelete.php">Delete</a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                
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
                            <h3 class="pb-md-0 mb-md-5 px-md-2">Insert Dependent Details</h3>

                            <?php
                                    include 'dbconnection.php';

                                    $row = null;
                                    $Aid = "";

                                    // Handle search request
                                    if (isset($_POST['btnsearch'])) {
                                        $Aid = $_POST['txtAid'];

                                        $stmt = $con->prepare("SELECT * FROM soldetails WHERE Aid =?");
                                        $stmt->bind_param("s", $Aid);
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        if ($result && $result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                        } else {
                                            echo "<div class='alert alert-danger'>Army ID not found!!</div>";
                                        }
                                    }

                                    if (isset($_POST['btnSubmit'])) {
                                        $Aid = $_POST["txtAid"];
                                        
                                        // Check if army ID exists first
                                        $check_stmt = $con->prepare("SELECT Aid FROM soldetails WHERE Aid = ?");
                                        $check_stmt->bind_param("s", $Aid);
                                        $check_stmt->execute();
                                        $check_result = $check_stmt->get_result();
                                        
                                        if ($check_result->num_rows === 0) {
                                            echo "<div class='alert alert-danger'>Army ID not found!</div>";
                                        } else {
                                            $successCount = 0;
                                            $errorMessages = [];
                                            
                                            // Start transaction for atomic operations
                                            $con->begin_transaction();
                                            
                                            try {
                                                // Process mother's details if provided
                                                if (isset($_POST['checkmother']) && !empty($_POST['txtnicmother']) && !empty($_POST['txtFnamemother'])) {
                                                    $stmt = $con->prepare("INSERT INTO dependent (Aid, NIC, name, dependent_type) VALUES (?, ?, ?, 'mother')");
                                                    $stmt->bind_param("sss", $Aid, $_POST['txtnicmother'], $_POST['txtFnamemother']);
                                                    if ($stmt->execute()) {
                                                        $successCount++;
                                                    } else {
                                                        $errorMessages[] = "Mother: " . $stmt->error;
                                                    }
                                                }
                                                
                                                // Process father's details if provided
                                                if (isset($_POST['checkfather']) && !empty($_POST['txtnicfather']) && !empty($_POST['txtFnamefather'])) {
                                                    $stmt = $con->prepare("INSERT INTO dependent (Aid, NIC, name, dependent_type) VALUES (?, ?, ?, 'father')");
                                                    $stmt->bind_param("sss", $Aid, $_POST['txtnicfather'], $_POST['txtFnamefather']);
                                                    if ($stmt->execute()) {
                                                        $successCount++;
                                                    } else {
                                                        $errorMessages[] = "Father: " . $stmt->error;
                                                    }
                                                }
                                                
                                                // Process spouse's details if provided
                                                if (isset($_POST['checkspouse']) && !empty($_POST['txtnicspouse']) && !empty($_POST['txtFnamespouse'])) {
                                                    $stmt = $con->prepare("INSERT INTO dependent (Aid, NIC, name, dependent_type) VALUES (?, ?, ?, 'spouse')");
                                                    $stmt->bind_param("sss", $Aid, $_POST['txtnicspouse'], $_POST['txtFnamespouse']);
                                                    if ($stmt->execute()) {
                                                        $successCount++;
                                                    } else {
                                                        $errorMessages[] = "Spouse: " . $stmt->error;
                                                    }
                                                }
                                                
                                                // Check if no dependents were added
                                                if ($successCount === 0 && empty($errorMessages)) {
                                                    echo "<div class='alert alert-warning'>No dependents were selected for insertion.</div>";
                                                } else {
                                                    // Commit transaction if all inserts were successful
                                                    $con->commit();
                                                    
                                                    if ($successCount > 0) {
                                                        echo "<div class='alert alert-success'>Successfully added $successCount dependent(s).</div>";
                                                    }
                                                    
                                                    if (!empty($errorMessages)) {
                                                        echo "<div class='alert alert-danger'>Errors occurred:<br>" . implode("<br>", $errorMessages) . "</div>";
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                // Rollback transaction if any error occurs
                                                $con->rollback();
                                                echo "<div class='alert alert-danger'>Transaction failed: " . $e->getMessage() . "</div>";
                                            }
                                        }
                                    }
                                    ?>
                            
                            <form method="post" action="#">
                                
                            <div class="search-container">
                                <button type="submit" name="btnsearch">Search</button>
                                <input type="text" placeholder="Search Army ID" name="txtAid" value="<?php echo isset($row) ? $row['Aid'] : ''; ?>">
                            </div>
                               
                               

                                <div class="mb-4">
                                    <input type="checkbox" name="checkmother" id="checkdep"><label id="lbldep">Mother</label>
                                    <input type="text" name="txtFnamemother" class="form-control" placeholder="Enter Full Name"  />
                                    <label>Dependent Full Name</label>
                                </div>
                                <div class="col-md-6 mb-4">
                                        <input type="text" name="txtnicmother" class="form-control" placeholder="Enter NIC"   />
                                        <label>Dependent NIC</label>
                                </div>
                                <div class="mb-4">
                                    <input type="checkbox" name="checkfather" id="checkdep"><label id="lbldep">Father</label>
                                    <input type="text" name="txtFnamefather" class="form-control" placeholder="Enter Full Name"  />
                                    <label>Dependent Full Name</label>
                                </div>
                                <div class="col-md-6 mb-4">
                                        <input type="text" name="txtnicfather" class="form-control" placeholder="Enter NIC"   />
                                        <label>Dependent NIC</label>
                                </div>
                                <div class="mb-4">
                                    <input type="checkbox" name="checkspouse" id="checkdep"><label id="lbldep">Spouse</label>
                                    <input type="text" name="txtFnamespouse" class="form-control" placeholder="Enter Full Name"  />
                                    <label>Dependent Full Name</label>
                                </div>
                                <div class="col-md-6 mb-4">
                                        <input type="text" name="txtnicspouse" class="form-control" placeholder="Enter NIC"   />
                                        <label>Dependent NIC</label>
                                </div>
                                
                                <button type="submit" class="btn btn-success" name="btnSubmit">Submit</button>
                            </form>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
