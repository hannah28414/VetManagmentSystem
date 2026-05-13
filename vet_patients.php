<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$vet_id = $_SESSION['user_id'];

// Fetch all patients treated by this vet
$patients_query = "
    SELECT DISTINCT p.pet_id, p.name as pet_name, p.species, p.breed,
           u.first_name, u.last_name, u.phone,
           COUNT(mr.record_id) as visit_count,
           MAX(mr.visit_date) as last_visit
    FROM pets p
    JOIN users u ON p.customer_id = u.user_id
    LEFT JOIN medical_records mr ON p.pet_id = mr.pet_id AND mr.vet_id = ?
    GROUP BY p.pet_id, p.name, p.species, p.breed, u.first_name, u.last_name, u.phone
    ORDER BY last_visit DESC
";

$patients_stmt = mysqli_prepare($conn, $patients_query);
mysqli_stmt_bind_param($patients_stmt, 'i', $vet_id);
mysqli_stmt_execute($patients_stmt);
$patients_result = mysqli_stmt_get_result($patients_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-5">
    <a href="vet_dashboard.php" class="text-decoration-none text-muted small fw-bold">
        <i class="fas fa-chevron-left me-2"></i> BACK TO DASHBOARD
    </a>
    <h2 class="fw-bold mt-3">My Patients</h2>
    <p class="text-muted">All patients you have treated</p>
</div>

<div class="glass-card shadow-sm p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">PATIENT</th>
                    <th class="border-0 py-3 small fw-bold text-muted">OWNER</th>
                    <th class="border-0 py-3 small fw-bold text-muted">VISITS</th>
                    <th class="border-0 py-3 small fw-bold text-muted">LAST VISIT</th>
                    <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($patients_result) > 0): ?>
                    <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                    <tr style="vertical-align: middle;">
                        <td class="ps-4 py-3">
                            <strong><?php echo htmlspecialchars($patient['pet_name']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($patient['species'] . ' • ' . $patient['breed']); ?></small>
                        </td>
                        <td class="py-3">
                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></small>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-dark"><?php echo (int)$patient['visit_count']; ?></span>
                        </td>
                        <td class="py-3">
                            <?php echo $patient['last_visit'] ? date('M d, Y', strtotime($patient['last_visit'])) : 'No visits'; ?>
                        </td>
                        <td class="pe-4 py-3 text-end">
                            <a href="vet_pet_records.php?pet_id=<?php echo (int)$patient['pet_id']; ?>" class="btn btn-sm btn-primary" style="background: var(--brand-blue); border: none;">
                                <i class="fas fa-history me-1"></i> History
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center p-5 text-muted">
                            No patients found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
mysqli_stmt_close($patients_stmt);
include 'vet_layout_footer.php'; 
?>