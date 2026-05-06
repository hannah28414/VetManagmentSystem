<style>
    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        background: white;
        border-right: 1px solid #f1f5f9;
        padding: 2rem;
        z-index: 1000;
    }

    /* Modern Logo Style from your image */
    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 3rem;
        text-decoration: none;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background-color: #0d6efd; /* The bright blue from your image */
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .logo-text {
        color: #003B5C;
        font-weight: 700;
        font-size: 1.4rem;
        margin: 0;
    }

    /* Nav Item Styling */
    .nav-item-custom {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 0.8rem 1rem;
        color: #0d6efd; /* Blue text from your image */
        text-decoration: none;
        font-weight: 500;
        font-size: 1.1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin-bottom: 0.5rem;
    }

    .nav-item-custom i {
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
    }

    .nav-item-custom:hover {
        background-color: #f8fafc;
        transform: translateX(5px);
        color: #0056b3;
    }

    .nav-item-custom.active {
        background-color: #ebf3ff;
        font-weight: 600;
    }
</style>

<div class="sidebar">
    <a href="dashboard.php" class="sidebar-logo">
        <div class="logo-icon">
            <i class="fas fa-paw"></i>
        </div>
        <h2 class="logo-text">PawHealth</h2>
    </a>

    <nav class="d-flex flex-column">
        <a href="dashboard.php" class="nav-item-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        
        <a href="clinic_locator.php" class="nav-item-custom <?php echo (basename($_SERVER['PHP_SELF']) == 'clinic_locator.php') ? 'active' : ''; ?>">
            <i class="fas fa-map-marker-alt"></i> Clinic Locator
        </a>

        <a href="#" class="nav-item-custom">
            <i class="fas fa-paw"></i> My Pets
        </a>
        
        <a href="#" class="nav-item-custom">
            <i class="far fa-calendar-alt"></i> Schedule
        </a>
    </nav>

    <div style="position: absolute; bottom: 2rem; width: calc(100% - 4rem);">
        <a href="logout.php" class="nav-item-custom text-danger">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
    </div>
</div>