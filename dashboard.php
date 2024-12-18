<?php
// dashboard.php
session_start();
include('config/db_connect.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$role      = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance and Payslip Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>
    <?php include('includes/navbar.php'); ?>

    <div class="container my-5">
        <h1 class="text-center">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <h2 class="text-center">Role: <?php echo $role; ?></h2>

        <div class="row mt-4">
            <?php if ($role == 'Owner'): ?>
                <!-- Owner's Dashboard -->
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Admin Panel</h3>
                            <ul class="list-group">
                                <li class="list-group-item"><a href="admin/users.php">Manage Users</a></li>
                                <li class="list-group-item"><a href="payslips/generate.php">Generate Payslips</a></li>
                                <li class="list-group-item"><a href="admin/reports.php">View Reports</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Manager, Admin Staff, Rider Dashboard -->
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Attendance</h3>
                            <ul class="list-group">
                                <li class="list-group-item"><a href="attendance/scan.php">Scan QR Code</a></li>
                                <li class="list-group-item"><a href="attendance/view.php">View Attendance Records</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Payslips</h3>
                            <ul class="list-group">
                                <li class="list-group-item"><a href="payslips/view.php">View Payslips</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0v8Fqv4YZq9lFvA+6l5k2v5VR0z5bA5nEOhBkh21t+jChAS" crossorigin="anonymous"></script>
</body>
</html>
