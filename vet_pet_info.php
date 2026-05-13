<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vet') {
    header("Location: login.php");
    exit();
}

include 'db.php';

$vet_id = (int)($_SESSION['user_id'] ?? 0);

// Fetch all pets this vet has treated
$pets_stmt = mysqli_prepare(
    $conn,
    "SELECT DISTINCT p.pet_id, p.name, p.species, p.breed, p.age, p.weight, u.first_name, u.last_name
     FROM pets p
     JOIN users u ON p.customer_id = u.user_id
     JOIN appointments a ON p.pet_id = a.pet_id
     WHERE a.vet_id = ?
     ORDER BY p.name ASC"
);
mysqli_stmt_bind_param($pets_stmt, 'i', $vet_id);
mysqli_stmt_execute($pets_stmt);
$pets = mysqli_stmt_get_result($pets_stmt);

include 'vet_layout_header.php';
?>

<div class="mb-4">
    <h2 class="fw-bold mb-1" style="color: var(--brand-blue);">Pet Information</h2>
    <p class="text-muted">View detailed information for all pets you have treated.</p>
</div>

<div class="row g-4">
    <?php 
    $count = 0;
    while ($pet = mysqli_fetch_assoc($pets)): 
        $count++;
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="glass-card shadow-sm" style="border-left: 4px solid var(--brand-blue);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: var(--brand-blue);"><?php echo htmlspecialchars($pet['name']); ?></h5>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($pet['breed'] ?? 'Unknown Breed'); ?></p>
                    </div>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($pet['species']); ?></span>
                </div>

                <hr class="opacity-25 my-2">

                <div class="mb-3">
                    <small class="text-muted d-block mb-1"><strong>Owner:</strong></small>
                    <p class="mb-0"><?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?></p>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div style="background: #f8fafc; border-radius: 10px; padding: 10px; text-align: center;">
                            <small class="text-muted d-block"><strong>Age</strong></small>
                            <p class="fw-bold mb-0"><?php echo (int)$pet['age']; ?> yrs</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background: #f8fafc; border-radius: 10px; padding: 10px; text-align: center;">
                            <small class="text-muted d-block"><strong>Weight</strong></small>
                            <p class="fw-bold mb-0"><?php echo (float)$pet['weight']; ?> kg</p>
                        </div>
                    </div>
                </div>

                <a href="vet_pet_records.php?pet_id=<?php echo (int)$pet['pet_id']; ?>" class="btn btn-primary w-100 fw-bold" style="background: var(--brand-blue); border: none;">
                    <i class="fas fa-file-medical me-2"></i>View Medical Records
                </a>
            </div>
        </div>
    <?php 
    endwhile;
    
    if ($count === 0):
    ?>
        <div class="col-12">
            <div class="glass-card text-center p-5">
                <i class="fas fa-paw fa-3x text-muted mb-3"></i>
                <p class="text-muted">No pets in your patient list yet.</p>
            </div>
        </div>
    <?php 
    endif;
    ?>
</div>

<?php include 'vet_layout_footer.php'; ?>
