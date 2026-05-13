<?php 
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Security check: Only vets can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$vet_name = htmlspecialchars($_SESSION['first_name'] ?? 'Vet');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawHealth - Veterinarian Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-blue: #135b9f;
            --accent: #1aa7a1;
            --mint: #b8f2e6;
            --peach: #d8f4ff;
            --sidebar-width: 260px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 18% 14%, rgba(26, 167, 161, 0.08), transparent 20%),
                radial-gradient(circle at 86% 20%, rgba(19, 91, 159, 0.08), transparent 18%),
                linear-gradient(180deg, #fffdf8 0%, #f6fbff 100%);
            color: #1e293b;
            margin: 0;
            overflow-x: hidden;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: white;
            border-right: 1px solid #f1f5f9;
            padding: 2rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .nav-link-box {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            border: 1px solid transparent;
        }
        .nav-link-box:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: var(--brand-blue);
            transform: translateX(4px);
        }
        .nav-link-box.active {
            background: #ebf3ff;
            color: #0f5b78;
            border-color: #dbeafe;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2.5rem;
            min-height: 100vh;
            position: relative;
        }
        .glass-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 24px -16px rgba(19, 91, 159, 0.28);
        }
        .brand-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(135deg, var(--brand-blue), #1f79c4 60%, #49a9ee);
            box-shadow: 0 8px 16px -10px rgba(19, 91, 159, 0.8);
        }
        /* Top bar user info */
        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .user-info-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            padding: 6px 16px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-blue), #1aa7a1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="d-flex align-items-center mb-4 px-2">
        <div class="brand-badge me-2">
            <i class="fas fa-paw"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-0" style="color: var(--brand-blue);">PawHealth</h5>
            <small class="text-muted fw-bold" style="font-size: 0.6rem; text-transform: uppercase;">Veterinarian Portal</small>
        </div>
    </div>

    <nav class="flex-grow-1">
        <a href="vet_dashboard.php" class="nav-link-box <?= ($current_page == 'vet_dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="vet_appointments.php" class="nav-link-box <?= ($current_page == 'vet_appointments.php') ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Appointments
        </a>
        <a href="vet_pet_info.php" class="nav-link-box <?= ($current_page == 'vet_pet_info.php') ? 'active' : '' ?>">
            <i class="fas fa-paw"></i> Pet Information
        </a>
        <a href="vet_medical_records.php" class="nav-link-box <?= ($current_page == 'vet_medical_records.php' || $current_page == 'vet_record_details.php') ? 'active' : '' ?>">
            <i class="fas fa-file-medical"></i> Medical Records
        </a>
        <a href="vet_patients.php" class="nav-link-box <?= ($current_page == 'vet_patients.php') ? 'active' : '' ?>">
            <i class="fas fa-users"></i> My Patients
        </a>
    </nav>

    <div style="margin-top: auto;">
        <div class="nav-divider" style="height: 1px; background: #f1f5f9; margin: 1rem 0;"></div>

        <a href="settings.php" class="nav-link-box <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Settings
        </a>

        <a href="logout.php" class="nav-link-box text-danger">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
    </div>
</div>
<div class="main-content">
    <!-- Top Navigation Bar with User Info -->
    <div class="top-nav-bar">
        <div>
            <small class="text-muted fw-bold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">
                <i class="fas fa-shield-alt me-1 text-success"></i> Verified Veterinarian Account
            </small>
        </div>
        <div class="user-info-pill">
            <div class="user-avatar">
                <i class="fas fa-user-md"></i>
            </div>
            <div>
                <span class="fw-bold small" style="color: var(--brand-blue);">Dr. <?= $vet_name ?></span>
                <span class="text-muted" style="font-size: 0.7rem; display: block;">Online</span>
            </div>
        </div>
    </div>