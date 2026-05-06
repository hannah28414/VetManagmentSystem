<?php
include 'db.php';
include 'layout_header.php';

$user_id = $_SESSION['user_id'];
$message = "";

// 1. Fetch Current User Data
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user_data = mysqli_fetch_assoc($user_q);

// 2. Handle Profile Update
if (isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $update_q = "UPDATE users SET first_name='$first_name', last_name='$last_name', phone='$phone' WHERE user_id=$user_id";
    
    if (mysqli_query($conn, $update_q)) {
        $_SESSION['first_name'] = $first_name; // Update session name instantly
        $message = "<div class='alert alert-success border-0 shadow-sm'>Profile updated successfully!</div>";
        // Refresh data
        header("Refresh:1");
    }
}

// 3. Handle Password Change
if (isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];

    // For a student project, we check the old password. 
    // Pro Tip: Use password_verify() if you used password_hash() earlier.
    if ($old_pass == $user_data['password']) {
        $update_pass_q = "UPDATE users SET password='$new_pass' WHERE user_id=$user_id";
        mysqli_query($conn, $update_pass_q);
        $message = "<div class='alert alert-success border-0 shadow-sm'>Password changed successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger border-0 shadow-sm'>Old password is incorrect.</div>";
    }
}
?>

<style>
    .settings-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; }
    .settings-nav { background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 2rem 1rem; }
    .settings-link { 
        display: block; padding: 0.75rem 1rem; color: #64748b; 
        text-decoration: none; font-weight: 600; border-radius: 10px; margin-bottom: 0.5rem;
        transition: 0.2s;
    }
    .settings-link:hover { background: #f1f5f9; color: var(--brand-blue); }
    .settings-link.active { background: white; color: var(--brand-blue); border: 1px solid #e2e8f0; }
    
    .form-label { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; }
    .form-control { border-radius: 10px; padding: 0.75rem; border: 1.5px solid #e2e8f0; }
    .form-control:focus { border-color: var(--brand-blue); box-shadow: none; }
</style>

<div class="mb-5">
    <h2 class="fw-bold mb-1">Account Settings</h2>
    <p class="text-muted">Manage your profile information and security preferences.</p>
</div>

<?= $message ?>

<div class="settings-card shadow-sm">
    <div class="row g-0">
        <div class="col-md-3 settings-nav d-none d-md-block">
            <a href="#profile" class="settings-link active"><i class="far fa-user me-2"></i> Profile Info</a>
            <a href="#security" class="settings-link"><i class="fas fa-shield-alt me-2"></i> Security</a>
        </div>

        <div class="col-md-9 p-4 p-md-5">
            <section id="profile" class="mb-5">
                <h5 class="fw-bold mb-4">Personal Information</h5>
                <form method="POST">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= $user_data['first_name'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= $user_data['last_name'] ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= $user_data['phone'] ?>" placeholder="e.g. +1 234 567 890">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary px-4 fw-bold" style="background: var(--brand-blue); border: none; border-radius: 10px;">
                        Save Changes
                    </button>
                </form>
            </section>

            <hr class="my-5 opacity-25">

            <section id="security">
                <h5 class="fw-bold mb-4">Change Password</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-outline-primary px-4 fw-bold" style="border-radius: 10px;">
                        Update Password
                    </button>
                </form>
            </section>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>