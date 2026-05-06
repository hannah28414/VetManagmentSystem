<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$user_id = $_SESSION['user_id'];

$selected_clinic_id = (int)($_GET['clinic_id'] ?? 0);

$top_pet_details_url = 'mypet.php';
$top_pet_stmt = mysqli_prepare($conn, "SELECT pet_id FROM pets WHERE customer_id = ? ORDER BY pet_id ASC LIMIT 1");
if ($top_pet_stmt) {
    mysqli_stmt_bind_param($top_pet_stmt, 'i', $user_id);
    mysqli_stmt_execute($top_pet_stmt);
    $top_pet_result = mysqli_stmt_get_result($top_pet_stmt);
    if ($top_pet_result && ($top_pet = mysqli_fetch_assoc($top_pet_result))) {
        $top_pet_details_url = 'petdetails.php?id=' . (int)$top_pet['pet_id'];
    }
    mysqli_stmt_close($top_pet_stmt);
}

include 'layout_header.php';
?>

    <style>
        :root {
            --brand-blue: #003B5C;
            --brand-blue-hover: #005682;
            --brand-light: #EBF3F8;
            --brand-light-hover: #D6E8F4;
            --bg-color: #F8FAFC;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            overflow: hidden; 
        }

        .main-content {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* TOP BAR HOVER EFFECTS */
        .top-bar {
            padding: 1.5rem 2rem;
            background: var(--bg-color);
            z-index: 10;
        }
        .top-bar i.fa-bell, .top-bar i.fa-question-circle, .top-bar i.fa-cog {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .top-bar i.fa-bell:hover, .top-bar i.fa-question-circle:hover, .top-bar i.fa-cog:hover {
            color: var(--brand-blue-hover) !important;
            transform: scale(1.2) rotate(5deg);
        }
        .search-bar {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            padding: 0.5rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .search-bar:focus, .search-bar:hover {
            box-shadow: 0 4px 12px rgba(0, 59, 92, 0.1);
            border-color: var(--brand-light-hover);
            outline: none;
        }
        .pet-avatar {
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        .pet-avatar:hover {
            transform: scale(1.15) rotate(5deg);
        }

        .content-split {
            flex: 1;
            display: flex;
            overflow: hidden;
            padding: 0 2rem 2rem 2rem;
            gap: 2rem;
        }
        .clinic-list-col {
            flex: 0 0 400px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        .clinic-list-col::-webkit-scrollbar { width: 6px; }
        .clinic-list-col::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }

        /* SEARCH & FILTER HOVER EFFECTS */
        .location-search .input-group-text, .location-search .form-control {
            border-color: #cbd5e1;
            background: white;
            transition: border-color 0.2s;
        }
        .location-search:hover .form-control, .location-search:hover .input-group-text {
            border-color: var(--brand-blue-hover);
        }
        .location-search .btn {
            border-radius: 0 0.5rem 0.5rem 0;
            background-color: var(--brand-blue);
            color: white;
            transition: all 0.2s ease;
        }
        .location-search .btn:hover {
            background-color: var(--brand-blue-hover);
        }

        .filter-chip {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            border: 1px solid #cbd5e1;
            border-radius: 1rem;
            padding: 0.25rem 0.75rem;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-chip:hover:not(.active) {
            background-color: var(--brand-light-hover);
            color: var(--brand-blue-hover);
            border-color: var(--brand-blue-hover);
            transform: translateY(-2px);
        }
        .filter-chip.active {
            color: var(--brand-blue);
            border-color: var(--brand-blue);
            background-color: var(--brand-light);
        }

        /* CLINIC CARDS HOVER EFFECTS */
        .clinic-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: pointer;
        }
        .clinic-card:hover {
            transform: translateY(-5px);
            border-color: var(--brand-blue-hover);
            box-shadow: 0 10px 20px rgba(0, 59, 92, 0.12);
        }
        .clinic-card.active-card {
            border-color: var(--brand-blue);
            border-left: 4px solid var(--brand-blue);
            box-shadow: 0 4px 12px rgba(0, 59, 92, 0.08);
        }

        /* BUTTON HOVER EFFECTS */
        .btn-primary {
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: var(--brand-blue-hover) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 59, 92, 0.2) !important;
        }
        .btn-outline-secondary {
            transition: all 0.2s ease;
        }
        .btn-outline-secondary:hover {
            background-color: #f1f5f9;
            color: var(--brand-blue-hover);
            transform: translateY(-2px);
        }

        .distance-badge {
            font-size: 0.7rem;
            font-weight: 700;
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }

        /* MAP HOVER EFFECTS */
        .map-col {
            flex: 1;
            border-radius: 1rem;
            background: white;
            position: relative;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .map-frame {
            width: 100%;
            height: 68%;
            border: 0;
        }
        .clinic-detail-panel {
            padding: 1rem 1.25rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            flex: 1;
        }
        .clinic-detail-title {
            color: var(--brand-blue);
            font-weight: 700;
            margin-bottom: 0.4rem;
        }
        .clinic-detail-meta {
            color: #475569;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }
    </style>

        <div class="top-bar d-flex justify-content-between align-items-center">
            <div class="position-relative w-50" style="max-width: 400px;">
                <i class="fas fa-search position-absolute text-muted" style="top: 10px; left: 15px;"></i>
                <input type="text" class="form-control search-bar ps-5" placeholder="Search...">
            </div>
            <div class="d-flex align-items-center gap-3 text-muted">
                <i class="far fa-bell fs-5"></i>
                <i class="far fa-question-circle fs-5"></i>
                <a href="settings.php" class="text-muted text-decoration-none" title="Settings">
                    <i class="fas fa-cog fs-5"></i>
                </a>
                <a href="<?php echo htmlspecialchars($top_pet_details_url); ?>" class="text-decoration-none" title="Go to pet details">
                    <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=100&q=80" class="rounded-circle ms-2 pet-avatar" width="40" height="40" alt="Pet Avatar">
                </a>
            </div>
        </div>

        <div class="content-split">
            <div class="clinic-list-col">
                <h3 class="fw-bold mb-3" style="color: var(--brand-blue);">Find a Clinic</h3>
                
                <div class="input-group location-search mb-3">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Enter zip code or city...">
                    <button class="btn px-3" type="button"><i class="fas fa-search"></i></button>
                </div>

                <div class="d-flex gap-2 mb-4">
                    <span class="filter-chip active">Open Now</span>
                    <span class="filter-chip">Emergency</span>
                    <span class="filter-chip">Specialty</span>
                </div>

                <div class="clinic-cards-container">
                    <?php
                    $clinic_query = mysqli_query($conn, "SELECT * FROM clinics ORDER BY name ASC");
                    $clinics = [];

                    if ($clinic_query && mysqli_num_rows($clinic_query) > 0) {
                        while ($clinic = mysqli_fetch_assoc($clinic_query)) {
                            $clinics[] = $clinic;
                        }
                    }

                    $selected_clinic = null;
                    if (!empty($clinics)) {
                        foreach ($clinics as $clinic) {
                            if ((int)$clinic['clinic_id'] === $selected_clinic_id) {
                                $selected_clinic = $clinic;
                                break;
                            }
                        }
                        if ($selected_clinic === null) {
                            $selected_clinic = $clinics[0];
                        }
                    }

                    if (!empty($clinics)) {
                        foreach ($clinics as $clinic) {
                            $is_active = ((int)$clinic['clinic_id'] === (int)$selected_clinic['clinic_id']) ? 'active-card' : '';
                            $mock_dist = rand(1, 6) . '.' . rand(0, 9) . ' mi';
                            $mock_rating = rand(4, 5) . '.' . rand(0, 9);
                            $mock_reviews = rand(50, 300);
                            ?>
                            <div class="clinic-card <?php echo $is_active; ?>"
                                 data-clinic-id="<?php echo (int)$clinic['clinic_id']; ?>"
                                 data-name="<?php echo htmlspecialchars($clinic['name'], ENT_QUOTES); ?>"
                                 data-address="<?php echo htmlspecialchars($clinic['address'] ?? '', ENT_QUOTES); ?>"
                                 data-phone="<?php echo htmlspecialchars($clinic['phone'] ?? '', ENT_QUOTES); ?>">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h5 class="fw-bold mb-0" style="color: var(--brand-blue);"><?php echo htmlspecialchars($clinic['name']); ?></h5>
                                    <span class="distance-badge"><?php echo $mock_dist; ?></span>
                                </div>

                                <div class="text-warning mb-2" style="font-size: 0.8rem;">
                                    <i class="fas fa-star"></i> <span class="text-dark fw-bold"><?php echo $mock_rating; ?></span> <span class="text-muted">(<?php echo $mock_reviews; ?> reviews)</span>
                                </div>

                                <div class="d-flex align-items-start mb-3 text-muted" style="font-size: 0.85rem;">
                                    <i class="far fa-map mt-1 me-2"></i>
                                    <span><?php echo htmlspecialchars($clinic['address'] ?? 'Address not available'); ?></span>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="book_appointment.php?clinic_id=<?php echo (int)$clinic['clinic_id']; ?>" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center" style="background-color: var(--brand-blue); border: none;">Book Now</a>
                                    <a href="tel:<?php echo htmlspecialchars($clinic['phone'] ?? '', ENT_QUOTES); ?>" class="btn btn-outline-secondary px-3 d-flex align-items-center">
                                        <i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($clinic['phone'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted">No clinics found in the system.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="map-col">
                <?php if (!empty($selected_clinic)): ?>
                    <iframe
                        id="clinicMapFrame"
                        class="map-frame"
                        src="https://www.google.com/maps?q=<?php echo urlencode($selected_clinic['address'] ?? $selected_clinic['name']); ?>&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Clinic Map"
                    ></iframe>
                    <div class="clinic-detail-panel">
                        <h5 class="clinic-detail-title" id="clinicDetailName"><?php echo htmlspecialchars($selected_clinic['name']); ?></h5>
                        <div class="clinic-detail-meta" id="clinicDetailAddress">
                            <i class="fas fa-map-marker-alt me-2 text-muted"></i><?php echo htmlspecialchars($selected_clinic['address'] ?? 'Address not available'); ?>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a id="bookClinicBtn" href="book_appointment.php?clinic_id=<?php echo (int)$selected_clinic['clinic_id']; ?>" class="btn btn-primary fw-bold" style="background: var(--brand-blue); border: none;">Book This Clinic</a>
                            <a id="callClinicBtn" href="tel:<?php echo htmlspecialchars($selected_clinic['phone'] ?? '', ENT_QUOTES); ?>" class="btn btn-outline-secondary fw-bold">
                                <i class="fas fa-phone me-1"></i>Call
                            </a>
                            <a id="openMapBtn" href="https://www.google.com/maps?q=<?php echo urlencode($selected_clinic['address'] ?? $selected_clinic['name']); ?>" target="_blank" class="btn btn-outline-secondary fw-bold">
                                <i class="fas fa-external-link-alt me-1"></i>Open Map
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<script>
    const clinicCards = document.querySelectorAll('.clinic-card[data-clinic-id]');
    const mapFrame = document.getElementById('clinicMapFrame');
    const clinicNameEl = document.getElementById('clinicDetailName');
    const clinicAddressEl = document.getElementById('clinicDetailAddress');
    const bookBtn = document.getElementById('bookClinicBtn');
    const callBtn = document.getElementById('callClinicBtn');
    const openMapBtn = document.getElementById('openMapBtn');

    function updateClinicDetails(card) {
        if (!card || !mapFrame || !clinicNameEl || !clinicAddressEl || !bookBtn || !callBtn || !openMapBtn) {
            return;
        }

        const clinicId = card.dataset.clinicId;
        const name = card.dataset.name || 'Clinic';
        const address = card.dataset.address || 'Address not available';
        const phone = card.dataset.phone || '';
        const query = encodeURIComponent(address || name);

        clinicCards.forEach((c) => c.classList.remove('active-card'));
        card.classList.add('active-card');

        clinicNameEl.textContent = name;
        clinicAddressEl.innerHTML = '<i class="fas fa-map-marker-alt me-2 text-muted"></i>' + address;
        mapFrame.src = 'https://www.google.com/maps?q=' + query + '&output=embed';
        bookBtn.href = 'book_appointment.php?clinic_id=' + clinicId;
        callBtn.href = phone ? ('tel:' + phone) : '#';
        openMapBtn.href = 'https://www.google.com/maps?q=' + query;
    }

    clinicCards.forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('a, button')) {
                return;
            }
            updateClinicDetails(card);
        });
    });
</script>

<?php include 'layout_footer.php'; ?>