<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'db_schema_helper.php';

$vet_id = (int)($_SESSION['user_id'] ?? 0);
$message = '';
$error = '';

// Check if appointments table has vet_id column
$has_vet_id = appointments_has_vet_id($conn);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $new_status = '';
    if ($action === 'accept') $new_status = 'Scheduled';
    elseif ($action === 'completed') $new_status = 'Completed';
    elseif ($action === 'cancel') $new_status = 'Cancelled';

    if ($new_status !== '') {
        if ($has_vet_id) {
            $update_stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE appointment_id = ? AND vet_id = ?");
            mysqli_stmt_bind_param($update_stmt, 'sii', $new_status, $appointment_id, $vet_id);
        } else {
            // If no vet_id column, update without vet check (less secure but works)
            $update_stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE appointment_id = ?");
            mysqli_stmt_bind_param($update_stmt, 'si', $new_status, $appointment_id);
        }

        if (mysqli_stmt_execute($update_stmt)) {
            $message = 'Appointment updated successfully.';
        }
        mysqli_stmt_close($update_stmt);
    }

    header('Location: vet_appointments.php?msg=' . urlencode($message));
    exit();
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Fetch appointments - adapt query based on schema
if ($has_vet_id) {
    $appts_stmt = mysqli_prepare(
        $conn,
        "SELECT a.appointment_id, a.date, a.status, p.name AS pet_name, p.species, u.first_name, u.last_name, 
                s.name AS service_name, p.pet_id
         FROM appointments a
         JOIN pets p ON a.pet_id = p.pet_id
         JOIN users u ON p.customer_id = u.user_id
         LEFT JOIN services s ON a.service_id = s.service_id
         WHERE a.vet_id = ?
         ORDER BY a.date DESC"
    );
    mysqli_stmt_bind_param($appts_stmt, 'i', $vet_id);
} else {
    // If no vet_id in appointments, show all appointments (fallback)
    $appts_stmt = mysqli_prepare(
        $conn,
        "SELECT a.appointment_id, a.date, a.status, p.name AS pet_name, p.species, u.first_name, u.last_name, 
                s.name AS service_name, p.pet_id
         FROM appointments a
         JOIN pets p ON a.pet_id = p.pet_id
         JOIN users u ON p.customer_id = u.user_id
         LEFT JOIN services s ON a.service_id = s.service_id
         ORDER BY a.date DESC"
    );
}

mysqli_stmt_execute($appts_stmt);
$appointments = mysqli_stmt_get_result($appts_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold mb-1" style="color: var(--brand-blue);">Appointment Management</h2>
    <p class="text-muted">View, accept, and manage all patient appointments.</p>
    <?php if (!$has_vet_id): ?>
        <div class="alert alert-warning py-2">
            <small><i class="fas fa-exclamation-triangle me-2"></i>Note: Appointments are not linked to specific vets in this database configuration.</small>
        </div>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="glass-card shadow-sm p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE & TIME</th>
                    <th class="border-0 py-3 small fw-bold text-muted">PATIENT</th>
                    <th class="border-0 py-3 small fw-bold text-muted">OWNER</th>
                    <th class="border-0 py-3 small fw-bold text-muted">SERVICE</th>
                    <th class="border-0 py-3 small fw-bold text-muted">STATUS</th>
                    <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 0;
                while ($appt = mysqli_fetch_assoc($appointments)): 
                    $count++;
                    $appt_date = date('M d, Y g:i A', strtotime($appt['date']));
                    $status_badge = '';

                    switch ($appt['status']) {
                        case 'Scheduled':
                            $status_badge = '<span class="badge bg-warning text-dark">Scheduled</span>';
                            break;
                        case 'Completed':
                            $status_badge = '<span class="badge bg-success">Completed</span>';
                            break;
                        case 'Cancelled':
                            $status_badge = '<span class="badge bg-danger">Cancelled</span>';
                            break;
                        default:
                            $status_badge = '<span class="badge bg-light text-dark">' . htmlspecialchars($appt['status']) . '</span>';
                    }
                ?>
                <tr style="vertical-align: middle;">
                    <td class="ps-4 py-3 fw-bold"><?php echo $appt_date; ?></td>
                    <td class="py-3">
                        <a href="vet_pet_info.php?pet_id=<?php echo (int)$appt['pet_id']; ?>" class="text-decoration-none" style="color: var(--brand-blue);">
                            <?php echo htmlspecialchars($appt['pet_name']); ?>
                        </a>
                        <br>
                        <small class="text-muted"><?php echo htmlspecialchars($appt['species']); ?></small>
                    </td>
                    <td class="py-3"><?php echo htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']); ?></td>
                    <td class="py-3 text-muted"><?php echo htmlspecialchars($appt['service_name'] ?? 'General Consult'); ?></td>
                    <td class="py-3"><?php echo $status_badge; ?></td>
                    <td class="pe-4 py-3 text-end">
                        <div class="btn-group" role="group">
                            <?php if ($appt['status'] === 'Scheduled'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                    <input type="hidden" name="action" value="completed">
                                    <button type="submit" class="btn btn-sm btn-success fw-bold">Complete</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-sm btn-outline-danger fw-bold" onclick="return confirm('Cancel this appointment?');">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php 
                endwhile;

                if ($count === 0): 
                ?>
                <tr>
                    <td colspan="6" class="text-center p-5 text-muted">No appointments scheduled.</td>
                </tr>
                <?php 
                endif;
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
mysqli_stmt_close($appts_stmt);
include 'vet_layout_footer.php'; 
?>