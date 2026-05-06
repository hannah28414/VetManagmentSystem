<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$user_id = $_SESSION['user_id'];

$top_pet_details_url = 'mypet.php';
$top_pet_stmt = mysqli_prepare($conn, "SELECT pet_id FROM pets WHERE customer_id = ? ORDER BY pet_id ASC LIMIT 1");
if ($top_pet_stmt) {
    mysqli_stmt_bind_param($top_pet_stmt, 'i', $user_id);
    mysqli_stmt_execute($top_pet_stmt);
    $top_pet_result = mysqli_stmt_get_result($top_pet_stmt);
    if ($top_pet_result && ($top_pet = mysqli_fetch_assoc($top_pet_result))) {
        $top_pet_details_url = 'petdetails.php?id=' . (int)$top_pet['pet_id'];
    }
    mysqli_stmt_close($top_pet_stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_pet_id'], $_POST['cancel_appt_date'])) {
    $cancel_pet_id = (int) $_POST['cancel_pet_id'];
    $cancel_appt_date = $_POST['cancel_appt_date'];

    $cancel_stmt = mysqli_prepare(
        $conn,
        "UPDATE appointments a
         JOIN pets p ON a.pet_id = p.pet_id
         SET a.status = 'Cancelled'
         WHERE a.pet_id = ? AND a.date = ? AND p.customer_id = ? AND a.status = 'Scheduled'
         LIMIT 1"
    );
    mysqli_stmt_bind_param($cancel_stmt, 'isi', $cancel_pet_id, $cancel_appt_date, $user_id);
    mysqli_stmt_execute($cancel_stmt);

    if (mysqli_stmt_affected_rows($cancel_stmt) > 0) {
        mysqli_stmt_close($cancel_stmt);
        header('Location: dashboard.php?appt=cancelled');
        exit();
    }

    mysqli_stmt_close($cancel_stmt);
    header('Location: dashboard.php?appt=cancel_failed');
    exit();
}

// Get actual counts from the database
$pet_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM pets WHERE customer_id = $user_id"))['count'];
$appt_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments a JOIN pets p ON a.pet_id = p.pet_id WHERE p.customer_id = $user_id AND a.status = 'Scheduled'"))['count'];
$booking_success = isset($_GET['booking']) && $_GET['booking'] === 'success';

include 'layout_header.php';
?>

<style>
    .card-custom {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .stat-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        background: white;
        min-width: 160px;
    }
    .stat-card h4 { font-size: 1.5rem; }
    .stat-card .bg-light { padding: 0.5rem !important; }

    .card-action-box {
        padding: 1.25rem !important;
        transition: transform 0.2s;
        display: block;
    }
    .card-action-box:hover { transform: translateY(-3px); }
    .card-action-box h5 { font-size: 1.1rem; }
    .card-action-box p { font-size: 0.85rem; }

    .hero-card {
        color: white;
        border-radius: 1rem;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        z-index: 1;
        border: none;
    }
    .hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0, 59, 92, 0.95) 10%, rgba(0, 59, 92, 0.4) 100%), url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&q=80&w=1000') center/cover;
        transition: transform 0.5s ease-out;
        z-index: -1;
    }
    .hero-card:hover::before { transform: scale(1.08); }

    .search-bar {
        background-color: #f1f5f9;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 1.5rem;
    }

    .pet-avatar {
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
    }
    .pet-avatar:hover { transform: scale(1.15) rotate(5deg); }
</style>
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div class="position-relative w-25">
                <i class="fas fa-search position-absolute text-muted" style="top: 10px; left: 15px;"></i>
                <input type="text" class="form-control search-bar ps-5" placeholder="Search records...">
            </div>
            <div class="d-flex align-items-center gap-3 text-muted">
                <i class="far fa-bell fs-5"></i>
                <i class="far fa-question-circle fs-5"></i>
                <a href="settings.php" class="text-muted text-decoration-none" title="Settings">
                    <i class="fas fa-cog fs-5"></i>
                </a>
                <a href="<?php echo htmlspecialchars($top_pet_details_url); ?>" class="text-decoration-none" title="Go to pet details">
                    <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=100&q=80" class="rounded-circle ms-2 pet-avatar" width="40" height="40" alt="Pet Avatar">
                </a>
            </div>
        </div>

        <div class="row align-items-end mb-4">
            <div class="col-md-7">
                <h2 class="fw-bold" style="color: var(--brand-blue);">Good Morning, <?php echo $_SESSION['first_name']; ?></h2>
                <p class="text-secondary mb-0">Here is the latest overview of your pets' clinical vitality.</p>
            </div>
            <div class="col-md-5 d-flex justify-content-end gap-2">
                <div class="stat-card d-flex align-items-center shadow-sm">
                    <div class="bg-light rounded-circle p-2 me-3 text-primary"><i class="fas fa-paw"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $pet_count; ?></h4>
                        <small class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Active Pets</small>
                    </div>
                </div>
                <div class="stat-card d-flex align-items-center shadow-sm">
                    <div class="bg-light rounded-circle p-2 me-3 text-primary"><i class="far fa-calendar"></i></div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $appt_count; ?></h4>
                        <small class="text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Upcoming</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-8">
                <div class="hero-card shadow-lg">
                    <span class="badge bg-light text-dark w-auto mb-3 px-3 py-2 rounded-pill" style="align-self: flex-start; font-size: 0.75rem;"><i class="fas fa-file-medical me-2"></i>Clinical Records</span>
                    <h3 class="fw-bold mb-2">Manage My Pets</h3>
                    <p class="opacity-75 mb-4" style="max-width: 60%; font-size: 0.95rem;">Review vaccination history, recent diagnoses, and ongoing treatment plans.</p>
                    <a href="mypet.php" class="text-white text-decoration-none fw-bold" style="letter-spacing: 0.5px; font-size: 0.85rem;">VIEW PROFILES <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
            <div class="col-md-4 d-flex flex-column gap-3">
                
                <a href="clinic_locator.php" class="card card-custom card-action-box h-50 p-3 text-decoration-none" style="background-color: var(--brand-light); border: 2px dashed #B8D4E3;">
                    <i class="fas fa-map-marker-alt text-primary fs-5 mb-2"></i>
                    <h5 class="fw-bold mb-1" style="color: var(--brand-blue);">Find a Clinic</h5>
                    <p class="text-muted small mb-0">Locate specialized care near you.</p>
                </a>
                
                <a href="book_appointment.php" class="card card-custom card-action-box h-50 p-3 text-decoration-none" style="background-color: var(--brand-blue); color: white;">
                    <i class="far fa-calendar-plus fs-5 mb-2"></i>
                    <h5 class="fw-bold mb-1">Book Appointment</h5>
                    <p class="opacity-75 small mb-0">Schedule routine checkups or consults.</p>
                </a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold" style="color: var(--brand-blue);">Upcoming Appointments</h5>
            <a href="book_appointment.php" class="text-decoration-none small fw-bold">View Schedule <i class="fas fa-chevron-right ms-1"></i></a>
        </div>

        <?php if (isset($_GET['appt']) && $_GET['appt'] === 'cancelled'): ?>
            <div class="alert alert-success py-2">Appointment cancelled successfully.</div>
        <?php elseif (isset($_GET['appt']) && $_GET['appt'] === 'cancel_failed'): ?>
            <div class="alert alert-danger py-2">Unable to cancel appointment.</div>
        <?php elseif ($booking_success): ?>
            <div class="alert alert-success py-2 new-appointment-success">
                <i class="fas fa-check-circle success-check-pop me-2"></i>Appointment booked successfully.
            </div>
        <?php endif; ?>
        
        <div class="card card-custom p-0 overflow-hidden shadow-sm">
            <ul class="list-group list-group-flush">
                
                <?php
                // Fetch UPCOMING APPOINTMENTS linked to the user's pets
                $appt_query = "
                    SELECT a.pet_id, a.clinic_id, p.name AS pet_name, p.species, a.date,
                           s.name AS service_name, c.name AS clinic_name, c.address AS clinic_address
                    FROM appointments a
                    JOIN pets p ON a.pet_id = p.pet_id
                    LEFT JOIN services s ON a.service_id = s.service_id
                    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
                    WHERE p.customer_id = $user_id AND a.status = 'Scheduled'
                    ORDER BY a.date ASC LIMIT 3";
                
                $appt_result = mysqli_query($conn, $appt_query);
                
                if (mysqli_num_rows($appt_result) > 0) {
                    $row_index = 0;
                    while ($appt = mysqli_fetch_assoc($appt_result)) {
                        $formatted_date = date("M d, Y - g:i A", strtotime($appt['date']));
                        $service = $appt['service_name'] ? $appt['service_name'] : "General Consult";
                        $clinic_name = $appt['clinic_name'] ? htmlspecialchars($appt['clinic_name']) : 'Clinic not set';
                        $clinic_address = $appt['clinic_address'] ? htmlspecialchars($appt['clinic_address']) : 'Address unavailable';
                        $clinic_map_link = 'clinic_locator.php?clinic_id=' . (int)$appt['clinic_id'];
                        $row_animation_class = ($booking_success && $row_index === 0) ? ' new-appointment-success' : '';

                        echo '
                        <li class="list-group-item p-4 d-flex justify-content-between align-items-center border-bottom-1' . $row_animation_class . '">
                            <div class="d-flex align-items-center">
                                <div class="bg-dark rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-calendar-check text-white fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 fs-5" style="color: var(--brand-blue);">' . $appt['pet_name'] . ' <i class="fas fa-circle text-primary ms-1" style="font-size: 0.4rem; vertical-align: middle;"></i></h6>
                                    <span class="badge bg-light text-primary me-2">' . $appt['species'] . '</span>
                                    <small class="text-muted fw-bold">' . $service . '</small>
                                    <div class="small text-secondary mt-1"><i class="fas fa-clinic-medical me-1"></i>' . $clinic_name . '</div>
                                    <div class="small text-muted">' . $clinic_address . ' · <a href="' . $clinic_map_link . '" class="text-decoration-none">View on map</a></div>
                                </div>
                            </div>
                            <div class="text-end me-4">
                                <h6 class="fw-bold mb-1" style="color: var(--brand-blue);">Scheduled</h6>
                                <small class="text-secondary"><i class="far fa-clock me-1"></i>' . $formatted_date . '</small>
                            </div>
                            <form method="POST" class="mb-0 pe-3" onsubmit="return confirm(\'Cancel this appointment?\');">
                                <input type="hidden" name="cancel_pet_id" value="' . (int)$appt['pet_id'] . '">
                                <input type="hidden" name="cancel_appt_date" value="' . htmlspecialchars($appt['date'], ENT_QUOTES) . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">Cancel</button>
                            </form>
                        </li>';
                        $row_index++;
                    }
                } else {
                    echo '<li class="list-group-item p-4 text-center text-muted">No upcoming appointments scheduled.</li>';
                }
                ?>

            </ul>
        </div>

<?php include 'layout_footer.php'; ?>