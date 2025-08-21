<!doctype html>
<html lang="en">
<head>
    <title>Update Battalion</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .regform{
            background: linear-gradient(#9cd769,#30b730, #088724);
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
                    <a class="nav-link " aria-current="page" href="AdminBattInsert.php"><b>Insert</b></a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link active" href="AdminBattUpdate.php">Update</a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link" href="AdminBattDelete.php">Delete</a>
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
                                <h3 class="pb-md-0 mb-md-5 px-md-2">Update Battalion Details</h3>

                                <?php
                                    include 'dbconnection.php';

                                    $row = null;
                                    $battalion_id = "";

                                    // Handle search request
                                    if (isset($_POST['btnsearch'])) {
                                        $battalion_id = $_POST['txtbattid'];

                                        $stmt = $con->prepare("SELECT * FROM battalion WHERE battalion_id = ?");
                                        $stmt->bind_param("s", $battalion_id);
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
                                        $battalion_id = $_POST["txtbattid"];
                                        $battalion_name = $_POST["txtbattname"];
                                        $regiment_id = $_POST["comboregid"];
                                        $active_personnel = $_POST["txtApersonnel"];

                                        $sql = "UPDATE battalion SET battalion_name = ?,regiment_id = ?,active_personnel = ? WHERE battalion_id = ?";

                                        $stmt = $con->prepare($sql);
                                        $stmt->bind_param("ssis", $battalion_name, $regiment_id, $active_personnel, $battalion_id);

                                        if ($stmt->execute()) {
                                            echo "<div class='alert alert-success'>Record updated successfully.</div>";

                                            $row = null;
                                            $battalion_id = "";
                                        } else {
                                            echo "<div class='alert alert-danger'>Error updating record: " . $stmt->error . "</div>";
                                        }
                                    }
                                ?>

                                <form method="post" action="#">
                                    <div class="row">
                                        <div class="search-container">
                                            <button type="submit" name="btnsearch">Search</button>
                                            <input type="text" name="txtbattid" class="form-control" 
                                                   placeholder="Battalion ID" required 
                                                   value="<?php echo isset($row) ? $row['battalion_id'] : ''; ?>" />
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <input type="text" name="txtbattname" class="form-control" 
                                               placeholder="Update Battalion Name" 
                                               value="<?php echo isset($row) ? $row['battalion_name'] : ''; ?>" />
                                        <label>Regiment Name</label>
                                    </div>
                                    
                                    <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboregid" id="combo" class="form-control" >
                                            <option value="">--- Update Regiment ---</option>
                                            <?php 
                                                for ($i = 1; $i <= 25; $i++) {
                                                    $value = 'R' . $i;
                                                    $selected = (isset($row) && $row['regiment_id'] == $value) ? 'selected' : '';
                                                    echo "<option value=\"$value\" $selected>$value</option>";
                                                }
                                                ?>
                                            
                                        </select>
                                        <label id="lblcombo">Select Regiment</label>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <input type="text" name="txtApersonnel" class="form-control" 
                                               placeholder="Update Active Personnel" 
                                               value="<?php echo isset($row) ? $row['active_personnel'] : ''; ?>" />
                                        <label>Active Personnel</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success" name="btnUpdate">Update</button>
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