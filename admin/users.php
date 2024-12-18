<?php
session_start();
include('../config/db_connect.php');

// Check if the user is logged in and is the Owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Owner') {
    header("Location: ../index.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission to add or edit users
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $user_id = $_POST['user_id'];

    if ($action == 'add') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $role, $status);
        if ($stmt->execute()) {
            $message = "User added successfully.";
        } else {
            $error = "Error adding user: " . $stmt->error;
        }
    } elseif ($action == 'edit') {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ?, full_name = ?, role = ?, status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $username, $hashed_password, $full_name, $role, $status, $user_id);
        } else {
            $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            // Corrected bind_param (remove space in s si)
            $stmt->bind_param("ssssi", $username, $full_name, $role, $status, $user_id);
        }
        if ($stmt->execute()) {
            $message = "User updated successfully.";
        } else {
            $error = "Error updating user: " . $stmt->error;
        }
    }
}

$sql_users = "SELECT * FROM users WHERE role != 'Owner'";
$result_users = $conn->query($sql_users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Make page responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Attendance and Payslip Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        #userForm {
            padding: 20px;
            background: #f9f9f9;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
        }
        #userForm h3 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include('../includes/navbar.php'); ?>
    <div class="container mt-4">
        <h2>Manage Users</h2>
        <?php if ($message != ''): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error != ''): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" onclick="showAddForm()">Add New User</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_users->num_rows > 0): ?>
                        <?php while ($user = $result_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo $user['status']; ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" onclick="showEditForm(<?php echo $user['user_id']; ?>)">Edit</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit User Form (Hidden by default) -->
        <div id="userForm" style="display:none;">
            <h3 id="formTitle">Add New User</h3>
            <form action="users.php" method="POST" class="row g-3 needs-validation" novalidate>
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="user_id">

                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="text" class="form-control" name="full_name" id="full_name" required>
                    <div class="invalid-feedback">Please enter a full name.</div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" name="username" id="username" required>
                    <div class="invalid-feedback">Please enter a username.</div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Password: <small>(Leave blank if not changing)</small></label>
                    <input type="password" class="form-control" name="password" id="password">
                    <div class="invalid-feedback">Please enter a password (when adding a new user).</div>
                </div>

                <div class="col-md-3">
                    <label for="role" class="form-label">Role:</label>
                    <select class="form-select" name="role" id="role" required>
                        <option value="">--Select Role--</option>
                        <option value="Manager">Manager</option>
                        <option value="Admin Staff">Admin Staff</option>
                        <option value="Rider">Rider</option>
                    </select>
                    <div class="invalid-feedback">Please select a role.</div>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">Status:</label>
                    <select class="form-select" name="status" id="status" required>
                        <option value="">--Select Status--</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <div class="invalid-feedback">Please select a status.</div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('userForm').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Add New User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('user_id').value = '';
            document.getElementById('full_name').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('role').value = '';
            document.getElementById('status').value = '';
        }

        function showEditForm(userId) {
            // Re-fetch users data for JavaScript
            <?php
            // Build a fresh JSON object with user data
            $users_data = [];
            $result_users->data_seek(0); 
            while ($user = $result_users->fetch_assoc()) {
                $users_data[$user['user_id']] = [
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'status' => $user['status'],
                ];
            }
            echo "var usersData = " . json_encode($users_data) . ";";
            ?>

            var user = usersData[userId];
            document.getElementById('userForm').style.display = 'block';
            document.getElementById('formTitle').innerText = 'Edit User';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('user_id').value = userId;
            document.getElementById('full_name').value = user.full_name;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.status;
        }

        function hideForm() {
            document.getElementById('userForm').style.display = 'none';
        }

        // Bootstrap form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')

            Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    // If adding a user, password field must be required
                    if (document.getElementById('formAction').value === 'add') {
                        document.getElementById('password').required = true;
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
