<?php
// admin/reports.php
session_start();
include('../config/db_connect.php');

// Check if the user is logged in and is the Owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$error = '';
$attendance_data = [];
$payslip_data = [];
$summary_stats = [];
$chart_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (empty($start_date) || empty($end_date)) {
        $error = "Please select both start and end dates.";
    } else {
        if ($report_type == 'attendance') {
            // Generate attendance report with advanced analytics
            // Fetch attendance records
            $sql = "SELECT u.full_name, a.date, a.check_in_time, a.check_out_time, TIMESTAMPDIFF(HOUR, a.check_in_time, a.check_out_time) AS hours_worked
                    FROM attendance a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.date BETWEEN ? AND ?
                    ORDER BY u.full_name, a.date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $attendance_data = $stmt->get_result();

            // Calculate summary statistics
            $sql_summary = "SELECT 
                                COUNT(*) AS total_records,
                                SUM(TIMESTAMPDIFF(HOUR, a.check_in_time, a.check_out_time)) AS total_hours,
                                AVG(TIMESTAMPDIFF(HOUR, a.check_in_time, a.check_out_time)) AS avg_hours_per_day
                            FROM attendance a
                            WHERE a.date BETWEEN ? AND ?";
            $stmt_summary = $conn->prepare($sql_summary);
            $stmt_summary->bind_param("ss", $start_date, $end_date);
            $stmt_summary->execute();
            $summary_stats = $stmt_summary->get_result()->fetch_assoc();

            // Prepare data for charts
            $chart_data = [];
            $sql_chart = "SELECT DATE(a.date) AS date, COUNT(a.user_id) AS present_employees
                          FROM attendance a
                          WHERE a.date BETWEEN ? AND ?
                          GROUP BY DATE(a.date)
                          ORDER BY DATE(a.date)";
            $stmt_chart = $conn->prepare($sql_chart);
            $stmt_chart->bind_param("ss", $start_date, $end_date);
            $stmt_chart->execute();
            $result_chart = $stmt_chart->get_result();
            while ($row = $result_chart->fetch_assoc()) {
                $chart_data[] = $row;
            }

        } elseif ($report_type == 'payslip') {
            // Generate payroll report with advanced analytics
            $sql = "SELECT u.full_name, p.pay_period_start, p.pay_period_end, p.basic_salary, p.overtime_pay, p.deductions, p.net_pay
                    FROM payslips p
                    JOIN users u ON p.user_id = u.user_id
                    WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?
                    ORDER BY p.pay_period_end DESC, u.full_name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $payslip_data = $stmt->get_result();

            // Calculate summary statistics
            $sql_summary = "SELECT 
                                SUM(p.net_pay) AS total_payroll,
                                AVG(p.net_pay) AS avg_pay_per_employee,
                                MAX(p.net_pay) AS max_pay,
                                MIN(p.net_pay) AS min_pay
                            FROM payslips p
                            WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?";
            $stmt_summary = $conn->prepare($sql_summary);
            $stmt_summary->bind_param("ss", $start_date, $end_date);
            $stmt_summary->execute();
            $summary_stats = $stmt_summary->get_result()->fetch_assoc();

            // Prepare data for charts
            $chart_data = [];
            $sql_chart = "SELECT u.full_name, p.net_pay
                          FROM payslips p
                          JOIN users u ON p.user_id = u.user_id
                          WHERE p.pay_period_start >= ? AND p.pay_period_end <= ?";
            $stmt_chart = $conn->prepare($sql_chart);
            $stmt_chart->bind_param("ss", $start_date, $end_date);
            $stmt_chart->execute();
            $result_chart = $stmt_chart->get_result();
            while ($row = $result_chart->fetch_assoc()) {
                $chart_data[] = $row;
            }

        } else {
            $error = "Invalid report type selected.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Reports</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Styles -->
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container mt-4">
        <h2>Reports</h2>
        <?php if ($error != ''): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="reports.php" method="POST" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Report Type:</label>
                    <select name="report_type" class="form-control" required>
                        <option value="attendance" <?php if(isset($report_type) && $report_type == 'attendance') echo 'selected'; ?>>Attendance Report</option>
                        <option value="payslip" <?php if(isset($report_type) && $report_type == 'payslip') echo 'selected'; ?>>Payroll Report</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" class="form-control" required value="<?php echo isset($start_date) ? $start_date : ''; ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>End Date:</label>
                    <input type="date" name="end_date" class="form-control" required value="<?php echo isset($end_date) ? $end_date : ''; ?>">
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                </div>
            </div>
        </form>

        <?php if (!empty($attendance_data) && $report_type == 'attendance'): ?>
            <!-- Display Attendance Report -->
            <h3>Attendance Report (<?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</h3>

            <!-- Summary Statistics -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-header">Total Hours Worked</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $summary_stats['total_hours'] ? $summary_stats['total_hours'] : '0'; ?> Hours</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Average Hours per Day</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($summary_stats['avg_hours_per_day'], 2); ?> Hours</h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <table class="table table-bordered table-hover mt-3">
                <thead class="thead-dark">
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $attendance_data->data_seek(0); // Reset pointer
                    while ($row = $attendance_data->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($row['full_name']).'</td>';
                        echo '<td>'.$row['date'].'</td>';
                        echo '<td>'.$row['check_in_time'].'</td>';
                        echo '<td>'.$row['check_out_time'].'</td>';
                        echo '<td>'.($row['hours_worked'] ? $row['hours_worked'] : '0').'</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Attendance Chart -->
            <canvas id="attendanceChart" width="400" height="150"></canvas>

            <script>
                var ctx = document.getElementById('attendanceChart').getContext('2d');
                var attendanceChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [
                            <?php foreach ($chart_data as $data) { echo "'".$data['date']."',"; } ?>
                        ],
                        datasets: [{
                            label: 'Employees Present',
                            data: [
                                <?php foreach ($chart_data as $data) { echo $data['present_employees'].","; } ?>
                            ],
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            fill: false
                        }]
                    },
                    options: {
                        scales: {
                            xAxes: [{ type: 'time', time: { unit: 'day' } }],
                            yAxes: [{ ticks: { beginAtZero: true, precision:0 } }]
                        },
                        responsive: true
                    }
                });
            </script>

        <?php elseif (!empty($payslip_data) && $report_type == 'payslip'): ?>
            <!-- Display Payroll Report -->
            <h3>Payroll Report (<?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</h3>

            <!-- Summary Statistics -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-header">Total Payroll</div>
                        <div class="card-body">
                            <h5 class="card-title">₱<?php echo number_format($summary_stats['total_payroll'], 2); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Average Pay</div>
                        <div class="card-body">
                            <h5 class="card-title">₱<?php echo number_format($summary_stats['avg_pay_per_employee'], 2); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-header">Highest Pay</div>
                        <div class="card-body">
                            <h5 class="card-title">₱<?php echo number_format($summary_stats['max_pay'], 2); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-header">Lowest Pay</div>
                        <div class="card-body">
                            <h5 class="card-title">₱<?php echo number_format($summary_stats['min_pay'], 2); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Table -->
            <table class="table table-bordered table-hover mt-3">
                <thead class="thead-dark">
                    <tr>
                        <th>Employee</th>
                        <th>Pay Period</th>
                        <th>Basic Salary</th>
                        <th>Overtime Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $payslip_data->data_seek(0); // Reset pointer
                    while ($row = $payslip_data->fetch_assoc()) {
                        $pay_period = date('M d, Y', strtotime($row['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($row['pay_period_end']));
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($row['full_name']).'</td>';
                        echo '<td>'.$pay_period.'</td>';
                        echo '<td>₱'.number_format($row['basic_salary'], 2).'</td>';
                        echo '<td>₱'.number_format($row['overtime_pay'], 2).'</td>';
                        echo '<td>₱'.number_format($row['deductions'], 2).'</td>';
                        echo '<td>₱'.number_format($row['net_pay'], 2).'</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>



            <script>
        var ctx = document.getElementById('payrollChart').getContext('2d');
                var payrollChart = new Chart(ctx, {
          type: 'bar',
                    data: {
                        labels: [
                            <?php foreach ($chart_data as $data) { echo "'".$data['full_name']."',"; } ?>
                        ],
                        datasets: [{
                            label: 'Net Pay',
                            data: [
                                <?php foreach ($chart_data as $data) { echo $data['net_pay'].","; } ?>
                            ],
             backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
           options: {
                        scales: {
                            yAxes: [{ ticks: { beginAtZero: true } }]
                        },
            responsive: true
                    }
                });
            </script>

        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
</body>
</html>
