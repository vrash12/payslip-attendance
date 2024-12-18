<?php
// attendance/view.php
session_start();
include('../config/db_connect.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Check for messages
$success_message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $success_message = "Attendance record deleted successfully.";
    }
    // Add more message types if needed
}

// Fetch attendance records
$sql = "SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Attendance Records</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        img {
            max-width: 100px;
            height: auto;
        }
        .action-buttons a {
            margin: 0 5px;
            text-decoration: none;
            color: #007BFF;
        }
        .action-buttons a:hover {
            text-decoration: underline;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <h2>My Attendance Records</h2>
        <?php if ($success_message != ''): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Check-In Time</th>
                <th>Check-Out Time</th>
                <th>Check-In Photo</th>
                <th>Check-Out Photo</th>
                <th>Actions</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['check_in_time']) . "</td>";
                    echo "<td>" . (!empty($row['check_out_time']) ? htmlspecialchars($row['check_out_time']) : 'N/A') . "</td>";
                    
                    // Display Check-In Photo
                    echo "<td>";
                    if (!empty($row['photo_path'])) {
                        echo "<img src='" . htmlspecialchars($row['photo_path']) . "' alt='Check-In Photo'>";
                    } else {
                        echo "N/A";
                    }
                    echo "</td>";
                    
                    // Display Check-Out Photo
                    echo "<td>";
                    if (!empty($row['check_out_time']) && !empty($row['check_out_photo_path'])) {
                        echo "<img src='" . htmlspecialchars($row['check_out_photo_path']) . "' alt='Check-Out Photo'>";
                    } else {
                        echo "N/A";
                    }
                    echo "</td>";
                    
                    // Actions: Update and Delete
                    echo "<td class='action-buttons'>";
                    echo "<a href='update.php?id=" . htmlspecialchars($row['attendance_id']) . "'>Update</a>";
                    echo "<a href='delete.php?id=" . htmlspecialchars($row['attendance_id']) . "' onclick=\"return confirm('Are you sure you want to delete this record?');\">Delete</a>";
                    echo "</td>";
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No attendance records found.</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
