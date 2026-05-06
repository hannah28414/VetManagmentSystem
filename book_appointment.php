<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}



$user_id = (int)($_SESSION['user_id'] ?? 0);
$booking_error = '';
$clinic_id = isset($_GET['clinic_id']) ? (int)$_GET['clinic_id'] : 1;
$selected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : (int)($_POST['service_id'] ?? 0);
$selected_date = $_POST['appointment_date'] ?? date('Y-m-d');
$selected_time = $_POST['appointment_time'] ?? '11:30';
$allowed_times = ['09:00', '09:30', '10:00', '10:30', '11:30', '13:00', '13:30'];

$pets_result = mysqli_query($conn, "SELECT pet_id, name FROM pets WHERE customer_id = $user_id ORDER BY name ASC");
$services_result = mysqli_query($conn, "SELECT service_id, name FROM services ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';

    if ($pet_id <= 0 || $service_id <= 0 || $appointment_date === '' || $appointment_time === '') {
        $booking_error = 'Please select pet, service, date, and time.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date) || !preg_match('/^\d{2}:\d{2}$/', $appointment_time)) {
        $booking_error = 'Please choose a valid appointment date and time.';
    } elseif (!in_array($appointment_time, $allowed_times, true)) {
        $booking_error = 'Selected time is not available.';
    } else {
        // Ensure the selected pet belongs to the logged-in customer.
        $pet_check_stmt = mysqli_prepare($conn, "SELECT pet_id FROM pets WHERE pet_id = ? AND customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($pet_check_stmt, 'ii', $pet_id, $user_id);
        mysqli_stmt_execute($pet_check_stmt);
        $pet_check_result = mysqli_stmt_get_result($pet_check_stmt);

        if (mysqli_num_rows($pet_check_result) === 0) {
            $booking_error = 'Invalid pet selected.';
            mysqli_stmt_close($pet_check_stmt);
        } else {
            mysqli_stmt_close($pet_check_stmt);

            $service_check_stmt = mysqli_prepare($conn, "SELECT service_id FROM services WHERE service_id = ? LIMIT 1");
            mysqli_stmt_bind_param($service_check_stmt, 'i', $service_id);
            mysqli_stmt_execute($service_check_stmt);
            $service_check_result = mysqli_stmt_get_result($service_check_stmt);

            if (mysqli_num_rows($service_check_result) === 0) {
                $booking_error = 'Invalid service selected.';
                mysqli_stmt_close($service_check_stmt);
            } else {
                mysqli_stmt_close($service_check_stmt);
            $appointment_timestamp = strtotime($appointment_date . ' ' . $appointment_time);
            if ($appointment_timestamp === false) {
                $booking_error = 'Please choose a valid appointment date and time.';
            } elseif ($appointment_timestamp < time()) {
                $booking_error = 'Please choose a future appointment date and time.';
            } else {
                $appointment_datetime = date('Y-m-d H:i:s', $appointment_timestamp);

                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO appointments (pet_id, clinic_id, service_id, date, status) VALUES (?, ?, ?, ?, 'Scheduled')"
                );
                mysqli_stmt_bind_param($insert_stmt, 'iiis', $pet_id, $clinic_id, $service_id, $appointment_datetime);

                if (mysqli_stmt_execute($insert_stmt)) {
                    mysqli_stmt_close($insert_stmt);
                    header('Location: dashboard.php?booking=success');
                    exit();
                }

                $booking_error = 'Could not save appointment. Please try again.';
                mysqli_stmt_close($insert_stmt);
            }
            }
        }
    }
}

$clinic_query = mysqli_query($conn, "SELECT name FROM clinics WHERE clinic_id = $clinic_id");
$clinic = mysqli_fetch_assoc($clinic_query);

include 'layout_header.php';
?>

<style>
    /* ANIMATIONS */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .animate-in { animation: fadeIn 0.5s ease forwards; }

    /* STEPPER */
    .stepper-wrap { display: flex; align-items: center; justify-content: flex-end; gap: 15px; }
    .step-item { display: flex; align-items: center; gap: 8px; font-size: 0.75rem; font-weight: 700; color: #cbd5e1; }
    .step-item.active { color: var(--brand-blue); }
    .step-item.done { color: #1e293b; }
    .step-circle { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
    .step-item.active .step-circle { border-color: var(--brand-blue); color: var(--brand-blue); box-shadow: 0 0 0 4px rgba(0,59,92,0.1); }
    .step-item.done .step-circle { background: var(--brand-blue); border-color: var(--brand-blue); color: white; }

    /* CONTENT BOXES */
    .glass-card { 
        background: white; border: 1px solid #e2e8f0; border-radius: 20px; 
        padding: 2rem; margin-bottom: 2rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
    }
    .glass-card:hover { border-color: #cbd5e1; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05); }

    /* PATIENT SUMMARY BAR */
    .patient-bar { 
        background: #F1F5F9; border-radius: 15px; padding: 1rem 1.5rem; 
        display: flex; align-items: center; gap: 15px; margin-bottom: 2.5rem;
    }

    /* CALENDAR */
    .calendar-box {
        max-width: 430px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
        padding: 14px;
    }
    .calendar-weekdays,
    #calendarDays {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 5px;
        text-align: center;
    }
    .day-label { font-size: 0.68rem; font-weight: 800; color: #94a3b8; margin-bottom: 6px; }
    .day-num { 
        padding: 8px 0;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 600; 
        color: #475569; cursor: pointer; transition: 0.2s; position: relative;
    }
    .day-num:hover:not(.empty) { background: #EBF3F8; color: var(--brand-blue); transform: translateY(-2px); }
    .day-num.active { background: var(--brand-blue); color: white; box-shadow: 0 8px 15px rgba(0,59,92,0.2); }
    .day-num.disabled {
        color: #c0c8d3;
        cursor: not-allowed;
        background: #f4f6f8;
        pointer-events: none;
    }
    .event-dot { width: 4px; height: 4px; background: var(--brand-accent); border-radius: 50%; position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%); }

    /* TIME SLOTS */
    .time-slot { 
        background: #F8FAFC; border: 1px solid #e2e8f0; padding: 12px; 
        border-radius: 12px; text-align: center; font-weight: 700; font-size: 0.8rem;
        cursor: pointer; transition: 0.2s;
    }
    .time-slot:hover { border-color: var(--brand-accent); color: var(--brand-accent); background: white; transform: scale(1.05); }
    .time-slot.active { background: #EBF3F8; border: 2px solid var(--brand-blue); color: var(--brand-blue); }
    .time-slot.disabled {
        opacity: 0.35;
        pointer-events: none;
        background: #f2f4f7;
    }

    .btn-next {
        background: var(--brand-blue); color: white; padding: 14px 35px; 
        border-radius: 12px; font-weight: 800; border: none; transition: 0.3s;
    }
    .btn-next:hover {
        background: #005682;
        transform: translateY(-3px);
        box-shadow: 0 12px 20px rgba(0,59,92,0.25);
        color: white;
    }

    .field-label {
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .calendar-header-btn {
        border: 1px solid #d6dee8;
        background: #f8fafc;
    }

    .selected-datetime {
        background: #eef5fb;
        border: 1px solid #d6e5f2;
        border-radius: 10px;
        padding: 10px 12px;
        color: #37526a;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 18px;
        min-height: 54px;
        display: flex;
        align-items: center;
    }

    .service-summary {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 10px 12px;
        color: #9a3412;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 18px;
        min-height: 54px;
        display: flex;
        align-items: center;
    }

    .summary-row {
        align-items: stretch;
    }
</style>

<div class="animate-in">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <a href="clinic_locator.php" class="text-decoration-none text-muted small fw-bold"><i class="fas fa-chevron-left me-2"></i>BACK TO LOCATOR</a>
            <h2 class="fw-bold mt-2" style="color: var(--brand-blue);">Book Appointment</h2>
        </div>
        <div class="stepper-wrap">
            <div class="step-item done"><div class="step-circle"><i class="fas fa-check"></i></div> Pet</div>
            <div class="step-item done"><div class="step-circle"><i class="fas fa-check"></i></div> Details</div>
            <div class="step-item active"><div class="step-circle">3</div> Date & Time</div>
            <div class="step-item"><div class="step-circle">4</div> Finish</div>
        </div>
    </div>

    <div class="patient-bar">
        <img src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=100&q=80" width="45" height="45" class="rounded-circle shadow-sm">
        <div class="flex-grow-1">
            <p class="mb-0 text-muted fw-bold" style="font-size: 0.6rem; text-transform: uppercase;">Reviewing selection for</p>
            <h6 class="mb-0 fw-bold">Bella • Wellness Checkup</h6>
        </div>
        <button class="btn btn-sm btn-white bg-white shadow-sm rounded-pill px-3 fw-bold text-primary" style="font-size: 0.7rem;">Change</button>
    </div>

    <?php if ($booking_error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($booking_error); ?></div>
    <?php endif; ?>

    <form method="POST" class="glass-card">
        <div class="row g-3 summary-row">
            <div class="col-md-6">
                <label class="field-label">Pet</label>
                <select name="pet_id" class="form-select" required>
                    <option value="">Select pet</option>
                    <?php if ($pets_result && mysqli_num_rows($pets_result) > 0): ?>
                        <?php while ($pet = mysqli_fetch_assoc($pets_result)): ?>
                            <option value="<?php echo (int)$pet['pet_id']; ?>"><?php echo htmlspecialchars($pet['name']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="selected-datetime w-100 mb-0" id="selectedDateTimeLabel"></div>
            </div>
        </div>
        <div class="row g-3 mt-1 summary-row">
            <div class="col-md-6">
                <label class="field-label">Service</label>
                <select name="service_id" class="form-select" required>
                    <option value="">Select service</option>
                    <?php if ($services_result && mysqli_num_rows($services_result) > 0): ?>
                        <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                            <option value="<?php echo (int)$service['service_id']; ?>" <?php echo ((int)$service['service_id'] === $selected_service_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="service-summary w-100 mb-0" id="selectedServiceLabel">
                    Service: <?php echo htmlspecialchars($selected_service_id ? 'Preselected from services page' : 'Choose a service'); ?>
                </div>
            </div>
        </div>
        <input type="hidden" name="appointment_date" id="appointment_date" value="<?php echo htmlspecialchars($selected_date); ?>">
        <input type="hidden" name="appointment_time" id="appointment_time" value="<?php echo htmlspecialchars($selected_time); ?>">
        <div class="d-flex justify-content-end mt-3">
            <small class="text-muted">Clinic: <?php echo htmlspecialchars($clinic['name'] ?? 'Main Clinic'); ?></small>
        </div>
    

    <div class="glass-card">
        <div class="row">
            <div class="col-md-7 border-end pe-md-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" id="calendarMonthLabel"></h5>
                    <div class="d-flex gap-2">
                        <button type="button" id="prevMonthBtn" class="btn btn-sm rounded-circle calendar-header-btn"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" id="nextMonthBtn" class="btn btn-sm rounded-circle calendar-header-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                
                <div class="calendar-box">
                    <div class="calendar-weekdays">
                        <div class="day-label">S</div><div class="day-label">M</div><div class="day-label">T</div><div class="day-label">W</div><div class="day-label">T</div><div class="day-label">F</div><div class="day-label">S</div>
                    </div>
                    <div id="calendarDays"></div>
                </div>
            </div>

            <div class="col-md-5 ps-md-5">
                <h5 class="fw-bold mb-4">Available Times</h5>
                
                <p class="text-muted fw-bold mb-3" style="font-size: 0.65rem; text-transform: uppercase;"><i class="fas fa-sun me-2"></i>Morning</p>
                <div class="row g-2 mb-4">
                    <div class="col-4"><div class="time-slot" data-time="09:00">09:00</div></div>
                    <div class="col-4"><div class="time-slot" data-time="09:30">09:30</div></div>
                    <div class="col-4"><div class="time-slot" data-time="10:00">10:00</div></div>
                    <div class="col-4"><div class="time-slot" data-time="10:30">10:30</div></div>
                    <div class="col-4"><div class="time-slot" data-time="11:30">11:30</div></div>
                </div>

                <p class="text-muted fw-bold mb-3" style="font-size: 0.65rem; text-transform: uppercase;"><i class="fas fa-cloud-sun me-2"></i>Afternoon</p>
                <div class="row g-2">
                    <div class="col-4"><div class="time-slot" data-time="13:00">01:00</div></div>
                    <div class="col-4"><div class="time-slot" data-time="13:30">01:30</div></div>
                    <div class="col-4"><div class="time-slot disabled" data-time="14:30" data-permanent-disabled="1">02:30</div></div>
                </div>
            </div>
        </div>
    </div>

        <div class="d-flex justify-content-between align-items-center">
            <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='dashboard.php'; }" class="btn btn-link text-muted fw-bold text-decoration-none" style="font-size: 0.8rem;">CANCEL</button>
            <button type="submit" name="book_appointment" class="btn btn-next">BOOK APPOINTMENT <i class="fas fa-arrow-right ms-2"></i></button>
        </div>
    </form>
</div>

<script>
    const monthLabel = document.getElementById('calendarMonthLabel');
    const calendarDays = document.getElementById('calendarDays');
    const selectedDateInput = document.getElementById('appointment_date');
    const selectedTimeInput = document.getElementById('appointment_time');
    const selectedDateTimeLabel = document.getElementById('selectedDateTimeLabel');
    const selectedServiceLabel = document.getElementById('selectedServiceLabel');
    const serviceSelect = document.querySelector('select[name="service_id"]');
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');
    const today = new Date();
    const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    let selectedDate = new Date(selectedDateInput.value + 'T00:00:00');
    if (isNaN(selectedDate.getTime())) {
        selectedDate = new Date(todayStart);
    }
    if (selectedDate < todayStart) {
        selectedDate = new Date(todayStart);
    }
    let viewMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);

    function formatDateForInput(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function updateSelectedDateTimeLabel() {
        const dateText = selectedDate.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        selectedDateTimeLabel.textContent = 'Selected: ' + dateText + ' at ' + selectedTimeInput.value;
    }

    function updateSelectedServiceLabel() {
        const selectedText = serviceSelect.options[serviceSelect.selectedIndex]?.text || 'Choose a service';
        selectedServiceLabel.textContent = 'Service: ' + selectedText;
    }

    function updateTimeSlotAvailability() {
        const selectedDateStart = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
        const isToday = selectedDateStart.getTime() === todayStart.getTime();
        const nowMinutes = today.getHours() * 60 + today.getMinutes();
        let activeStillValid = false;

        document.querySelectorAll('.time-slot[data-time]').forEach(slot => {
            const timeValue = slot.dataset.time;
            const parts = timeValue.split(':');
            const slotMinutes = (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
            const isPermanentlyDisabled = slot.dataset.permanentDisabled === '1';
            const shouldDisable = isPermanentlyDisabled || (isToday && slotMinutes <= nowMinutes);

            if (shouldDisable) {
                slot.classList.add('disabled');
                slot.classList.remove('active');
            } else {
                slot.classList.remove('disabled');
                if (timeValue === selectedTimeInput.value) {
                    slot.classList.add('active');
                    activeStillValid = true;
                }
            }
        });

        if (!activeStillValid) {
            const firstEnabled = document.querySelector('.time-slot[data-time]:not(.disabled)');
            if (firstEnabled) {
                document.querySelectorAll('.time-slot[data-time]').forEach(s => s.classList.remove('active'));
                firstEnabled.classList.add('active');
                selectedTimeInput.value = firstEnabled.dataset.time;
            }
        }
    }

    function renderCalendar() {
        calendarDays.innerHTML = '';
        monthLabel.textContent = viewMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

        const year = viewMonth.getFullYear();
        const month = viewMonth.getMonth();
        const firstDayIndex = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'day-num empty';
            calendarDays.appendChild(emptyCell);
        }

        for (let day = 1; day <= totalDays; day++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'day-num';
            dayCell.textContent = day;

            const candidateDate = new Date(year, month, day);
            const candidateStart = new Date(candidateDate.getFullYear(), candidateDate.getMonth(), candidateDate.getDate());
            const isPast = candidateStart < todayStart;
            const isSelected = candidateDate.toDateString() === selectedDate.toDateString();

            if (isPast) {
                dayCell.classList.add('disabled');
            }
            if (isSelected) {
                dayCell.classList.add('active');
            }

            dayCell.addEventListener('click', () => {
                if (isPast) {
                    return;
                }
                selectedDate = new Date(year, month, day);
                selectedDateInput.value = formatDateForInput(selectedDate);
                updateTimeSlotAvailability();
                updateSelectedDateTimeLabel();
                renderCalendar();
            });

            calendarDays.appendChild(dayCell);
        }
    }

    prevMonthBtn.addEventListener('click', () => {
        const previousMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
        const currentMonthStart = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1);
        if (previousMonth >= currentMonthStart) {
            viewMonth = previousMonth;
            renderCalendar();
        }
    });

    nextMonthBtn.addEventListener('click', () => {
        viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
        renderCalendar();
    });

    document.querySelectorAll('.time-slot[data-time]').forEach(slot => {
        slot.addEventListener('click', () => {
            if (slot.classList.contains('disabled')) {
                return;
            }
            document.querySelectorAll('.time-slot[data-time]').forEach(s => s.classList.remove('active'));
            slot.classList.add('active');
            selectedTimeInput.value = slot.dataset.time;
            updateSelectedDateTimeLabel();
        });
    });

    serviceSelect.addEventListener('change', updateSelectedServiceLabel);

    selectedDateInput.value = formatDateForInput(selectedDate);
    updateTimeSlotAvailability();
    updateSelectedDateTimeLabel();
    updateSelectedServiceLabel();
    renderCalendar();
</script>

<?php include 'layout_footer.php'; ?>