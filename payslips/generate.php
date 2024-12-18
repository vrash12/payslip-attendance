<?php
// payslips/generate.php
session_start();
include('../config/db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = trim($_POST['role']);
    $basic_salary = floatval($_POST['basic_salary']);
    $pay_period_start = $_POST['pay_period_start'];
    $pay_period_end = $_POST['pay_period_end'];

    // Validate inputs
    if (empty($role) || $basic_salary <= 0) {
        $error = "Please provide a valid role and basic salary.";
    } elseif (empty($pay_period_start) || empty($pay_period_end)) {
        $error = "Please select both start and end dates.";
    } else {
        // Step 1: Update/Insert Salary for the chosen role
        $sql_check = "SELECT role FROM salaries WHERE role = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $role);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Update existing salary
            $sql_update = "UPDATE salaries SET basic_salary = ? WHERE role = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ds", $basic_salary, $role);
            $stmt_update->execute();
        } else {
            // Insert new salary
            $sql_insert = "INSERT INTO salaries (role, basic_salary) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sd", $role, $basic_salary);
            $stmt_insert->execute();
        }

        // Step 2: Generate Payslips using the updated salary
        $sql_users = "SELECT user_id, role FROM users WHERE role != 'Owner' AND status = 'Active'";
        $result_users = $conn->query($sql_users);

        if ($result_users->num_rows > 0) {
            while ($user = $result_users->fetch_assoc()) {
                $user_id = $user['user_id'];
                $user_role = $user['role'];

                // Get the updated salary for this user's role
                $sql_salary = "SELECT basic_salary FROM salaries WHERE role = ?";
                $stmt_salary = $conn->prepare($sql_salary);
                $stmt_salary->bind_param("s", $user_role);
                $stmt_salary->execute();
                $result_salary = $stmt_salary->get_result();
                $salary_row = $result_salary->fetch_assoc();

                // If no salary found for the role, default to 0
                $current_basic_salary = $salary_row ? $salary_row['basic_salary'] : 0;

                $sql_attendance = "SELECT COUNT(*) AS days_worked FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ?";
                $stmt_attendance = $conn->prepare($sql_attendance);
                $stmt_attendance->bind_param("iss", $user_id, $pay_period_start, $pay_period_end);
                $stmt_attendance->execute();
                $result_attendance = $stmt_attendance->get_result();
                $attendance_row = $result_attendance->fetch_assoc();
                $days_worked = $attendance_row ? $attendance_row['days_worked'] : 0;

                $deductions = 0;
                $overtime_pay = 0;
                // Assuming a 15-day period in the calculation as per the original code
                $net_pay = ($current_basic_salary / 15) * $days_worked + $overtime_pay - $deductions;

                $sql_payslip = "INSERT INTO payslips (user_id, pay_period_start, pay_period_end, basic_salary, overtime_pay, deductions, net_pay) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_payslip = $conn->prepare($sql_payslip);
                $stmt_payslip->bind_param("issdddd", $user_id, $pay_period_start, $pay_period_end, $current_basic_salary, $overtime_pay, $deductions, $net_pay);
                $stmt_payslip->execute();
            }
            $message = "Payslips generated successfully for the period " 
                     . date('M d, Y', strtotime($pay_period_start)) 
                     . " to " . date('M d, Y', strtotime($pay_period_end)) . ".";
        } else {
            $error = "No active employees found.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Generate Payslips</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <h2>Generate Payslips & Set Salary</h2>
        <?php if ($message != ''): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error != ''): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Combined form for setting salary and generating payslips -->
        <form action="generate.php" method="POST">
            <div class="form-group">
                <label>Role:</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <?php
                    // Fetch distinct roles from active non-owner users
                    $sql_roles = "SELECT DISTINCT role FROM users WHERE role != 'Owner' AND status = 'Active' ORDER BY role";
                    $result_roles = $conn->query($sql_roles);
                    if ($result_roles->num_rows > 0) {
                        while ($row = $result_roles->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['role']) . '">' . htmlspecialchars($row['role']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Basic Salary (₱):</label>
                <input type="number" step="0.01" name="basic_salary" required>
            </div>
            <div class="form-group">
                <label>Pay Period Start Date:</label>
                <input type="date" name="pay_period_start" required>
            </div>
            <div class="form-group">
                <label>Pay Period End Date:</label>
                <input type="date" name="pay_period_end" required>
            </div>
            <button type="submit">Set Salary & Generate Payslips</button>
        </form>

        <hr>
        <h3>Employee Salaries (Current Setup)</h3>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>User ID</th>
                <th>Role</th>
                <th>Basic Salary</th>
            </tr>
            <?php
            $sql_users = "SELECT user_id, role FROM users WHERE role != 'Owner' AND status = 'Active'";
            $result_users = $conn->query($sql_users);
            if ($result_users->num_rows > 0):
                while ($user = $result_users->fetch_assoc()):
                    $user_role = $user['role'];
                    $sql_salary = "SELECT basic_salary FROM salaries WHERE role = ?";
                    $stmt_salary = $conn->prepare($sql_salary);
                    $stmt_salary->bind_param("s", $user_role);
                    $stmt_salary->execute();
                    $result_salary = $stmt_salary->get_result();
                    $salary_row = $result_salary->fetch_assoc();
                    $current_basic_salary = $salary_row ? $salary_row['basic_salary'] : 0;
            ?>
            <tr>
                <td><?php echo $user['user_id']; ?></td>
                <td><?php echo htmlspecialchars($user_role); ?></td>
                <td>₱<?php echo number_format($current_basic_salary, 2); ?></td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr><td colspan="3">No active employees found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>