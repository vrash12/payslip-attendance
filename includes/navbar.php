<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nav_profile_image = null; // Initialize to null to avoid undefined variable warnings

if (isset($_SESSION['user_id'])) {
    include_once('../config/db_connect.php');
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($nav_profile_image);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Attendance & Payslip</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link px-3" href="/attendance%20payslip/dashboard.php">Home</a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Owner'): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/admin/users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/payslips/generate.php">Generate Payslips</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/admin/reports.php">View Reports</a>
                    </li>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/attendance/scan.php">Scan QR</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/attendance/view.php">View Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/payslips/view.php">View Payslips</a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center px-3" href="#" id="navbarDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <?php if(!empty($nav_profile_image)): ?>
                        <img src="uploads/<?php echo htmlspecialchars($nav_profile_image); ?>" alt="Profile" 
                            class="profile-image me-2">
                      <?php else: ?>
                        <img src="assets/img/default_profile.png" alt="Profile" 
                            class="profile-image me-2">
                      <?php endif; ?>
                      <span>Profile</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                      <li><a class="dropdown-item" href="edit_profile.php">Ediofile</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item" href="/attendance%20payslip/logout.php">Logout</a></li>
                    </ul>
                  </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="/attendance%20payslip/index.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Custom CSS -->
<style>
    .navbar-nav .nav-link {
        border-radius: 5px;
        transition: background-color 0.3s, color 0.3s;
    }

    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link:focus {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        text-decoration: none;
    }

    .profile-image {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Optional: Adjust dropdown menu styles */
    .dropdown-menu {
        border-radius: 3px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Optional: Add some padding to dropdown items */
    .dropdown-item {
        padding: 10px 20px;
    }
</style>
