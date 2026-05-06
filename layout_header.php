<?php 
ob_start(); // Start Output Buffering to prevent header errors
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'signUp.php', 'signupProcess.php'];

if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawHealth - Clinical Portal</title>
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
        body::before,
        body::after {
            content: '';
            position: fixed;
            pointer-events: none;
            z-index: 0;
            opacity: 0.5;
        }
        body::before {
            top: 28px;
            left: calc(var(--sidebar-width) + 44px);
            width: 84px;
            height: 84px;
            border-radius: 45% 55% 58% 42%;
            border: 2px dotted rgba(26, 167, 161, 0.28);
            background: radial-gradient(circle at 35% 30%, rgba(184, 242, 230, 0.7), rgba(255, 255, 255, 0.85) 58%);
            box-shadow:
                34px 18px 0 -28px rgba(19, 91, 159, 0.14),
                -20px 42px 0 -30px rgba(26, 167, 161, 0.12);
            transform: rotate(-10deg);
            animation: doodleFloatA 12s ease-in-out infinite;
        }
        body::after {
            right: 24px;
            bottom: 28px;
            width: 68px;
            height: 68px;
            border-radius: 34% 66% 47% 53%;
            border: 2px dashed rgba(19, 91, 159, 0.16);
            background: linear-gradient(135deg, rgba(216, 244, 255, 0.78), rgba(184, 242, 230, 0.18));
            transform: rotate(16deg);
            animation: doodleFloatB 10s ease-in-out infinite;
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
        .sidebar::after {
            content: '\f1b0';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -18px;
            top: 34%;
            font-size: 88px;
            color: rgba(19, 91, 159, 0.05);
            transform: rotate(-14deg);
            pointer-events: none;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 94px;
            width: 58px;
            height: 58px;
            border-radius: 48% 52% 43% 57%;
            background:
                radial-gradient(circle at 26% 24%, rgba(184, 242, 230, 0.8) 0 14%, transparent 15%),
                radial-gradient(circle at 76% 22%, rgba(216, 244, 255, 0.8) 0 14%, transparent 15%),
                radial-gradient(circle at 26% 74%, rgba(19, 91, 159, 0.12) 0 10%, transparent 11%),
                radial-gradient(circle at 76% 72%, rgba(26, 167, 161, 0.12) 0 10%, transparent 11%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.88), rgba(255, 247, 236, 0.94));
            border: 2px solid rgba(26, 167, 161, 0.14);
            box-shadow: 16px 24px 0 -22px rgba(19, 91, 159, 0.1);
            transform: rotate(20deg);
            pointer-events: none;
            animation: doodleFloatC 11s ease-in-out infinite;
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
            background-image:
                radial-gradient(circle at 12% 84%, rgba(123, 223, 242, 0.1) 0 3px, transparent 4px),
                radial-gradient(circle at 84% 18%, rgba(26, 167, 161, 0.08) 0 3px, transparent 4px),
                radial-gradient(circle at 76% 76%, rgba(184, 242, 230, 0.12) 0 4px, transparent 5px);
            background-repeat: no-repeat;
        }
        .main-content::before,
        .main-content::after {
            content: '';
            position: fixed;
            pointer-events: none;
            z-index: 0;
            opacity: 0.45;
        }
        .main-content::before {
            top: 108px;
            right: 118px;
            width: 110px;
            height: 110px;
            border-radius: 52% 48% 40% 60%;
            background:
                radial-gradient(circle at 28% 30%, rgba(123, 223, 242, 0.42) 0 16%, transparent 17%),
                radial-gradient(circle at 74% 28%, rgba(242, 181, 212, 0.38) 0 15%, transparent 16%),
                radial-gradient(circle at 26% 76%, rgba(184, 242, 230, 0.28) 0 12%, transparent 13%),
                radial-gradient(circle at 76% 74%, rgba(26, 167, 161, 0.22) 0 12%, transparent 13%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(216, 244, 255, 0.65));
            border: 2px solid rgba(123, 223, 242, 0.22);
            box-shadow:
                0 16px 0 -11px rgba(19, 91, 159, 0.1),
                -168px 148px 0 -82px rgba(26, 167, 161, 0.12),
                -220px 54px 0 -88px rgba(123, 223, 242, 0.11);
            transform: rotate(10deg);
            animation: doodleFloatD 13s ease-in-out infinite;
        }
        .main-content::after {
            left: calc(var(--sidebar-width) + 92px);
            bottom: 82px;
            width: 78px;
            height: 78px;
            border-radius: 38% 62% 56% 44%;
            border: 2px dashed rgba(26, 167, 161, 0.22);
            background: radial-gradient(circle at 35% 28%, rgba(255, 255, 255, 0.82), rgba(184, 242, 230, 0.28));
            box-shadow:
                190px -120px 0 -54px rgba(19, 91, 159, 0.12),
                248px -38px 0 -60px rgba(26, 167, 161, 0.1);
            transform: rotate(-18deg);
            animation: doodleFloatE 11s ease-in-out infinite;
        }
        .main-content > * {
            position: relative;
            z-index: 1;
        }
        .glass-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 24px -16px rgba(19, 91, 159, 0.28);
        }
        .btn {
            transition: transform 0.18s ease, box-shadow 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 16px -12px rgba(0, 0, 0, 0.35);
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
        .main-content > :nth-child(1) { animation: fadeLift 0.35s ease both; }
        .main-content > :nth-child(2) { animation: fadeLift 0.45s ease both; }
        .main-content > :nth-child(3) { animation: fadeLift 0.55s ease both; }

        @keyframes fadeLift {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes doodleFloatD {
            0%, 100% { transform: rotate(10deg) translateY(0); }
            50% { transform: rotate(14deg) translateY(8px); }
        }

        @keyframes doodleFloatE {
            0%, 100% { transform: rotate(-18deg) translateY(0); }
            50% { transform: rotate(-14deg) translateY(-7px); }
        }

        @keyframes doodleFloatA {
            0%, 100% { transform: rotate(-10deg) translateY(0); }
            50% { transform: rotate(-7deg) translateY(7px); }
        }

        @keyframes doodleFloatB {
            0%, 100% { transform: rotate(16deg) translateY(0); }
            50% { transform: rotate(13deg) translateY(-6px); }
        }

        @keyframes doodleFloatC {
            0%, 100% { transform: rotate(20deg) translateY(0); }
            50% { transform: rotate(16deg) translateY(-5px); }
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
            <small class="text-muted fw-bold" style="font-size: 0.6rem; text-transform: uppercase;">Clinical Portal</small>
        </div>
    </div>

    <nav class="flex-grow-1">
        <a href="dashboard.php" class="nav-link-box <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="mypet.php" class="nav-link-box <?= ($current_page == 'mypet.php' || $current_page == 'addpet.php' || $current_page == 'petdetails.php' || $current_page == 'add_prescription.php') ? 'active' : '' ?>">
            <i class="fas fa-paw"></i> My Pets
        </a>
        <a href="service.php" class="nav-link-box <?= ($current_page == 'service.php') ? 'active' : '' ?>">
            <i class="fas fa-hand-holding-medical"></i> Services
        </a>
        <a href="medicine.php" class="nav-link-box <?= ($current_page == 'medicine.php') ? 'active' : '' ?>">
            <i class="fas fa-pills"></i> Pharmacy
        </a>
        <a href="clinic_locator.php" class="nav-link-box <?= ($current_page == 'clinic_locator.php' || $current_page == 'book_appointment.php' || $current_page == 'confirm_booking.php') ? 'active' : '' ?>">
            <i class="fas fa-map-marker-alt"></i> Clinic Locator
        </a>
    </nav>

    <a href="settings.php" class="nav-link-box <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Settings
    </a>

    <a href="logout.php" class="nav-link-box text-danger mt-auto">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
</div>
<div class="main-content">