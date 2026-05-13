<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
include 'db_schema_helper.php';

$vet_id = (int)($_SESSION['user_id'] ?? 0);
$pet_id = (int)($_GET['pet_id'] ?? 0);
$message = '';
$error = '';

// Check schema
$has_vet_id = appointments_has_vet_id($conn);

// Fetch pet info - adapt query based on schema
if ($has_vet_id) {
    $pet_stmt = mysqli_prepare(
        $conn,
        "SELECT p.pet_id, p.name, p.species, p.breed, p.age, p.weight, u.first_name, u.last_name
         FROM pets p
         JOIN users u ON p.customer_id = u.user_id
         JOIN appointments a ON p.pet_id = a.pet_id
         WHERE p.pet_id = ? AND a.vet_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($pet_stmt, 'ii', $pet_id, $vet_id);
} else {
    // Fallback without vet_id check
    $pet_stmt = mysqli_prepare(
        $conn,
        "SELECT p.pet_id, p.name, p.species, p.breed, p.age, p.weight, u.first_name, u.last_name
         FROM pets p
         JOIN users u ON p.customer_id = u.user_id
         WHERE p.pet_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($pet_stmt, 'i', $pet_id);
}

mysqli_stmt_execute($pet_stmt);
$pet_result = mysqli_stmt_get_result($pet_stmt);
$pet = mysqli_fetch_assoc($pet_result);
mysqli_stmt_close($pet_stmt);

if (!$pet) {
    header("Location: vet_pet_info.php");
    exit();
}

// Handle new medical record submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $visit_date = $_POST['visit_date'] ?? date('Y-m-d');

    if ($diagnosis === '') {
        $error = 'Diagnosis is required.';
    } else {
        $record_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO medical_records (visit_date, diagnosis, notes, pet_id, vet_id) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($record_stmt, 'sssii', $visit_date, $diagnosis, $notes, $pet_id, $vet_id);

        if (mysqli_stmt_execute($record_stmt)) {
            $message = 'Medical record saved successfully.';
        } else {
            $error = 'Failed to save medical record.';
        }
        mysqli_stmt_close($record_stmt);
    }
}

// Fetch existing medical records for this pet (always filter by vet_id in medical_records)
$records_stmt = mysqli_prepare(
    $conn,
    "SELECT record_id, visit_date, diagnosis, notes FROM medical_records WHERE pet_id = ? AND vet_id = ? ORDER BY visit_date DESC"
);
mysqli_stmt_bind_param($records_stmt, 'ii', $pet_id, $vet_id);
mysqli_stmt_execute($records_stmt);
$records = mysqli_stmt_get_result($records_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-4">
    <a href="vet_pet_info.php" class="text-decoration-none text-muted small fw-bold">
        <i class="fas fa-chevron-left me-2"></i>BACK TO PET LIST
    </a>
</div>

<div class="glass-card shadow-sm mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold mb-1" style="color: var(--brand-blue);"><?php echo htmlspecialchars($pet['name']); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($pet['breed']); ?> &bull; <?php echo htmlspecialchars($pet['species']); ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="row g-2 justify-content-md-end">
                <div class="col-auto">
                    <div style="background: #f8fafc; border-radius: 10px; padding: 12px 16px; text-align: center;">
                        <small class="text-muted d-block mb-1"><strong>Age</strong></small>
                        <p class="fw-bold mb-0"><?php echo (int)$pet['age']; ?> years</p>
                    </div>
                </div>
                <div class="col-auto">
                    <div style="background: #f8fafc; border-radius: 10px; padding: 12px 16px; text-align: center;">
                        <small class="text-muted d-block mb-1"><strong>Weight</strong></small>
                        <p class="fw-bold mb-0"><?php echo (float)$pet['weight']; ?> kg</p>
                    </div>
                </div>
                <div class="col-auto">
                    <div style="background: #f8fafc; border-radius: 10px; padding: 12px 16px; text-align: center;">
                        <small class="text-muted d-block mb-1"><strong>Owner</strong></small>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($pet['first_name']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="glass-card shadow-sm mb-4">
    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
        <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px; color: var(--brand-blue);">
            <i class="fas fa-plus-circle fs-4"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-0">Add Medical Record</h5>
            <p class="text-muted small mb-0">Document diagnosis, observations, and treatment notes</p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="add_record" value="1">

        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">VISIT DATE</label>
            <input type="date" name="visit_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">DIAGNOSIS</label>
            <input type="text" name="diagnosis" class="form-control" placeholder="e.g. Mild Infection, Healthy Checkup" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">NOTES & OBSERVATIONS</label>
            <textarea name="notes" class="form-control" rows="4" placeholder="Document any findings, symptoms, tests performed, recommendations..."></textarea>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary fw-bold px-4" style="background: var(--brand-blue); border: none;">
                <i class="fas fa-save me-2"></i>Save Record
            </button>
        </div>
    </form>
</div>

<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
    <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px; color: var(--brand-blue);">
        <i class="fas fa-history fs-4"></i>
    </div>
    <div>
        <h5 class="fw-bold mb-0">Visit History</h5>
        <p class="text-muted small mb-0">Previous medical records and diagnoses</p>
    </div>
</div>

<div class="glass-card shadow-sm p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE</th>
                    <th class="border-0 py-3 small fw-bold text-muted">DIAGNOSIS</th>
                    <th class="pe-4 border-0 py-3 small fw-bold text-muted">NOTES</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $record_count = 0;
                while ($record = mysqli_fetch_assoc($records)): 
                    $record_count++;
                    $visit_date = date('M d, Y', strtotime($record['visit_date']));
                ?>
                <tr style="vertical-align: top;">
                    <td class="ps-4 py-3 fw-bold"><?php echo $visit_date; ?></td>
                    <td class="py-3"><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                    <td class="pe-4 py-3 text-muted" style="max-width: 300px; word-wrap: break-word;">
                        <?php echo htmlspecialchars($record['notes'] ?? '—'); ?>
                    </td>
                </tr>
                <?php 
                endwhile;

                if ($record_count === 0):
                ?>
                <tr>
                    <td colspan="3" class="text-center p-5 text-muted">No medical records found for this pet.</td>
                </tr>
                <?php 
                endif;
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
mysqli_stmt_close($records_stmt);
include 'vet_layout_footer.php'; 
?>