<!doctype html>
<html lang="en">
<head>
    <title>Insert Battalion</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <style>

.regform{
    background: linear-gradient(#9cd769,#30b730, #088724);
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
                    <a class="nav-link active" aria-current="page" href="AdminBattInsert.php"><b>Insert</b></a>
                </li> &nbsp; &nbsp; &nbsp; &nbsp;
                <li class="nav-item">
                    <a class="nav-link" href="AdminBattUpdate.php">Update</a>
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
                            <h3 class="pb-md-0 mb-md-5 px-md-2">Insert Battalion Details</h3>

                            <?php
                                if (isset($_POST['btnSubmit'])) {
                                    $batid = $_POST["txtBatid"];
                                    $batname = $_POST["txtBatname"];
                                    $Aperson = $_POST["txtApersonnel"];
                                    $regid = $_POST["comboregid"];
                                 
                                    
                                  

                                    
                                        include 'dbconnection.php';

                                        $sql1 = "INSERT INTO battalion (regiment_id,battalion_id,battalion_name,active_personnel)
                                                VALUES('$regid','$batid','$batname','$Aperson')";

                                      if (mysqli_query($con, $sql1) ) 
                                        {
                                            echo "<div class='alert alert-success'>Record inserted successfully.</div>";
                                        }
                                        else
                                        {
                                            echo "<div class='alert alert-danger'>Error: " . mysqli_error($con) . "</div>";
                                        }
                                        
                                    }
                                
                            ?>


                            <form method="post" action="#">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="txtBatid" class="form-control" placeholder=" Battalion ID"  required  />
                                        <label>Battalion ID</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <input type="text" name="txtBatname" class="form-control" placeholder="Enter Battalion Name" required />
                                    <label>Battalion Name</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <select name="comboregid" id="combo" class="form-control" required>
                                            <option value="">--- Select Regiment ---</option>
                                            <?php
                                            for ($i = 1; $i <= 25; $i++) {
                                                $num = str_pad($i, 2, '0', STR_PAD_LEFT);
                                                echo "<option value=\"R$num\">R$num</option>\n";
                                            }
                                            ?>
                                        </select>
                                        <label id="lblcombo">Select Regiment</label>
                                    </div>
                                <div class="mb-4">
                                    <input type="text" name="txtApersonnel" class="form-control" placeholder="Enter Active Personnel" required />
                                    <label>Active Personnel</label>
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
