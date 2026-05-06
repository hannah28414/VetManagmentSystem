<?php
include 'db.php';
include 'pharmacy_setup.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
ensure_pharmacy_schema($conn);

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: login.php');
    exit();
}

if (($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: petdetails.php?id=' . (int)($_GET['pet_id'] ?? ($_POST['pet_id'] ?? 0)));
    exit();
}

$pet_id = (int)($_GET['pet_id'] ?? ($_POST['pet_id'] ?? 0));
if ($pet_id <= 0) {
    header('Location: mypet.php');
    exit();
}

// Fetch pet info for the header
$pet_stmt = mysqli_prepare($conn, "SELECT pet_id, name FROM pets WHERE pet_id = ? AND customer_id = ? LIMIT 1");
mysqli_stmt_bind_param($pet_stmt, 'ii', $pet_id, $user_id);
mysqli_stmt_execute($pet_stmt);
$pet_result = mysqli_stmt_get_result($pet_stmt);
$pet = mysqli_fetch_assoc($pet_result);
mysqli_stmt_close($pet_stmt);

if (!$pet) {
    header('Location: mypet.php');
    exit();
}

$form_error = '';

// Fetch medicines for the dropdown menu
$medicines_query = mysqli_query($conn, "SELECT medicine_id, name, dosage_mg FROM medicines ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $medicine_id = (int)($_POST['medicine_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $instructions = trim($_POST['instructions'] ?? '');
    $visit_date = date('Y-m-d');
    
    if ($diagnosis === '' || $medicine_id <= 0 || $quantity <= 0 || $instructions === '') {
        $form_error = 'Please fill all prescription fields correctly.';
    } else {
        // Default fallback vet id; replace with actual authenticated vet id when available.
        $vet_id = 8;

        mysqli_begin_transaction($conn);
        $save_ok = true;

        // STEP 1: Create the Medical Record
        $record_stmt = mysqli_prepare(
            $conn,
            "INSERT INTO medical_records (visit_date, diagnosis, notes, pet_id, vet_id)
             VALUES (?, ?, 'Prescription Issued via Portal', ?, ?)"
        );
        if ($record_stmt) {
            mysqli_stmt_bind_param($record_stmt, 'ssii', $visit_date, $diagnosis, $pet_id, $vet_id);
            $save_ok = mysqli_stmt_execute($record_stmt);
            $record_id = $save_ok ? mysqli_insert_id($conn) : 0;
            mysqli_stmt_close($record_stmt);
        } else {
            $save_ok = false;
            $record_id = 0;
        }

        // STEP 2: Create the Prescription linked to the Record
        if ($save_ok) {
            $rx_stmt = mysqli_prepare($conn, "INSERT INTO prescriptions (instructions, record_id) VALUES (?, ?)");
            if ($rx_stmt) {
                mysqli_stmt_bind_param($rx_stmt, 'si', $instructions, $record_id);
                $save_ok = mysqli_stmt_execute($rx_stmt);
                $prescription_id = $save_ok ? mysqli_insert_id($conn) : 0;
                mysqli_stmt_close($rx_stmt);
            } else {
                $save_ok = false;
                $prescription_id = 0;
            }
        }

        // STEP 3: Link the Medicine and Quantity to the Prescription
        if ($save_ok) {
            $link_stmt = mysqli_prepare(
                $conn,
                "INSERT INTO prescription_medicines (prescription_id, medicine_id, quantity) VALUES (?, ?, ?)"
            );
            if ($link_stmt) {
                mysqli_stmt_bind_param($link_stmt, 'iii', $prescription_id, $medicine_id, $quantity);
                $save_ok = mysqli_stmt_execute($link_stmt);
                mysqli_stmt_close($link_stmt);
            } else {
                $save_ok = false;
            }
        }

        // STEP 4: Deduct medicine stock.
        if ($save_ok) {
            $stock_stmt = mysqli_prepare($conn, "SELECT stock_qty FROM medicines WHERE medicine_id = ? LIMIT 1");
            if ($stock_stmt) {
                mysqli_stmt_bind_param($stock_stmt, 'i', $medicine_id);
                mysqli_stmt_execute($stock_stmt);
                $stock_result = mysqli_stmt_get_result($stock_stmt);
                $stock_row = mysqli_fetch_assoc($stock_result);
                mysqli_stmt_close($stock_stmt);

                if (!$stock_row) {
                    $save_ok = false;
                    $form_error = 'Selected medicine was not found.';
                } elseif ((int)$stock_row['stock_qty'] < $quantity) {
                    $save_ok = false;
                    $form_error = 'Not enough stock for this medicine.';
                } else {
                    $deduct_stmt = mysqli_prepare(
                        $conn,
                        "UPDATE medicines SET stock_qty = stock_qty - ? WHERE medicine_id = ?"
                    );
                    if ($deduct_stmt) {
                        mysqli_stmt_bind_param($deduct_stmt, 'ii', $quantity, $medicine_id);
                        $save_ok = mysqli_stmt_execute($deduct_stmt);
                        mysqli_stmt_close($deduct_stmt);
                    } else {
                        $save_ok = false;
                    }
                }
            } else {
                $save_ok = false;
            }
        }

        if ($save_ok) {
            mysqli_commit($conn);
            header('Location: petdetails.php?id=' . $pet_id . '&status=rx_added');
            exit();
        }

        mysqli_rollback($conn);
        if ($form_error === '') {
            $form_error = 'Could not save prescription. Please try again.';
        }
    }
}

include 'layout_header.php';
?>

<div class="glass-card mx-auto shadow-sm" style="max-width: 650px;">
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
        <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px; color: var(--brand-blue);">
            <i class="fas fa-file-prescription fs-4"></i>
        </div>
        <div>
            <h3 class="fw-bold mb-0">Issue Prescription</h3>
            <p class="text-muted small mb-0">Patient: <span class="fw-bold"><?= htmlspecialchars($pet['name']) ?></span></p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="pet_id" value="<?= (int)$pet_id ?>">
        <?php if ($form_error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($form_error) ?></div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="form-label fw-bold small text-muted">REASON FOR PRESCRIPTION (DIAGNOSIS)</label>
            <input type="text" name="diagnosis" class="form-control" placeholder="e.g. Mild Infection" required>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-md-8">
                <label class="form-label fw-bold small text-muted">SELECT MEDICATION</label>
                <select name="medicine_id" class="form-select" required>
                    <option value="" disabled selected>Choose from catalog...</option>
                    <?php while($med = mysqli_fetch_assoc($medicines_query)): ?>
                        <option value="<?= $med['medicine_id'] ?>">
                            <?= htmlspecialchars($med['name']) ?> (<?= $med['dosage_mg'] ?>mg)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted">QUANTITY</label>
                <input type="number" name="quantity" class="form-control" min="1" value="14" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-muted">ADMINISTRATION INSTRUCTIONS</label>
            <textarea name="instructions" class="form-control" rows="3" placeholder="e.g. Give 1 tablet with food twice daily." required></textarea>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="petdetails.php?id=<?= $pet_id ?>" class="text-muted fw-bold text-decoration-none small">CANCEL</a>
            <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3" style="background: var(--brand-blue); border: none;">
                SAVE TO RECORD <i class="fas fa-check ms-2"></i>
            </button>
        </div>
    </form>
</div>

<?php include 'layout_footer.php'; ?>