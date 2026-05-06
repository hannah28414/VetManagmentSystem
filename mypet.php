<?php
// 1. DATABASE & SESSION FIRST
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_id = $_SESSION['user_id'];

// 2. LOGIC SECTION (Must happen before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pet_id'])) {
    $delete_pet_id = (int) $_POST['delete_pet_id'];

    $delete_stmt = mysqli_prepare($conn, "DELETE FROM pets WHERE pet_id = ? AND customer_id = ?");
    mysqli_stmt_bind_param($delete_stmt, 'ii', $delete_pet_id, $user_id);
    mysqli_stmt_execute($delete_stmt);

    if (mysqli_stmt_affected_rows($delete_stmt) > 0) {
        mysqli_stmt_close($delete_stmt);
        // This redirect will now work perfectly!
        header('Location: mypet.php?status=deleted');
        exit();
    }

    mysqli_stmt_close($delete_stmt);
    header('Location: mypet.php?status=delete_failed');
    exit();
}

// 3. FETCH DATA
$pets_query = mysqli_query($conn, "SELECT * FROM pets WHERE customer_id = $user_id");

// 4. NOW START THE VISUALS
include 'layout_header.php'; 
?>

<style>
    .pet-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 16px;
        padding: 1.5rem; transition: all 0.3s ease; position: relative;
        height: 100%; border-left: 4px solid #003B5C;
    }
    .pet-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.05); }
    
    .species-badge {
        position: absolute; top: 1.5rem; right: 1.5rem;
        background: #EBF3F8; color: #003B5C;
        font-size: 0.65rem; font-weight: 800; padding: 4px 12px; border-radius: 20px;
    }
    .pet-img-circle {
        width: 65px; height: 65px; border-radius: 50%;
        border: 3px solid #EBF3F8; object-fit: cover; margin-bottom: 1rem;
    }
    .view-details-link {
        font-size: 0.8rem; font-weight: 700; color: #003B5C;
        text-decoration: none; display: flex; align-items: center;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">My Pets</h2>
        <p class="text-muted">Manage health records and profiles for your companions.</p>
    </div>
    <a href="addpet.php" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" style="background: var(--brand-blue); border-color: var(--brand-blue); color: #2a6476;">
    <a href="addpet.php" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" style="background: #003B5C;">
        <i class="fas fa-plus me-2"></i> Add New Pet
    </a>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
    <div class="alert alert-success border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> Pet record deleted successfully.
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'delete_failed'): ?>
    <div class="alert alert-danger border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> Unable to delete this pet record.
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php if(mysqli_num_rows($pets_query) > 0): ?>
        <?php while($pet = mysqli_fetch_assoc($pets_query)): ?>
            <div class="col-md-4">
                <div class="pet-card shadow-sm">
                    <span class="species-badge text-uppercase"><?php echo htmlspecialchars($pet['species']); ?></span>
                    <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=100&q=80" class="pet-img-circle">
                    
                    <h4 class="fw-bold mb-1" style="color: #1e293b;"><?php echo htmlspecialchars($pet['name']); ?></h4>
                    <p class="text-muted mb-0 small"><?php echo htmlspecialchars($pet['breed']); ?></p>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($pet['age']); ?> Years Old</p>
                    
                    <hr class="opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="petdetails.php?id=<?php echo (int) $pet['pet_id']; ?>" class="view-details-link mb-0">
                            View Details <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        
                        <form method="POST" class="mb-0" onsubmit="return confirm('Are you sure you want to delete this pet record? This cannot be undone.');">
                            <input type="hidden" name="delete_pet_id" value="<?php echo (int) $pet['pet_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3">
                                <i class="fas fa-trash-alt me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center p-5">
            <div class="text-muted mb-3"><i class="fas fa-paw fa-3x"></i></div>
            <h5 class="text-muted">No pets registered yet.</h5>
            <a href="addpet.php" class="btn btn-link fw-bold">Register your first patient</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>