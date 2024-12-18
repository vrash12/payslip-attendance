<?php
// payslips/view.php
session_start();
include('../config/db_connect.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

// Fetch payslip records for the logged-in user
$sql = "SELECT * FROM payslips WHERE user_id = ? ORDER BY pay_period_end DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>View Payslips</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <h2>My Payslips</h2>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Pay Period</th>
                <th>Basic Salary</th>
                <th>Overtime Pay</th>
                <th>Deductions</th>
                <th>Net Pay</th>
                <th>Action</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $pay_period = date('M d, Y', strtotime($row['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($row['pay_period_end']));
                    echo "<tr>";
                    echo "<td>" . $pay_period . "</td>";
                    echo "<td>" . number_format($row['basic_salary'], 2) . "</td>";
                    echo "<td>" . number_format($row['overtime_pay'], 2) . "</td>";
                    echo "<td>" . number_format($row['deductions'], 2) . "</td>";
                    echo "<td>" . number_format($row['net_pay'], 2) . "</td>";
                    echo "<td><a href='view_payslip.php?payslip_id=" . $row['payslip_id'] . "'>View Details</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No payslip records found.</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
