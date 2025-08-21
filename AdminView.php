<!doctype html>
<html lang="en">
    <head>
        <title>Title</title>
        <!-- Required meta tags -->
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1, shrink-to-fit=no"
        />
        <link rel="stylesheet" href="style.css">

        <!-- Bootstrap CSS v5.2.1 -->
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
            crossorigin="anonymous"
        />
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
                        <a class="nav-link"  href="AdminInsert.php">Insert</a>
                    </li> &nbsp; &nbsp; &nbsp; &nbsp;
                    <li class="nav-item">
                        <a class="nav-link" href="AdminUpdate.php">Update</a>
                    </li> &nbsp; &nbsp; &nbsp; &nbsp;
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="AdminView.php"><b>View</b></a>
                    </li> &nbsp; &nbsp; &nbsp; 
                </ul>
            </nav>
        </header>

        <main>
            <section class="regform ">
                <div class="container py-5">
                  <div class="row d-flex justify-content-center align-items-center h-100">
                    <div class="card rounded-3" >
                      <div class="card-body p-4 p-md-5">
                          <h3 class="pb-md-0 mb-md-5 px-md-2">View Users Details</h3>

                            <?php
                            include 'dbconnection.php';

                            $rows = [];
                            $education_data = [];

                            // Fetch all records initially
                            $sql = "SELECT * FROM soldetails";
                            $result = mysqli_query($con, $sql);

                            if ($result) {
                                while ($data = mysqli_fetch_assoc($result)) {
                                    $rows[] = $data;
                                }
                            }

                            // Fetch all education data
                            $edu_sql = "SELECT * FROM soleducation";
                            $edu_result = mysqli_query($con, $edu_sql);
                            
                            if ($edu_result) {
                                while ($edu = mysqli_fetch_assoc($edu_result)) {
                                    $education_data[$edu['Aid']] = $edu['education'];
                                }
                            }

                            // Handle search request
                            if (isset($_POST['search'])) {
                                $Aid = $_POST['search_id'];
                                $sql1 = "SELECT * FROM soldetails WHERE Aid='$Aid'";
                                $sql2 = "SELECT * FROM soleducation WHERE id='$Aid'";

                                $result = mysqli_query($con, $sql1);
                                $edu_result = mysqli_query($con, $sql2);

                                if (mysqli_num_rows($result) > 0) {
                                    $rows = [];
                                    while ($data = mysqli_fetch_assoc($result)) {
                                        $rows[] = $data;
                                    }
                                    
                                    // Update education data for searched record
                                    if ($edu_result && mysqli_num_rows($edu_result) > 0) {
                                        $edu_data = mysqli_fetch_assoc($edu_result);
                                        $education_data[$Aid] = $edu_data['education'];
                                    }

                                } 
                                else {
                                    echo "<p style='color:red;'> No Records Found </p>";
                                }
                            }

                            mysqli_close($con);
                            ?>
                        <div class="abc">
                        <form method="POST" class="mb-3">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="mb-4">
                                        <input type="text" name="search_id" class="form-control" placeholder="Enter Army ID to Search" required>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-4">
                                    <div class="mb-4">
                                        <button type="submit" name="search" class="btn btn-primary w-100">Search</button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="mb-4">
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Soldiers Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Army ID</th>
                                        <th>NIC</th>
                                        <th>Full Name</th>
                                        <th>DOB</th>
                                        <th>Gender</th>
                                        <th>Address</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>RS Division</th>
                                        <th>Marital Status</th>
                                        <th>Blood Group</th>
                                        <th>Password</th>
                                        <th>Education Qualification</th>
                                        <th>Regiment</th>
                                        <th>Battalion</th>
                                        <th>Rank</th>
                                        <th>Join Date</th>
                                        <th>Position</th>
                                        <th>Assign Date</th>
                                        <th>Profile</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($rows)) { ?>
                                        <?php foreach ($rows as $data) { 
                                            $Aid = $data['Aid'];
                                            $education = isset($education_data[$Aid]) ? $education_data[$Aid] : 'Not specified';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($Aid); ?></td>
                                                <td><?php echo htmlspecialchars($data['nic']); ?></td>
                                                <td><?php echo htmlspecialchars($data['fullName']); ?></td>
                                                <td><?php echo htmlspecialchars($data['dob']); ?></td>
                                                <td><?php echo htmlspecialchars($data['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($data['address']); ?></td>
                                                <td><?php echo htmlspecialchars($data['email']); ?></td>
                                                <td><?php echo htmlspecialchars($data['contact']); ?></td>
                                                <td><?php echo htmlspecialchars($data['rsDivition']); ?></td>
                                                <td><?php echo htmlspecialchars($data['maritalStatus']); ?></td>
                                                <td><?php echo htmlspecialchars($data['bGroup']); ?></td>
                                                <td><?php echo htmlspecialchars($data['password']); ?></td>
                                                <td><?php echo htmlspecialchars($education); ?></td>
                                                <td><?php echo htmlspecialchars($data['regiment_id']); ?></td>
                                                <td><?php echo htmlspecialchars($data['battalion_id']); ?></td>
                                                <td><?php echo htmlspecialchars($data['rank']); ?></td>
                                                <td><?php echo htmlspecialchars($data['joinDate']); ?></td>
                                                <td><?php echo htmlspecialchars($data['position']); ?></td>
                                                <td><?php echo htmlspecialchars($data['assignDate']); ?></td>
                                                <td>
                                                    <?php if (!empty($data['profile'])): ?>
                                                        <img src="Profile/<?php echo htmlspecialchars($data['profile']); ?>" alt="Profile" width="60" height="60" style="object-fit: cover; border-radius: 50%;">
                                                    <?php else: ?>
                                                        <span>No Image</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['status']); ?></td>
                                                <td>
                                                    <a href="AdminUpdate.php?aid=<?php echo htmlspecialchars($Aid); ?>"><button type="button" class="btn btn-info btn-sm"> ✏ </button></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="23" class="text-center">No records found.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
              
                        </div>
                      </div>
                    </div>
                </div>
              </section>
        </main>

        <footer>
            <!-- place footer here -->
        </footer>
        <!-- Bootstrap JavaScript Libraries -->
        <script
            src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
            crossorigin="anonymous"
        ></script>

        <script
            src="bootstrap/js/bootstrap.min.js"
            integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
            crossorigin="anonymous"
        ></script>
    </body>
</html>