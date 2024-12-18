<?php
session_start();
include('config/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $profile_image);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_full_name = trim($_POST['full_name']);

    // Handle profile image upload if provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        // You should ensure the uploads directory is writable and consider security checks
        $imageFileType = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $new_image_name = "profile_" . $user_id . "." . $imageFileType;
        $target_file = $target_dir . $new_image_name;

        // Simple validation (You might want to add more checks here)
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if($check !== false) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Update the profile_image field in DB
                $profile_image = $new_image_name;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    // Update user info in DB
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, profile_image = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $new_full_name, $profile_image, $user_id);
    if($stmt->execute()) {
        // Update session username if changed
        $_SESSION['username'] = $new_full_name; // Or if full_name represents the display name
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error updating profile.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Profile</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit_profile.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
            </div>

            <div class="mb-3">
                <label for="profile_image" class="form-label">Profile Image</label>
                <?php if ($profile_image): ?>
                    <img src="uploads/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" width="100" height="100" class="d-block mb-3" style="border-radius:50%;">
                <?php endif; ?>
                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
