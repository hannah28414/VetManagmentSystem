<?php
include 'db.php';
include 'layout_header.php';

// Fetch all services from the database
$query = "SELECT * FROM services ORDER BY name ASC";
$services = mysqli_query($conn, $query);
?>

<style>
    .service-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 20px;
        padding: 2rem; transition: all 0.3s ease; position: relative;
        overflow: hidden; height: 100%; display: flex; flex-direction: column;
    }
    .service-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,59,92,0.08); border-color: #0d6efd; }
    
    .service-icon {
        width: 50px; height: 50px; background: #EBF3F8; color: #003B5C;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 1.5rem; transition: 0.3s;
    }
    .service-card:hover .service-icon { background: #003B5C; color: white; }

    .price-tag {
        font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem;
    }
    .price-tag span { font-size: 0.8rem; color: #64748b; font-weight: 500; }

    .btn-book {
        margin-top: auto; background: #F1F5F9; color: #003B5C;
        border: none; padding: 10px; border-radius: 10px; font-weight: 700;
        transition: 0.2s; width: 100%; text-decoration: none; text-align: center;
    }
    .service-card:hover .btn-book { background: #003B5C; color: white; }
</style>

<div class="mb-5">
    <h2 class="fw-bold mb-1">Clinical Services</h2>
    <p class="text-muted">Explore our professional care options and transparent pricing.</p>
</div>

<div class="row g-4">
    <?php if(mysqli_num_rows($services) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($services)): ?>
            <?php 
                // Simple logic to pick an icon based on service name
                $icon = "fa-notes-medical";
                $name = strtolower($row['name']);
                if(strpos($name, 'vaccin') !== false) $icon = "fa-syringe";
                if(strpos($name, 'check') !== false) $icon = "fa-heartbeat";
                if(strpos($name, 'surgery') !== false) $icon = "fa-microscope";
                if(strpos($name, 'dental') !== false) $icon = "fa-tooth";
            ?>
            <div class="col-md-4 col-lg-3">
                <div class="service-card shadow-sm">
                    <div class="service-icon">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    
                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($row['name']) ?></h5>
                    <p class="text-muted small mb-3">Professional veterinary care provided by our board-certified experts.</p>
                    
                    <div class="price-tag">
                        $<?= number_format($row['cost'], 2) ?> <span>/ visit</span>
                    </div>

                    <a href="book_appointment.php?service_id=<?= $row['service_id'] ?>" class="btn-book">
                        Book Now
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center p-5 glass-card">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">No services found in the database.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>