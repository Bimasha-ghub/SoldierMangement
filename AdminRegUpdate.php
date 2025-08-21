<!doctype html>
<html lang="en">
<head>
    <title>Update Regiment</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .regform{
            background: linear-gradient(#9cd769,#30b730, #088724);
        }
        
        #btnUpdate
        {
         width: 100%;
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
        <nav class="bg-dark " style="height: 10px; width: 100%;"></nav>
        <nav class="bg-dark" data-bs-theme="dark" style="height: 60px; width: 100%;">
            <ul class="nav justify-content-center mb-2 nav-tabs"><br>
                <li class="nav-item">
                    <a class="nav-link" href="adminPanel.html">Home</a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp;    
                <li class="nav-item">
                    <a class="nav-link " aria-current="page" href="AdminRegInsert.php"><b>Insert</b></a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link active" href="AdminRegUpdate.php">Update</a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link" href="AdminRegDelete.php">Delete</a>
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
                                <h3 class="pb-md-0 mb-md-5 px-md-2">Update Regiment Details</h3>

                                <?php
                                    include 'dbconnection.php';

                                    $row = null;
                                    $regiment_id = "";

                                    // Handle search request
                                    if (isset($_POST['btnsearch'])) {
                                        $regiment_id = $_POST['txtRegid'];

                                        $stmt = $con->prepare("SELECT * FROM regiment WHERE regiment_id=?");
                                        $stmt->bind_param("s", $regiment_id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        if ($result && $result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                        } else {
                                            echo "<div class='alert alert-danger'>Army ID not found!!</div>";
                                        }
                                    }

                                    // Handle update request
                                    if (isset($_POST['btnUpdate'])) {
                                        $regiment_id = $_POST["txtRegid"];
                                        $regiment_name = $_POST["txtRegname"];
                                        $regiment_type = $_POST["combo"];
                                        $active_personnel = $_POST["txtApersonnel"];

                                        $sql = "UPDATE regiment SET regiment_name = ?,regiment_type = ?,active_personnel = ? WHERE regiment_id = ?";

                                        $stmt = $con->prepare($sql);
                                        $stmt->bind_param("ssis", $regiment_name, $regiment_type, $active_personnel, $regiment_id);

                                        if ($stmt->execute()) {
                                            echo "<div class='alert alert-success'>Record updated successfully.</div>";

                                            $row = null;
                                            $regiment_id = "";
                                        } else {
                                            echo "<div class='alert alert-danger'>Error updating record: " . $stmt->error . "</div>";
                                        }
                                    }
                                ?>

                                <form method="post" action="#">
                                    <div class="row">
                                        <div class="search-container">
                                            <button type="submit" name="btnsearch">Search</button>
                                            <input type="text" name="txtRegid" class="form-control" 
                                                   placeholder="Regiment ID" required 
                                                   value="<?php echo isset($row) ? $row['regiment_id'] : ''; ?>" />
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <input type="text" name="txtRegname" class="form-control" 
                                               placeholder="Enter Regiment Name" 
                                               value="<?php echo isset($row) ? $row['regiment_name'] : ''; ?>" />
                                        <label>Regiment Name</label>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <select name="combo" id="combo" class="form-control">
                                                <option value="">--- Select Regiment Type ---</option>
                                                <option value="Armoured" <?php echo (isset($row) && $row['regiment_type'] == 'Armoured') ? 'selected' : ''; ?>>Armoured</option>
                                                <option value="Regular" <?php echo (isset($row) && $row['regiment_type'] == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                                <option value="Volunteer Force" <?php echo (isset($row) && $row['regiment_type'] == 'Volunteer Force') ? 'selected' : ''; ?>>Volunteer Force</option>
                                                <option value="Special Operations" <?php echo (isset($row) && $row['regiment_type'] == 'Special Operations') ? 'selected' : ''; ?>>Special Operations</option>
                                                <option value="Support Arms and Services" <?php echo (isset($row) && $row['regiment_type'] == 'Support Arms and Services') ? 'selected' : ''; ?>>Support Arms and Services</option>
                                            </select>
                                            <label id="lblcombo">Regiment Type</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <input type="text" name="txtApersonnel" class="form-control" 
                                               placeholder="Enter Active Personnel" 
                                               value="<?php echo isset($row) ? $row['active_personnel'] : ''; ?>" />
                                        <label>Active Personnel</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success" name="btnUpdate" id="btnUpdate">Update</button>
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