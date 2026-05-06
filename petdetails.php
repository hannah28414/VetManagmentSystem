<?php
include 'db.php';
include 'pharmacy_setup.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ensure_pharmacy_schema($conn);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$pet_id = (int) ($_GET['id'] ?? ($_GET['pet_id'] ?? 0));

if ($pet_id <= 0) {
    header('Location: mypet.php');
    exit();
}

// Fetch pet info and verify ownership for security.
$pet_stmt = mysqli_prepare($conn, "SELECT * FROM pets WHERE pet_id = ? AND customer_id = ? LIMIT 1");
mysqli_stmt_bind_param($pet_stmt, 'ii', $pet_id, $user_id);
mysqli_stmt_execute($pet_stmt);
$pet_result = mysqli_stmt_get_result($pet_stmt);
$pet = mysqli_fetch_assoc($pet_result);
mysqli_stmt_close($pet_stmt);

if (!$pet) {
    echo "Access Denied or Pet Not Found.";
    exit();
}

$profile_message = '';
$profile_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pet_profile'])) {
    $updated_name = trim($_POST['pet_name'] ?? '');
    $updated_species = trim($_POST['pet_species'] ?? '');
    $updated_breed = trim($_POST['pet_breed'] ?? '');
    $updated_age = (int)($_POST['pet_age'] ?? -1);
    $updated_weight = trim($_POST['pet_weight'] ?? '');

    if ($updated_name === '') {
        $profile_error = 'Pet name is required.';
    } elseif ($updated_species === '') {
        $profile_error = 'Species is required.';
    } elseif ($updated_age < 0 || $updated_age > 30) {
        $profile_error = 'Age must be between 0 and 30.';
    } elseif ($updated_weight === '' || !is_numeric($updated_weight)) {
        $profile_error = 'Weight must be a number.';
    } else {
        $updated_weight_value = (float)$updated_weight;

        $update_stmt = mysqli_prepare(
            $conn,
            "UPDATE pets
             SET name = ?,
                 species = ?,
                 breed = ?,
                 age = ?,
                 weight = ?
             WHERE pet_id = ? AND customer_id = ?"
        );
        mysqli_stmt_bind_param(
            $update_stmt,
            'sssidii',
            $updated_name,
            $updated_species,
            $updated_breed,
            $updated_age,
            $updated_weight_value,
            $pet_id,
            $user_id
        );
        mysqli_stmt_execute($update_stmt);

        if (mysqli_stmt_affected_rows($update_stmt) >= 0) {
            mysqli_stmt_close($update_stmt);
            header('Location: petdetails.php?id=' . $pet_id . '&status=updated');
            exit();
        }

        $profile_error = 'Unable to update pet profile.';
        mysqli_stmt_close($update_stmt);
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $profile_message = 'Pet profile updated successfully.';
} elseif (isset($_GET['status']) && $_GET['status'] === 'rx_added') {
    $profile_message = 'Prescription saved successfully.';
}

// Fetch prescription and medicine history for this pet.
$rx_stmt = mysqli_prepare(
    $conn,
    "SELECT
          mr.visit_date AS visit_date,
        mr.diagnosis,
        p.instructions,
        pm.quantity,
        m.medicine_id,
        m.name AS medicine_name,
        m.dosage_mg
     FROM medical_records mr
     JOIN prescriptions p ON mr.record_id = p.record_id
     JOIN prescription_medicines pm ON p.prescription_id = pm.prescription_id
     JOIN medicines m ON pm.medicine_id = m.medicine_id
     WHERE mr.pet_id = ?
     
      UNION ALL
     
      SELECT
          o.created_at AS visit_date,
          'Pharmacy Purchase' AS diagnosis,
          'Purchased from pharmacy' AS instructions,
          oi.quantity,
          m.medicine_id,
          m.name AS medicine_name,
          m.dosage_mg
      FROM pharmacy_orders o
      JOIN pharmacy_order_items oi ON o.order_id = oi.order_id
      JOIN medicines m ON oi.medicine_id = m.medicine_id
      WHERE o.pet_id = ? AND o.customer_id = ?

      ORDER BY visit_date DESC"
);
mysqli_stmt_bind_param($rx_stmt, 'iii', $pet_id, $pet_id, $user_id);
mysqli_stmt_execute($rx_stmt);
$rx_result = mysqli_stmt_get_result($rx_stmt);

include 'layout_header.php';

// Fetch appointment history for this specific pet.
$history_stmt = mysqli_prepare(
    $conn,
    "SELECT a.date, COALESCE(s.name, 'General Consult') AS service_name, a.status
     FROM appointments a
     LEFT JOIN services s ON a.service_id = s.service_id
     WHERE a.pet_id = ?
     ORDER BY a.date DESC"
);
mysqli_stmt_bind_param($history_stmt, 'i', $pet_id);
mysqli_stmt_execute($history_stmt);
$history_q = mysqli_stmt_get_result($history_stmt);
?>

<style>
    .profile-header { background: white; border-radius: 20px; padding: 2.5rem; border: 1px solid #e2e8f0; margin-bottom: 2rem; }
    .vital-box { background: #f8fafc; border-radius: 12px; padding: 1rem; text-align: center; border: 1px solid #e2e8f0; }
    .vital-value { display: block; font-size: 1.25rem; font-weight: 800; color: var(--brand-blue); }
    .vital-label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
    
    .history-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; }
    .profile-edit-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 1.5rem; margin-bottom: 2rem; }
</style>

<div class="mb-4">
    <a href="mypet.php" class="text-decoration-none text-muted small fw-bold">
        <i class="fas fa-chevron-left me-2"></i> BACK TO MY PETS
    </a>
</div>

<div class="profile-header shadow-sm">
    <div class="row align-items-center">
        <div class="col-md-2 text-center">
            <img src="https://ui-avatars.com/api/?name=<?= $pet['name'] ?>&size=120&background=random" class="rounded-circle shadow-sm border border-4 border-white">
        </div>
        <div class="col-md-5">
            <h1 class="fw-bold mb-1" style="color: var(--brand-blue);"><?= $pet['name'] ?></h1>
            <p class="text-muted mb-3 fs-5"><?= $pet['breed'] ?> • <?= $pet['species'] ?></p>
            <span class="badge bg-success rounded-pill px-3 py-2">Clinical Status: Stable</span>
        </div>
        <div class="col-md-5">
            <div class="row g-3">
                <div class="col-4">
                    <div class="vital-box">
                        <span class="vital-value"><?= $pet['age'] ?></span>
                        <span class="vital-label">Years Old</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="vital-box">
                        <span class="vital-value"><?= $pet['weight'] ?? '0' ?></span>
                        <span class="vital-label">Weight (kg)</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="vital-box">
                        <span class="vital-value">None</span>
                        <span class="vital-label">Allergies</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($profile_message !== ''): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($profile_message); ?></div>
<?php endif; ?>

<?php if ($profile_error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($profile_error); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-outline-primary fw-bold px-4" id="toggleEditProfileBtn" style="border-color: var(--brand-blue); color: var(--brand-blue);">
        Edit Profile
    </button>
</div>

<div class="profile-edit-card shadow-sm d-none" id="editProfileCard">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Edit Profile</h5>
        <small class="text-muted">Update all pet profile information</small>
    </div>

    <form method="POST">
        <input type="hidden" name="update_pet_profile" value="1">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">PET NAME</label>
                <input type="text" name="pet_name" class="form-control" value="<?= htmlspecialchars($pet['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">SPECIES</label>
                <select name="pet_species" class="form-select" required>
                    <option value="Canine" <?= ($pet['species'] === 'Canine') ? 'selected' : '' ?>>Canine (Dog)</option>
                    <option value="Feline" <?= ($pet['species'] === 'Feline') ? 'selected' : '' ?>>Feline (Cat)</option>
                    <option value="Bird" <?= ($pet['species'] === 'Bird') ? 'selected' : '' ?>>Bird</option>
                    <option value="Other" <?= ($pet['species'] === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">BREED</label>
                <input type="text" name="pet_breed" class="form-control" value="<?= htmlspecialchars($pet['breed'] ?? '') ?>" placeholder="e.g. Husky">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">AGE (YEARS)</label>
                <select name="pet_age" class="form-select" required>
                    <?php for ($age_opt = 0; $age_opt <= 30; $age_opt++): ?>
                        <option value="<?= $age_opt ?>" <?= ((int)$pet['age'] === $age_opt) ? 'selected' : '' ?>>
                            <?= $age_opt ?> Year<?= $age_opt === 1 ? '' : 's' ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">WEIGHT (KG)</label>
                <input type="number" step="0.1" min="0" name="pet_weight" class="form-control" value="<?= htmlspecialchars($pet['weight'] ?? '') ?>" placeholder="0.0" required>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-light fw-bold" id="cancelEditProfileBtn">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4" style="background: var(--brand-blue); border: none;">Save Changes</button>
        </div>
    </form>
</div>

<h5 class="fw-bold mb-3">Prescriptions & Medicines</h5>
<div class="history-card shadow-sm mb-4">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE</th>
                <th class="border-0 py-3 small fw-bold text-muted">DIAGNOSIS</th>
                <th class="border-0 py-3 small fw-bold text-muted">MEDICINE</th>
                <th class="border-0 py-3 small fw-bold text-muted">DOSE</th>
                <th class="border-0 py-3 small fw-bold text-muted">QTY</th>
                <th class="pe-4 border-0 py-3 small fw-bold text-muted">INSTRUCTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rx_result && mysqli_num_rows($rx_result) > 0): ?>
                <?php while ($rx = mysqli_fetch_assoc($rx_result)): ?>
                <tr style="vertical-align: middle;">
                    <td class="ps-4 py-3 fw-bold"><?= date("M d, Y", strtotime($rx['visit_date'])) ?></td>
                    <td class="py-3"><?= htmlspecialchars($rx['diagnosis']) ?></td>
                    <td class="py-3"><?= htmlspecialchars($rx['medicine_name']) ?></td>
                    <td class="py-3"><?= htmlspecialchars($rx['dosage_mg']) ?> mg</td>
                    <td class="py-3"><?= (int)$rx['quantity'] ?></td>
                    <td class="py-3 text-muted"><?= htmlspecialchars($rx['instructions']) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center p-5 text-muted">No prescriptions recorded for this pet yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h5 class="fw-bold mb-3">Clinical History</h5>
<div class="history-card shadow-sm">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE</th>
                <th class="border-0 py-3 small fw-bold text-muted">SERVICE</th>
                <th class="border-0 py-3 small fw-bold text-muted">VETERINARIAN</th>
                <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($history_q) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($history_q)): ?>
                <tr style="vertical-align: middle;">
                    <td class="ps-4 py-3 fw-bold"><?= date("M d, Y", strtotime($row['date'])) ?></td>
                    <td class="py-3"><?= $row['service_name'] ?></td>
                    <td class="py-3 text-muted">Dr. Standard Staff</td>
                    <td class="pe-4 py-3 text-end">
                        <span class="badge rounded-pill bg-light text-dark border"><?= $row['status'] ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center p-5 text-muted">No clinical history found for this pet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 text-end">
    <a href="book_appointment.php?pet_id=<?= $pet_id ?>" class="btn btn-primary px-4 fw-bold shadow-sm" style="background: var(--brand-blue); border: none;">
        <i class="far fa-calendar-plus me-2"></i> Book New Session
    </a>
</div>

<script>
    const toggleEditProfileBtn = document.getElementById('toggleEditProfileBtn');
    const cancelEditProfileBtn = document.getElementById('cancelEditProfileBtn');
    const editProfileCard = document.getElementById('editProfileCard');

    if (toggleEditProfileBtn && editProfileCard) {
        toggleEditProfileBtn.addEventListener('click', () => {
            editProfileCard.classList.toggle('d-none');
            if (!editProfileCard.classList.contains('d-none')) {
                editProfileCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    if (cancelEditProfileBtn && editProfileCard) {
        cancelEditProfileBtn.addEventListener('click', () => {
            editProfileCard.classList.add('d-none');
        });
    }
</script>

<?php mysqli_stmt_close($history_stmt); ?>
<?php mysqli_stmt_close($rx_stmt); ?>
<?php include 'layout_footer.php'; ?>