<?php include 'header.php'; ?>

<?php
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'required') {
        $error_message = 'Please enter both email and password.';
    } elseif ($_GET['error'] === 'invalid') {
        $error_message = 'Invalid email or password.';
    }
}
?>

<div class="container">
    <div class="login-card mx-auto">
        <div class="row g-0">
            
            <div class="col-md-6 login-banner d-none d-md-flex flex-column justify-content-end p-5 text-white">
                <div class="mb-3">
                    <i class="fas fa-paw fa-3x" style="color: white;"></i>
                </div>
                <h1 class="fw-bold display-5">PawHealth Systems</h1>
                <p class="lead opacity-75">Streamlined clinical precision and empathetic care for veterinary professionals and pet owners.</p>
            </div>

            <div class="col-md-6 p-5 bg-white d-flex flex-column justify-content-center">
                <h2 class="fw-bold text-dark mb-1">Welcome back</h2>
                <p class="text-secondary mb-4">Please enter your details to sign in.</p>

                <?php if ($error_message !== ''): ?>
                    <p class="small text-danger mb-3"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <form action="loginprocess.php" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="far fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label small" for="rememberMe">Remember me</label>
                        </div>
                        <a href="#" class="text-decoration-none small fw-bold" style="color: var(--brand-blue);">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-signin text-white w-100 shadow-sm">Sign In</button>
                </form>

                <p class="text-center mt-4 small text-secondary">
                    Don't have an account? <a href="signUp.php" class="fw-bold text-decoration-none" style="color: var(--brand-blue);">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>