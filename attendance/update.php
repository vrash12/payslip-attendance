<?php
// attendance/update.php
session_start();
include('../config/db_connect.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Initialize variables
$attendance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Fetch the attendance record
$sql = "SELECT * FROM attendance WHERE attendance_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param("ii", $attendance_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No record found or user does not have permission
    header("Location: view.php?msg=record_not_found");
    exit();
}

$attendance = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $new_check_in_time = isset($_POST['check_in_time']) ? trim($_POST['check_in_time']) : '';
    $new_check_out_time = isset($_POST['check_out_time']) ? trim($_POST['check_out_time']) : '';
    
    // Initialize variables for photo uploads
    $check_in_photo = isset($_FILES['check_in_photo']) ? $_FILES['check_in_photo'] : null;
    $check_out_photo = isset($_FILES['check_out_photo']) ? $_FILES['check_out_photo'] : null;
    
    // Validation
    if (empty($new_check_in_time)) {
        $error_message .= "Check-In Time is required.<br>";
    }
    
    if (!empty($new_check_out_time) && (strtotime($new_check_out_time) < strtotime($new_check_in_time))) {
        $error_message .= "Check-Out Time cannot be earlier than Check-In Time.<br>";
    }
    
    // Handle photo uploads
    $check_in_photo_path = $attendance['check_in_photo_path'];
    $check_out_photo_path = $attendance['check_out_photo_path'];
    
    // Directory to store uploaded photos
    $upload_dir = '../uploads/attendance_photos/';
    
    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            die("Failed to create directories for uploads.");
        }
    }
    
    // Function to handle photo upload
    function upload_photo($file, $prefix, $user_id, $date, $time, $action) {
        global $upload_dir, $error_message;
        
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $file['tmp_name'];
            $file_name = basename($file['name']);
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_message .= "Invalid file type for $prefix photo. Only JPG and PNG are allowed.<br>";
                return null;
            }
            
            // Create a unique filename
            $new_filename = $prefix . '_' . $user_id . '_' . $date . '_' . str_replace(':', '-', $time) . '_' . $action . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            // Move the uploaded file
            if (move_uploaded_file($file_tmp_path, $destination)) {
                return $destination;
            } else {
                $error_message .= "Failed to upload $prefix photo.<br>";
                return null;
            }
        }
        return null;
    }
    
    // If no errors, proceed to update
    if (empty($error_message)) {
        // Begin building the UPDATE query
        $update_fields = "check_in_time = ?, check_out_time = ?";
        $params = [$new_check_in_time, $new_check_out_time];
        $param_types = "ss";
        
        // Handle Check-In Photo Upload
        if ($check_in_photo && $check_in_photo['error'] !== UPLOAD_ERR_NO_FILE) {
            $current_date = $attendance['date'];
            $current_time = date('H-i-s'); // Use current time for filename
            $uploaded_path = upload_photo($check_in_photo, 'checkin', $user_id, $current_date, $current_time, 'update');
            if ($uploaded_path) {
                $update_fields .= ", check_in_photo_path = ?";
                $params[] = $uploaded_path;
                $param_types .= "s";
            }
        }
        
        // Handle Check-Out Photo Upload
        if ($check_out_photo && $check_out_photo['error'] !== UPLOAD_ERR_NO_FILE) {
            $current_date = $attendance['date'];
            $current_time = date('H-i-s'); // Use current time for filename
            $uploaded_path = upload_photo($check_out_photo, 'checkout', $user_id, $current_date, $current_time, 'update');
            if ($uploaded_path) {
                $update_fields .= ", check_out_photo_path = ?";
                $params[] = $uploaded_path;
                $param_types .= "s";
            }
        }
        
        // Complete the SQL statement
        $sql_update = "UPDATE attendance SET $update_fields WHERE attendance_id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        
        // Add attendance_id and user_id to parameters
        $params[] = $attendance_id;
        $params[] = $user_id;
        $param_types .= "ii";
        
        // Bind parameters dynamically
        $stmt_update->bind_param($param_types, ...$params);
        
        // Execute the update
        if ($stmt_update->execute()) {
            $success_message = "Attendance record updated successfully.";
            // Optionally, redirect to view page with a success message
            header("Location: view.php?msg=updated");
            exit();
        } else {
            $error_message .= "Error updating record: " . $stmt_update->error;
        }
        
        $stmt_update->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Attendance Record</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-top: 10px;
        }
        input[type="time"], input[type="file"] {
            padding: 8px;
            margin-top: 5px;
        }
        .current-photo {
            margin-top: 5px;
        }
        img {
            max-width: 150px;
            height: auto;
            display: block;
            margin-top: 5px;
        }
        button {
            margin-top: 20px;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-link {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container">
        <h2>Update Attendance Record</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="update.php?id=<?php echo htmlspecialchars($attendance_id); ?>" enctype="multipart/form-data">
            <label for="check_in_time">Check-In Time:</label>
            <input type="time" id="check_in_time" name="check_in_time" value="<?php echo htmlspecialchars($attendance['check_in_time']); ?>" required>
            
            <div class="current-photo">
                <strong>Current Check-In Photo:</strong><br>
                <?php if (!empty($attendance['check_in_photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($attendance['check_in_photo_path']); ?>" alt="Check-In Photo">
                <?php else: ?>
                    <p>N/A</p>
                <?php endif; ?>
            </div>
            
            <label for="check_in_photo">Update Check-In Photo:</label>
            <input type="file" id="check_in_photo" name="check_in_photo" accept="image/*">
            
            <label for="check_out_time">Check-Out Time:</label>
            <input type="time" id="check_out_time" name="check_out_time" value="<?php echo htmlspecialchars($attendance['check_out_time']); ?>">
            
            <div class="current-photo">
                <strong>Current Check-Out Photo:</strong><br>
                <?php if (!empty($attendance['check_out_photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($attendance['check_out_photo_path']); ?>" alt="Check-Out Photo">
                <?php else: ?>
                    <p>N/A</p>
                <?php endif; ?>
            </div>
            
            <label for="check_out_photo">Update Check-Out Photo:</label>
            <input type="file" id="check_out_photo" name="check_out_photo" accept="image/*">
            
            <button type="submit">Update Record</button>
        </form>
        
        <div class="back-link">
            <a href="view.php">← Back to Attendance Records</a>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
