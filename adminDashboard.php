<?php
session_start();
// Security Check: Kick them out if they aren't logged in OR aren't an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'header.php'; 

// Fetch quick system stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Customer'"))['count'];
$total_pets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM pets"))['count'];
$total_appts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments"))['count'];
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge bg-danger mb-2">Administrator</span>
            <h1 class="fw-bold text-dark">System Overview</h1>
        </div>
        <a href="logout.php" class="btn btn-outline-danger shadow-sm">Sign Out</a>
    </div>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75 fw-bold">Total Clients</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $total_users; ?></h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75 fw-bold">Registered Pets</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $total_pets; ?></h2>
                    </div>
                    <i class="fas fa-paw fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75 fw-bold">Total Appointments</h6>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $total_appts; ?></h2>
                    </div>
                    <i class="far fa-calendar-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <h4 class="fw-bold mb-3">Quick Actions</h4>
    <div class="row">
        <div class="col-md-3 mb-3">
            <button class="btn btn-light w-100 p-4 border shadow-sm text-start">
                <i class="fas fa-plus-circle text-primary mb-2 fa-2x"></i><br>
                <span class="fw-bold">Register New Clinic</span>
            </button>
        </div>
        <div class="col-md-3 mb-3">
            <button class="btn btn-light w-100 p-4 border shadow-sm text-start">
                <i class="fas fa-user-md text-success mb-2 fa-2x"></i><br>
                <span class="fw-bold">Manage Veterinarians</span>
            </button>
        </div>
        <div class="col-md-3 mb-3">
            <button class="btn btn-light w-100 p-4 border shadow-sm text-start">
                <i class="fas fa-file-medical text-danger mb-2 fa-2x"></i><br>
                <span class="fw-bold">View System Logs</span>
            </button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>