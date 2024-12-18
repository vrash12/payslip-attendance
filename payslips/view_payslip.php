<?php
// payslips/view_payslip.php
session_start();
include('../config/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['payslip_id'])) {
    header("Location: view.php");
    exit();
}

$payslip_id = intval($_GET['payslip_id']);
$user_id = $_SESSION['user_id'];

$sql = "SELECT p.*, u.full_name 
        FROM payslips p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.payslip_id = ? AND p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payslip_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payslip = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Payslip</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <?php if ($payslip): ?>
            <h2>Payslip Details</h2>
            <p><strong>Employee:</strong> <?php echo htmlspecialchars($payslip['full_name']); ?></p>
            <p><strong>Pay Period:</strong> <?php echo date('M d, Y', strtotime($payslip['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($payslip['pay_period_end'])); ?></p>
            <p><strong>Basic Salary:</strong> ₱<?php echo number_format($payslip['basic_salary'], 2); ?></p>
            <p><strong>Overtime Pay:</strong> ₱<?php echo number_format($payslip['overtime_pay'], 2); ?></p>
            <p><strong>Deductions:</strong> ₱<?php echo number_format($payslip['deductions'], 2); ?></p>
            <p><strong>Net Pay:</strong> ₱<?php echo number_format($payslip['net_pay'], 2); ?></p>
            <p><strong>Generated At:</strong> <?php echo $payslip['generated_at']; ?></p>
        <?php else: ?>
            <p>Payslip not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
