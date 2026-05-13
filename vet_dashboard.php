<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'db_schema_helper.php';
$vet_id = $_SESSION['user_id'];

// Check schema
$has_vet_id = appointments_has_vet_id($conn);
$has_clinic_id = appointments_has_clinic_id($conn);

// Fetch vet information
$vet_stmt = mysqli_prepare($conn, "SELECT v.specialization, c.name as clinic_name FROM vets v LEFT JOIN clinics c ON v.clinic_id = c.clinic_id WHERE v.vet_id = ?");
if ($vet_stmt) {
    mysqli_stmt_bind_param($vet_stmt, 'i', $vet_id);
    mysqli_stmt_execute($vet_stmt);
    $vet_result = mysqli_stmt_get_result($vet_stmt);
    $vet_info = mysqli_fetch_assoc($vet_result);
    mysqli_stmt_close($vet_stmt);
} else {
    $vet_info = ['specialization' => 'General', 'clinic_name' => 'Not Assigned'];
}

$today = date('Y-m-d');
$today_appt_count = 0;
$today_appts = false;

// Fetch today's appointments - adapt based on schema
if ($has_vet_id) {
    $appt_stmt = mysqli_prepare($conn, "SELECT a.appointment_id, a.date, p.name as pet_name, p.species, u.first_name, u.last_name, s.name as service_name, p.pet_id FROM appointments a JOIN pets p ON a.pet_id = p.pet_id JOIN users u ON p.customer_id = u.user_id LEFT JOIN services s ON a.service_id = s.service_id WHERE a.vet_id = ? AND DATE(a.date) = ? AND a.status = 'Scheduled' ORDER BY a.date ASC");
    if ($appt_stmt) {
        mysqli_stmt_bind_param($appt_stmt, 'is', $vet_id, $today);
        mysqli_stmt_execute($appt_stmt);
        $today_appts = mysqli_stmt_get_result($appt_stmt);
        $today_appt_count = mysqli_num_rows($today_appts);
        mysqli_stmt_close($appt_stmt);
    }
} else {
    // Show all today's appointments if no vet_id column
    $appt_stmt = mysqli_prepare($conn, "SELECT a.appointment_id, a.date, p.name as pet_name, p.species, u.first_name, u.last_name, s.name as service_name, p.pet_id FROM appointments a JOIN pets p ON a.pet_id = p.pet_id JOIN users u ON p.customer_id = u.user_id LEFT JOIN services s ON a.service_id = s.service_id WHERE DATE(a.date) = ? AND a.status = 'Scheduled' ORDER BY a.date ASC");
    if ($appt_stmt) {
        mysqli_stmt_bind_param($appt_stmt, 's', $today);
        mysqli_stmt_execute($appt_stmt);
        $today_appts = mysqli_stmt_get_result($appt_stmt);
        $today_appt_count = mysqli_num_rows($today_appts);
        mysqli_stmt_close($appt_stmt);
    }
}

// Fetch upcoming appointments (next 7 days)
$upcoming_appt_count = 0;
if ($has_vet_id) {
    $upcoming_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM appointments WHERE vet_id = ? AND DATE(date) BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY) AND status = 'Scheduled'");
    if ($upcoming_stmt) {
        $week_date = $today;
        mysqli_stmt_bind_param($upcoming_stmt, 'iss', $vet_id, $today, $week_date);
        mysqli_stmt_execute($upcoming_stmt);
        $upcoming_result = mysqli_stmt_get_result($upcoming_stmt);
        $upcoming_row = mysqli_fetch_assoc($upcoming_result);
        $upcoming_appt_count = $upcoming_row['count'];
        mysqli_stmt_close($upcoming_stmt);
    }
} else {
    $upcoming_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM appointments WHERE DATE(date) BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY) AND status = 'Scheduled'");
    if ($upcoming_stmt) {
        mysqli_stmt_bind_param($upcoming_stmt, 'ss', $today, $today);
        mysqli_stmt_execute($upcoming_stmt);
        $upcoming_result = mysqli_stmt_get_result($upcoming_stmt);
        $upcoming_row = mysqli_fetch_assoc($upcoming_result);
        $upcoming_appt_count = $upcoming_row['count'];
        mysqli_stmt_close($upcoming_stmt);
    }
}

// Fetch recent medical records created
$today_records_count = 0;
$records_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM medical_records WHERE vet_id = ? AND DATE(visit_date) = ?");
if ($records_stmt) {
    mysqli_stmt_bind_param($records_stmt, 'is', $vet_id, $today);
    mysqli_stmt_execute($records_stmt);
    $records_result = mysqli_stmt_get_result($records_stmt);
    $records_row = mysqli_fetch_assoc($records_result);
    $today_records_count = $records_row['count'];
    mysqli_stmt_close($records_stmt);
}

// Fetch total patients treated by this vet
$total_patients = 0;
$patients_stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT pet_id) as count FROM medical_records WHERE vet_id = ?");
if ($patients_stmt) {
    mysqli_stmt_bind_param($patients_stmt, 'i', $vet_id);
    mysqli_stmt_execute($patients_stmt);
    $patients_result = mysqli_stmt_get_result($patients_stmt);
    $patients_row = mysqli_fetch_assoc($patients_result);
    $total_patients = $patients_row['count'];
    mysqli_stmt_close($patients_stmt);
}

// Fetch total prescriptions issued
$total_prescriptions = 0;
$rx_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM prescriptions p JOIN medical_records mr ON p.record_id = mr.record_id WHERE mr.vet_id = ?");
if ($rx_stmt) {
    mysqli_stmt_bind_param($rx_stmt, 'i', $vet_id);
    mysqli_stmt_execute($rx_stmt);
    $rx_result = mysqli_stmt_get_result($rx_stmt);
    $rx_row = mysqli_fetch_assoc($rx_result);
    $total_prescriptions = $rx_row['count'];
    mysqli_stmt_close($rx_stmt);
}

include 'vet_layout_header.php';
?>

<style>
    .stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 2rem;
        font-weight: 800;
        color: var(--brand-blue);
    }
    .stat-label {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        margin-top: 0.5rem;
    }
    .appointment-card {
        background: white;
        border-left: 4px solid var(--brand-blue);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }
    .appointment-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .clinic-info-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ebf3ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 12px;
        padding: 1.5rem;
    }
    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .activity-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-5">
    <div>
        <h2 class="fw-bold mb-2">Veterinarian Dashboard</h2>
        <p class="text-muted">Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['first_name']); ?></p>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-5">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number"><?php echo $today_appt_count; ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
                <i class="fas fa-calendar-check text-primary" style="font-size: 2.5rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number"><?php echo $upcoming_appt_count; ?></div>
                    <div class="stat-label">This Week</div>
                </div>
                <i class="fas fa-list text-success" style="font-size: 2.5rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
                <i class="fas fa-users text-info" style="font-size: 2.5rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-number"><?php echo $total_prescriptions; ?></div>
                    <div class="stat-label">Prescriptions</div>
                </div>
                <i class="fas fa-file-prescription text-warning" style="font-size: 2.5rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Schedule -->
    <div class="col-md-8">
        <h5 class="fw-bold mb-3">Today's Schedule</h5>
        <div class="glass-card shadow-sm">
            <?php if ($today_appt_count > 0 && $today_appts): ?>
                <?php while ($appt = mysqli_fetch_assoc($today_appts)): ?>
                    <div class="appointment-card">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6 class="fw-bold mb-0" style="color: var(--brand-blue);">
                                    <?php echo date('g:i A', strtotime($appt['date'])); ?>
                                </h6>
                                <small class="text-muted"><?php echo htmlspecialchars($appt['pet_name']); ?> &bull; <?php echo htmlspecialchars($appt['species']); ?></small>
                            </div>
                            <div class="col-md-4">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($appt['service_name'] ?? 'General Consult'); ?></small>
                            </div>
                            <div class="col-md-5 text-end">
                                <a href="vet_pet_records.php?pet_id=<?php echo (int)$appt['pet_id']; ?>" class="btn btn-sm btn-primary" style="background: var(--brand-blue); border: none;">
                                    <i class="fas fa-file-medical me-1"></i> Create Record
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-calendar-check fa-3x mb-3"></i>
                    <p>No appointments scheduled for today.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Side Panel -->
    <div class="col-md-4">
        <!-- Clinic Information -->
        <h5 class="fw-bold mb-3">Clinic Information</h5>
        <div class="clinic-info-card mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-white rounded-circle p-2 me-3 shadow-sm">
                    <i class="fas fa-hospital text-primary"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($vet_info['clinic_name'] ?? 'Not Assigned'); ?></h6>
                    <small class="text-muted">Primary Clinic</small>
                </div>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block"><strong>Specialization:</strong></small>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($vet_info['specialization'] ?? 'General Practice'); ?></span>
            </div>
            <div>
                <small class="text-muted d-block"><strong>Records Today:</strong></small>
                <span class="fw-bold" style="color: var(--brand-blue);"><?php echo $today_records_count; ?> new record<?php echo $today_records_count !== 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <!-- Recent Activity -->
        <h5 class="fw-bold mb-3">Recent Activity</h5>
        <div class="glass-card shadow-sm">
            <?php
            // Fetch last 3 medical records for activity feed
            $activity_stmt = mysqli_prepare($conn, "SELECT mr.visit_date, mr.diagnosis, p.name as pet_name FROM medical_records mr JOIN pets p ON mr.pet_id = p.pet_id WHERE mr.vet_id = ? ORDER BY mr.visit_date DESC LIMIT 3");
            $activity_count = 0;
            if ($activity_stmt) {
                mysqli_stmt_bind_param($activity_stmt, 'i', $vet_id);
                mysqli_stmt_execute($activity_stmt);
                $activity_result = mysqli_stmt_get_result($activity_stmt);
                while ($activity = mysqli_fetch_assoc($activity_result)) {
                    $activity_count++;
            ?>
                <div class="activity-item">
                    <div class="activity-icon bg-light text-primary">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div>
                        <p class="mb-0 fw-bold small"><?php echo htmlspecialchars($activity['pet_name']); ?></p>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($activity['diagnosis']); ?></p>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($activity['visit_date'])); ?></small>
                    </div>
                </div>
            <?php
                }
                mysqli_stmt_close($activity_stmt);
            }
            if ($activity_count === 0):
            ?>
                <div class="text-center p-3 text-muted">
                    <small>No recent activity</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'vet_layout_footer.php'; ?>