<?php
// setup_users.php

/**
 * This script inserts four predefined users into the 'users' table.
 * 
 * **User Credentials:**
 * 
 * | Role        | Username     | Password       | Full Name        |
 * |-------------|--------------|----------------|-------------------|
 * | Owner       | owner_user   | OwnerPass123    | Owner Name        |
 * | Manager     | manager_user | ManagerPass123  | Manager Name      |
 * | Admin Staff | admin_user   | AdminPass123    | Admin Staff Name  |
 * | Rider       | rider_user   | RiderPass123    | Rider Name        |
 * 
 * **Note:** Ensure that the 'users' table exists as per the provided schema before running this script.
 * After running, it's recommended to delete or secure this script to prevent unauthorized access.
 */

// Database configuration
$servername = "localhost";        // Replace with your server name
$db_username = "root";// Replace with your database username
$db_password = "";// Replace with your database password
$dbname     = "attendance_payslip_db"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define users to insert
$users = [
    [
        'username'   => 'owner_user',
        'password'   => 'OwnerPass123',
        'full_name'  => 'Owner Name',
        'role'       => 'Owner',
        'status'     => 'Active'
    ],
    [
        'username'   => 'manager_user',
        'password'   => 'ManagerPass123',
        'full_name'  => 'Manager Name',
        'role'       => 'Manager',
        'status'     => 'Active'
    ],
    [
        'username'   => 'admin_user',
        'password'   => 'AdminPass123',
        'full_name'  => 'Admin Staff Name',
        'role'       => 'Admin Staff',
        'status'     => 'Active'
    ],
    [
        'username'   => 'rider_user',
        'password'   => 'RiderPass123',
        'full_name'  => 'Rider Name',
        'role'       => 'Rider',
        'status'     => 'Active'
    ]
];

// Prepare the SQL statement
$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");

if (!$stmt) {
    die("Preparation failed: (" . $conn->errno . ") " . $conn->error);
}

// Bind parameters
$stmt->bind_param("sssss", $username, $hashed_password, $full_name, $role, $status);

// Insert each user
foreach ($users as $user) {
    $username   = $user['username'];
    $password   = $user['password'];
    $full_name  = $user['full_name'];
    $role       = $user['role'];
    $status     = $user['status'];
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "User '{$username}' inserted successfully.<br>";
    } else {
        // Handle duplicate entries or other errors
        if ($conn->errno == 1062) { // Duplicate entry error code
            echo "User '{$username}' already exists. Skipping insertion.<br>";
        } else {
            echo "Error inserting user '{$username}': " . $stmt->error . "<br>";
        }
    }
}

// Close the statement and connection
$stmt->close();
$conn->close();

echo "<br>User setup completed.";
?>
