<?php include 'header.php'; ?>

<div class="container py-5">
    <div class="login-card mx-auto shadow-lg" style="max-width: 1100px;">
        <div class="row g-0">
            
            <div class="col-md-6 register-banner d-none d-md-flex flex-column justify-content-end p-5 text-white">
                <div class="mb-3">
                    <i class="fas fa-paw fa-3x" style="color: white;"></i>
                </div>
                <h1 class="fw-bold display-5">Precision care.</h1>
                <h1 class="fw-bold display-5">Empathetic connection.</h1>
                <p class="lead opacity-75 mt-3">Join the modern ecosystem designed for veterinary professionals and devoted pet owners.</p>
            </div>

            <div class="col-md-6 p-5 bg-white d-flex flex-column justify-content-center">
                <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                <p class="text-secondary mb-4">Enter your details to register.</p>

             <form action="signupProcess.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="far fa-user text-muted"></i></span>
                            <input type="text" name="first_name" class="form-control bg-light border-start-0" placeholder="Jane Doe" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="far fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="jane@example.com" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Password</label>
                            <input type="password" name="password" class="form-control bg-light" placeholder="••••••••" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Confirm</label>
                            <input type="password" name="confirm_password" class="form-control bg-light" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-signin text-white w-100 shadow-sm">Register Account <i class="fas fa-arrow-right ms-2"></i></button>
                </form>

                <p class="text-center mt-4 small text-secondary">
                    Already have an account? <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--brand-blue);">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>