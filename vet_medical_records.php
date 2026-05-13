<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$vet_id = $_SESSION['user_id'];

// Fetch all medical records created by this vet
$records_query = "
    SELECT mr.record_id, mr.visit_date, mr.diagnosis, mr.notes,
           p.pet_id, p.name as pet_name, p.species, p.breed,
           u.first_name, u.last_name
    FROM medical_records mr
    JOIN pets p ON mr.pet_id = p.pet_id
    JOIN users u ON p.customer_id = u.user_id
    WHERE mr.vet_id = ?
    ORDER BY mr.visit_date DESC
";

$records_stmt = mysqli_prepare($conn, $records_query);
mysqli_stmt_bind_param($records_stmt, 'i', $vet_id);
mysqli_stmt_execute($records_stmt);
$records_result = mysqli_stmt_get_result($records_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-5">
    <a href="vet_dashboard.php" class="text-decoration-none text-muted small fw-bold">
        <i class="fas fa-chevron-left me-2"></i> BACK TO DASHBOARD
    </a>
    <h2 class="fw-bold mt-3">Medical Records</h2>
    <p class="text-muted">All medical records created by you</p>
</div>

<div class="glass-card shadow-sm p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE</th>
                    <th class="border-0 py-3 small fw-bold text-muted">PET</th>
                    <th class="border-0 py-3 small fw-bold text-muted">OWNER</th>
                    <th class="border-0 py-3 small fw-bold text-muted">DIAGNOSIS</th>
                    <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($records_result) > 0): ?>
                    <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                    <tr style="vertical-align: middle;">
                        <td class="ps-4 py-3 fw-bold"><?php echo date('M d, Y', strtotime($record['visit_date'])); ?></td>
                        <td class="py-3">
                            <strong><?php echo htmlspecialchars($record['pet_name']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($record['species']); ?></small>
                        </td>
                        <td class="py-3"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                        <td class="py-3"><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                        <td class="pe-4 py-3 text-end">
                            <a href="vet_record_details.php?record_id=<?php echo (int)$record['record_id']; ?>" class="btn btn-sm btn-primary" style="background: var(--brand-blue); border: none;">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center p-5 text-muted">
                            No medical records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
mysqli_stmt_close($records_stmt);
include 'vet_layout_footer.php'; 
?>