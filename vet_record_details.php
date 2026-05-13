<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$vet_id = $_SESSION['user_id'];
$record_id = (int)($_GET['record_id'] ?? 0);

if ($record_id <= 0) {
    header('Location: vet_medical_records.php');
    exit();
}

// Fetch medical record details
$record_stmt = mysqli_prepare($conn, "
    SELECT mr.record_id, mr.visit_date, mr.diagnosis, mr.notes,
           p.pet_id, p.name as pet_name, p.species, p.breed, p.age, p.weight,
           u.first_name, u.last_name, u.phone
    FROM medical_records mr
    JOIN pets p ON mr.pet_id = p.pet_id
    JOIN users u ON p.customer_id = u.user_id
    WHERE mr.record_id = ? AND mr.vet_id = ?
");
mysqli_stmt_bind_param($record_stmt, 'ii', $record_id, $vet_id);
mysqli_stmt_execute($record_stmt);
$record_result = mysqli_stmt_get_result($record_stmt);
$record = mysqli_fetch_assoc($record_result);
mysqli_stmt_close($record_stmt);

if (!$record) {
    header('Location: vet_medical_records.php');
    exit();
}

// Fetch prescriptions for this record
$rx_stmt = mysqli_prepare($conn, "
    SELECT p.prescription_id, p.instructions,
           pm.medicine_id, pm.quantity,
           m.name as medicine_name, m.dosage_mg
    FROM prescriptions p
    JOIN prescription_medicines pm ON p.prescription_id = pm.prescription_id
    JOIN medicines m ON pm.medicine_id = m.medicine_id
    WHERE p.record_id = ?
");
mysqli_stmt_bind_param($rx_stmt, 'i', $record_id);
mysqli_stmt_execute($rx_stmt);
$rx_result = mysqli_stmt_get_result($rx_stmt);
mysqli_stmt_close($rx_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-5">
    <a href="vet_medical_records.php" class="text-decoration-none text-muted small fw-bold">
        <i class="fas fa-chevron-left me-2"></i> BACK TO RECORDS
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="glass-card shadow-sm mb-4">
            <h4 class="fw-bold mb-4" style="color: var(--brand-blue);">Record Details</h4>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted small fw-bold">VISIT DATE</h6>
                    <p class="fw-bold"><?php echo date('F d, Y', strtotime($record['visit_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted small fw-bold">DIAGNOSIS</h6>
                    <p class="fw-bold"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-muted small fw-bold">CLINICAL NOTES</h6>
                <p><?php echo htmlspecialchars($record['notes'] ?? 'No notes recorded'); ?></p>
            </div>

            <hr class="opacity-25">

            <h5 class="fw-bold mt-4 mb-3">Prescriptions</h5>

            <?php if (mysqli_num_rows($rx_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="small fw-bold text-muted">MEDICINE</th>
                                <th class="small fw-bold text-muted">DOSAGE</th>
                                <th class="small fw-bold text-muted">QUANTITY</th>
                                <th class="small fw-bold text-muted">INSTRUCTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rx = mysqli_fetch_assoc($rx_result)): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($rx['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($rx['dosage_mg']); ?> mg</td>
                                <td><?php echo (int)$rx['quantity']; ?></td>
                                <td><small><?php echo htmlspecialchars($rx['instructions']); ?></small></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No prescriptions issued for this visit.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="glass-card shadow-sm">
            <h5 class="fw-bold mb-3" style="color: var(--brand-blue);">Patient Information</h5>

            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($record['pet_name']); ?></h6>
            <p class="text-muted mb-3">
                <?php echo htmlspecialchars($record['species']); ?> &bull; 
                <?php echo htmlspecialchars($record['breed']); ?>
            </p>

            <hr class="opacity-25">

            <div class="mb-3">
                <h6 class="text-muted small fw-bold">AGE</h6>
                <p class="fw-bold"><?php echo (int)$record['age']; ?> years</p>
            </div>

            <div class="mb-3">
                <h6 class="text-muted small fw-bold">WEIGHT</h6>
                <p class="fw-bold"><?php echo htmlspecialchars($record['weight']); ?> kg</p>
            </div>

            <hr class="opacity-25">

            <h6 class="fw-bold mb-2">Owner Information</h6>
            <p class="mb-1">
                <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
            </p>
            <p class="text-muted mb-0">
                <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($record['phone'] ?? 'N/A'); ?>
            </p>
        </div>
    </div>
</div>

<?php include 'vet_layout_footer.php'; ?>