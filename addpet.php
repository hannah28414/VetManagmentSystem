<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Collect and sanitize data
    $name    = mysqli_real_escape_string($conn, $_POST['n']);
    $species = mysqli_real_escape_string($conn, $_POST['s']);
    $user_id = $_SESSION['user_id'];
    
    // Default values if fields are missing in your table yet
    $breed   = isset($_POST['b']) ? mysqli_real_escape_string($conn, $_POST['b']) : 'Unknown';
    $age     = isset($_POST['a']) ? (int) $_POST['a'] : 0;
    $weight  = isset($_POST['w']) ? mysqli_real_escape_string($conn, $_POST['w']) : 0;

    // 2. The Insert Query
    // IMPORTANT: Ensure your table "pets" has these columns: name, species, breed, age, weight, customer_id
    $query = "INSERT INTO pets (name, species, breed, age, weight, customer_id) 
              VALUES ('$name', '$species', '$breed', '$age', '$weight', '$user_id')";
    
    if (mysqli_query($conn, $query)) {
        // Redirect to gallery on success
        header("Location: mypet.php");
        exit();
    } else {
        // Show error if it fails again
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

include 'layout_header.php';
?>

<div class="glass-card mx-auto" style="max-width: 600px;">
    <div class="mb-4 text-center">
        <h2 class="fw-bold" style="color: var(--brand-blue);">Register Patient</h2>
        <p class="text-muted">Initialize the medical record for your pet.</p>
    </div>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">PET NAME</label>
            <input type="text" name="n" class="form-control py-3" placeholder="e.g. Bella" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">SPECIES</label>
                <select name="s" class="form-select py-3" required>
                    <option value="Canine">Canine (Dog)</option>
                    <option value="Feline">Feline (Cat)</option>
                    <option value="Bird">Bird</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">BREED</label>
                <input type="text" name="b" class="form-control py-3" placeholder="e.g. Husky">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-muted">AGE (YEARS)</label>
            <select name="a" class="form-select py-3" required>
                <option value="" disabled selected>Select years</option>
                <?php for ($year = 0; $year <= 20; $year++): ?>
                    <option value="<?php echo $year; ?>"><?php echo $year; ?> Year<?php echo $year === 1 ? '' : 's'; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-muted">WEIGHT (KG)</label>
            <input type="number" step="0.1" name="w" class="form-control py-3" placeholder="0.0">
        </div>

        <div class="d-flex justify-content-end gap-2 border-top pt-4">
            <a href="mypet.php" class="btn btn-light px-4 fw-bold">Cancel</a>
            <button type="submit" class="btn btn-primary px-5 fw-bold" style="background: var(--brand-blue); border: none;">
                SAVE PATIENT RECORD
            </button>
        </div>
    </form>
</div>

<?php include 'layout_footer.php'; ?>