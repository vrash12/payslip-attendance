<?php
// scan.php
session_start();

// Set the default timezone to Philippines timezone
date_default_timezone_set('Asia/Manila'); // Set to Philippines timezone

include('../config/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$role      = $_SESSION['role'];

$message = '';
$error   = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $photo_data = $_POST['photo_data']; // Base64 encoded image
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Validate action
    if (!in_array($action, ['check_in', 'check_out'])) {
        $error = "Invalid action.";
    } else {
        // Decode and save the photo if provided
        $photo_filename = null;
        if (!empty($photo_data)) {
            // Decode the Base64 image
            $image_parts = explode(";base64,", $photo_data);
            if (count($image_parts) == 2) {
                $image_base64 = base64_decode($image_parts[1]);

                // Create a unique filename
                $photo_filename = 'attendance_' . $user_id . '_' . $date . '_' . str_replace(':', '-', $time) . '_' . $action . '.png';
                $photo_path = '../uploads/attendance_photos/' . $photo_filename;

                // Ensure directory exists
                if (!is_dir('../uploads/attendance_photos')) {
                    if (!mkdir('../uploads/attendance_photos', 0755, true)) {
                        die("Failed to create directories...");
                    }
                }

                // Save the image file
                if (file_put_contents($photo_path, $image_base64) === false) {
                    die("Failed to save the photo.");
                }
            } else {
                $error = "Invalid photo data.";
            }
        } else {
            $error = "Photo is required.";
        }

        if (empty($error)) {
            if ($action === 'check_in') {
                // Check if user has already checked in today
                $sql = "SELECT * FROM attendance WHERE user_id = ? AND `date` = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die("Prepare failed (SELECT): (" . $conn->errno . ") " . $conn->error);
                }
                $stmt->bind_param("is", $user_id, $date);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    // Insert check-in time
                    $sql = "INSERT INTO attendance (user_id, `date`, check_in_time, photo_path) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die("Prepare failed (INSERT): (" . $conn->errno . ") " . $conn->error);
                    }
                    $stmt->bind_param("isss", $user_id, $date, $time, $photo_filename);
                    if ($stmt->execute()) {
                        $message = "Check-in successful at $time.";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                } else {
                    $error = "You have already checked in today.";
                }
            } elseif ($action === 'check_out') {
                // Check if user has checked in today and hasn't checked out yet
                $sql = "SELECT * FROM attendance WHERE user_id = ? AND `date` = ? AND check_out_time IS NULL";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die("Prepare failed (SELECT for Check-Out): (" . $conn->errno . ") " . $conn->error);
                }
                $stmt->bind_param("is", $user_id, $date);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Update check-out time
                    $sql = "UPDATE attendance SET check_out_time = ?, photo_path = ? WHERE user_id = ? AND `date` = ? AND check_out_time IS NULL";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die("Prepare failed (UPDATE): (" . $conn->errno . ") " . $conn->error);
                    }
                    $stmt->bind_param("ssis", $time, $photo_filename, $user_id, $date);
                    if ($stmt->execute()) {
                        $message = "Check-out successful at $time.";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                } else {
                    $error = "You have not checked in today or have already checked out.";
                }
            }
        }
    }

    // Close the statement if it's still open
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Photo Verification Attendance</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        #video-container {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        video {
            width: 500px;
            height: auto;
        }
        #captureBtn {
            margin-top: 10px;
        }
        .action-section {
            margin-bottom: 40px;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <h2>Photo Verification Attendance</h2>
        <?php if ($message != ''): ?>
            <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error != ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Hidden form to submit scanned data -->
        <form method="POST" action="scan.php" id="attendance-form">
            <input type="hidden" name="photo_data" id="photo_data">
            <input type="hidden" name="action" id="action" value="">
        </form>

        <!-- Check-In Section -->
        <div class="action-section">
            <h3>Check-In</h3>
            <?php
                // Check if user has already checked in today
                include('../config/db_connect.php'); // Reconnect to the database
                $current_date = date('Y-m-d');
                $sql_check_in = "SELECT * FROM attendance WHERE user_id = ? AND `date` = ?";
                $stmt_check_in = $conn->prepare($sql_check_in);
                if ($stmt_check_in === false) {
                    die("Prepare failed (SELECT for Check-In Status): (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_check_in->bind_param("is", $user_id, $current_date);
                $stmt_check_in->execute();
                $result_check_in = $stmt_check_in->get_result();
                $has_checked_in = ($result_check_in->num_rows > 0);
                $has_checked_out = false;
                if ($has_checked_in) {
                    $row = $result_check_in->fetch_assoc();
                    if (!is_null($row['check_out_time'])) {
                        $has_checked_out = true;
                    }
                }
                $stmt_check_in->close();
                $conn->close();
            ?>

            <?php if (!$has_checked_in): ?>
                <button id="checkInBtn">Check-In</button>
            <?php else: ?>
                <?php if (!$has_checked_out): ?>
                    <p>You have already checked in today. Please proceed to check out.</p>
                <?php else: ?>
                    <p>You have already checked in and checked out today.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Check-Out Section -->
        <div class="action-section">
            <h3>Check-Out</h3>
            <?php
                // Reconnect to the database
                include('../config/db_connect.php'); // Reconnect to the database
                $sql_check_out = "SELECT * FROM attendance WHERE user_id = ? AND `date` = ? AND check_out_time IS NULL";
                $stmt_check_out = $conn->prepare($sql_check_out);
                if ($stmt_check_out === false) {
                    die("Prepare failed (SELECT for Check-Out Status): (" . $conn->errno . ") " . $conn->error);
                }
                $stmt_check_out->bind_param("is", $user_id, $current_date);
                $stmt_check_out->execute();
                $result_check_out = $stmt_check_out->get_result();
                $can_check_out = ($result_check_out->num_rows > 0);
                $stmt_check_out->close();
                $conn->close();
            ?>

            <?php if ($can_check_out): ?>
                <button id="checkOutBtn">Check-Out</button>
            <?php else: ?>
                <p>You have not checked in today or have already checked out.</p>
            <?php endif; ?>
        </div>

        <!-- Video Container for Photo Capture -->
        <div id="video-container">
            <video id="video" autoplay playsinline></video><br>
            <button id="captureBtn">Capture Photo</button>
        </div>

        <canvas id="canvas" style="display:none;"></canvas>
    </div>

    <script type="text/javascript">
        let localStream = null;
        let currentAction = '';

        // Check-In Button
        document.getElementById('checkInBtn')?.addEventListener('click', function() {
            currentAction = 'check_in';
            startWebcam();
        });

        // Check-Out Button
        document.getElementById('checkOutBtn')?.addEventListener('click', function() {
            currentAction = 'check_out';
            startWebcam();
        });

        function startWebcam() {
            const constraints = { video: true };
            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    localStream = stream;
                    document.getElementById('video-container').style.display = 'block';
                    let video = document.getElementById('video');
                    video.srcObject = stream;
                })
                .catch(error => {
                    console.error("Error accessing camera:", error);
                    alert("Could not access your camera. Please check your browser permissions.");
                });
        }

        document.getElementById('captureBtn')?.addEventListener('click', function() {
            let video = document.getElementById('video');
            let canvas = document.getElementById('canvas');
            let context = canvas.getContext('2d');

            // Set canvas size to match video dimensions
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw the video frame onto the canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert canvas to Data URL (base64 image)
            let dataURL = canvas.toDataURL('image/png');
            document.getElementById('photo_data').value = dataURL;
            document.getElementById('action').value = currentAction;

            // Stop the webcam stream
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }

            // Hide the video container
            document.getElementById('video-container').style.display = 'none';

            // Submit the form
            document.getElementById('attendance-form').submit();
        });
    </script>
</body>
</html>
