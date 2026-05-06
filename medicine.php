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

$order_message = '';
$order_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    $medicine_id = (int) ($_POST['medicine_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);

    if ($pet_id <= 0 || $medicine_id <= 0 || $quantity <= 0) {
        $order_error = 'Please select a pet and valid quantity to place the order.';
    } else {
        $pet_stmt = mysqli_prepare($conn, "SELECT pet_id FROM pets WHERE pet_id = ? AND customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($pet_stmt, 'ii', $pet_id, $user_id);
        mysqli_stmt_execute($pet_stmt);
        $pet_result = mysqli_stmt_get_result($pet_stmt);
        $is_pet_valid = mysqli_num_rows($pet_result) > 0;
        mysqli_stmt_close($pet_stmt);

        if (!$is_pet_valid) {
            $order_error = 'Selected pet is invalid.';
        } else {
            $med_stmt = mysqli_prepare($conn, "SELECT price, stock_qty, name FROM medicines WHERE medicine_id = ? LIMIT 1");
            mysqli_stmt_bind_param($med_stmt, 'i', $medicine_id);
            mysqli_stmt_execute($med_stmt);
            $med_result = mysqli_stmt_get_result($med_stmt);
            $medicine = mysqli_fetch_assoc($med_result);
            mysqli_stmt_close($med_stmt);

            if (!$medicine) {
                $order_error = 'Medicine not found.';
            } elseif ((int) $medicine['stock_qty'] < $quantity) {
                $order_error = 'Not enough stock to fulfill this order.';
            } else {
                $unit_price = (float) $medicine['price'];
                $line_total = $unit_price * $quantity;

                mysqli_begin_transaction($conn);
                $ok = true;

                $order_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO pharmacy_orders (customer_id, pet_id, total_amount, status) VALUES (?, ?, ?, 'Placed')"
                );
                if ($order_stmt) {
                    mysqli_stmt_bind_param($order_stmt, 'iid', $user_id, $pet_id, $line_total);
                    $ok = mysqli_stmt_execute($order_stmt);
                    $order_id = $ok ? mysqli_insert_id($conn) : 0;
                    mysqli_stmt_close($order_stmt);
                } else {
                    $ok = false;
                    $order_id = 0;
                }

                if ($ok) {
                    $item_stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO pharmacy_order_items (order_id, medicine_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)"
                    );
                    if ($item_stmt) {
                        mysqli_stmt_bind_param($item_stmt, 'iiidd', $order_id, $medicine_id, $quantity, $unit_price, $line_total);
                        $ok = mysqli_stmt_execute($item_stmt);
                        mysqli_stmt_close($item_stmt);
                    } else {
                        $ok = false;
                    }
                }

                if ($ok) {
                    $stock_stmt = mysqli_prepare(
                        $conn,
                        "UPDATE medicines SET stock_qty = stock_qty - ? WHERE medicine_id = ? AND stock_qty >= ?"
                    );
                    if ($stock_stmt) {
                        mysqli_stmt_bind_param($stock_stmt, 'iii', $quantity, $medicine_id, $quantity);
                        $ok = mysqli_stmt_execute($stock_stmt) && (mysqli_stmt_affected_rows($stock_stmt) > 0);
                        mysqli_stmt_close($stock_stmt);
                    } else {
                        $ok = false;
                    }
                }

                if ($ok) {
                    mysqli_commit($conn);
                    header('Location: medicine.php?order=placed');
                    exit();
                }

                mysqli_rollback($conn);
                $order_error = 'Could not place order. Please try again.';
            }
        }
    }
}

if (isset($_GET['order']) && $_GET['order'] === 'deleted') {
    $order_message = 'Purchase removed successfully.';
} elseif (isset($_GET['order']) && $_GET['order'] === 'placed') {
    $order_message = 'Order placed successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $delete_order_id = (int) $_POST['delete_order_id'];

    if ($delete_order_id <= 0) {
        $order_error = 'Invalid purchase selected.';
    } else {
        $order_stmt = mysqli_prepare(
            $conn,
            "SELECT order_id FROM pharmacy_orders WHERE order_id = ? AND customer_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($order_stmt, 'ii', $delete_order_id, $user_id);
        mysqli_stmt_execute($order_stmt);
        $order_result = mysqli_stmt_get_result($order_stmt);
        $order_row = mysqli_fetch_assoc($order_result);
        mysqli_stmt_close($order_stmt);

        if (!$order_row) {
            $order_error = 'Purchase not found.';
        } else {
            mysqli_begin_transaction($conn);
            $delete_ok = true;

            $items_stmt = mysqli_prepare(
                $conn,
                "SELECT medicine_id, quantity FROM pharmacy_order_items WHERE order_id = ?"
            );
            if ($items_stmt) {
                mysqli_stmt_bind_param($items_stmt, 'i', $delete_order_id);
                mysqli_stmt_execute($items_stmt);
                $items_result = mysqli_stmt_get_result($items_stmt);

                while ($item = mysqli_fetch_assoc($items_result)) {
                    $restore_stmt = mysqli_prepare(
                        $conn,
                        "UPDATE medicines SET stock_qty = stock_qty + ? WHERE medicine_id = ?"
                    );
                    if (!$restore_stmt) {
                        $delete_ok = false;
                        break;
                    }

                    mysqli_stmt_bind_param($restore_stmt, 'ii', $item['quantity'], $item['medicine_id']);
                    $delete_ok = mysqli_stmt_execute($restore_stmt);
                    mysqli_stmt_close($restore_stmt);

                    if (!$delete_ok) {
                        break;
                    }
                }

                mysqli_stmt_close($items_stmt);
            } else {
                $delete_ok = false;
            }

            if ($delete_ok) {
                $delete_items_stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM pharmacy_order_items WHERE order_id = ?"
                );
                if ($delete_items_stmt) {
                    mysqli_stmt_bind_param($delete_items_stmt, 'i', $delete_order_id);
                    $delete_ok = mysqli_stmt_execute($delete_items_stmt);
                    mysqli_stmt_close($delete_items_stmt);
                } else {
                    $delete_ok = false;
                }
            }

            if ($delete_ok) {
                $delete_order_stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM pharmacy_orders WHERE order_id = ? AND customer_id = ?"
                );
                if ($delete_order_stmt) {
                    mysqli_stmt_bind_param($delete_order_stmt, 'ii', $delete_order_id, $user_id);
                    $delete_ok = mysqli_stmt_execute($delete_order_stmt) && (mysqli_stmt_affected_rows($delete_order_stmt) > 0);
                    mysqli_stmt_close($delete_order_stmt);
                } else {
                    $delete_ok = false;
                }
            }

            if ($delete_ok) {
                mysqli_commit($conn);
                header('Location: medicine.php?order=deleted');
                exit();
            }

            mysqli_rollback($conn);
            $order_error = 'Could not remove that purchase.';
        }
    }
}

include 'layout_header.php';

// Fetch all medicines from your database
$med_query = mysqli_query($conn, "SELECT * FROM medicines ORDER BY name ASC");
$pets_query = mysqli_query($conn, "SELECT pet_id, name FROM pets WHERE customer_id = $user_id ORDER BY name ASC");
$pets_list = [];
if ($pets_query && mysqli_num_rows($pets_query) > 0) {
    while ($pet_row = mysqli_fetch_assoc($pets_query)) {
        $pets_list[] = $pet_row;
    }
}
$orders_query = mysqli_query(
    $conn,
    "SELECT o.order_id, o.total_amount, o.created_at, o.status, p.name AS pet_name,
            GROUP_CONCAT(CONCAT(m.name, ' x', oi.quantity) ORDER BY m.name SEPARATOR ', ') AS item_summary
     FROM pharmacy_orders o
     JOIN pets p ON o.pet_id = p.pet_id
     JOIN pharmacy_order_items oi ON o.order_id = oi.order_id
     JOIN medicines m ON oi.medicine_id = m.medicine_id
     WHERE o.customer_id = $user_id
     GROUP BY o.order_id, o.total_amount, o.created_at, o.status, p.name
     ORDER BY o.created_at DESC"
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; flex: 0 0 auto;">
            <i class="fas fa-paw"></i>
        </div>
        <div>
            <h2 class="fw-bold mb-1">Pharmacy Catalog</h2>
            <p class="text-muted mb-0">Standardized medications, dosages, pricing, and stock visibility.</p>
        </div>
    </div>
</div>

<div class="glass-card p-0 overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">MEDICATION NAME</th>
                    <th class="border-0 py-3 small fw-bold text-muted">DOSAGE (MG)</th>
                    <th class="border-0 py-3 small fw-bold text-muted">STOCK</th>
                    <th class="border-0 py-3 small fw-bold text-muted">UNIT PRICE</th>
                    <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">ORDER</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($med_query) > 0): ?>
                    <?php while($med = mysqli_fetch_assoc($med_query)): ?>
                    <tr style="vertical-align: middle;">
                        <td class="ps-4 py-3 fw-bold" style="color: var(--brand-blue);">
                            <i class="fas fa-pills me-2 text-muted"></i> <?= htmlspecialchars($med['name']) ?>
                        </td>
                        <td class="py-3 text-muted"><?= htmlspecialchars($med['dosage_mg']) ?> mg</td>
                        <td class="py-3">
                            <span class="badge <?= ((int)$med['stock_qty'] > 0) ? 'bg-light text-dark' : 'bg-danger' ?> border">
                                <?= (int)$med['stock_qty'] ?>
                            </span>
                        </td>
                        <td class="py-3 fw-bold text-success">
                            $<?= number_format($med['price'], 2) ?>
                        </td>
                        <td class="pe-4 py-3 text-end">
                            <form method="POST" class="d-inline-flex gap-2 align-items-center justify-content-end flex-wrap">
                                <input type="hidden" name="place_order" value="1">
                                <input type="hidden" name="medicine_id" value="<?= (int)$med['medicine_id'] ?>">
                                <select name="pet_id" class="form-select form-select-sm" style="min-width: 140px;" required>
                                    <option value="" disabled selected>Select pet</option>
                                    <?php if (!empty($pets_list)): ?>
                                        <?php foreach ($pets_list as $pet): ?>
                                            <option value="<?= (int)$pet['pet_id'] ?>"><?= htmlspecialchars($pet['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <input type="number" name="quantity" min="1" value="1" class="form-control form-control-sm" style="width: 80px;" required>
                                <button type="submit" class="btn btn-sm btn-primary fw-bold" style="background: var(--brand-blue); border: none;" <?= ((int)$med['stock_qty'] <= 0) ? 'disabled' : '' ?>>
                                    Order
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center p-5 text-muted">No medications found in the catalog.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 mt-4">
    <div>
        <h5 class="fw-bold mb-1">My Purchases</h5>
        <p class="text-muted mb-0">Delete any of your own purchases if needed. Stock will be restored automatically.</p>
    </div>
</div>

<div class="glass-card p-0 overflow-hidden shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 border-0 py-3 small fw-bold text-muted">DATE</th>
                    <th class="border-0 py-3 small fw-bold text-muted">PET</th>
                    <th class="border-0 py-3 small fw-bold text-muted">ITEMS</th>
                    <th class="border-0 py-3 small fw-bold text-muted">TOTAL</th>
                    <th class="border-0 py-3 small fw-bold text-muted">STATUS</th>
                    <th class="pe-4 border-0 py-3 text-end small fw-bold text-muted">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_query && mysqli_num_rows($orders_query) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_query)): ?>
                    <tr style="vertical-align: middle;">
                        <td class="ps-4 py-3 fw-bold"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        <td class="py-3"><?= htmlspecialchars($order['pet_name']) ?></td>
                        <td class="py-3 text-muted"><?= htmlspecialchars($order['item_summary']) ?></td>
                        <td class="py-3 fw-bold text-success">$<?= number_format((float)$order['total_amount'], 2) ?></td>
                        <td class="py-3"><span class="badge bg-light text-dark border"><?= htmlspecialchars($order['status']) ?></span></td>
                        <td class="pe-4 py-3 text-end">
                            <form method="POST" class="mb-0" onsubmit="return confirm('Delete this purchase and restore stock?');">
                                <input type="hidden" name="delete_order_id" value="<?= (int)$order['order_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center p-5 text-muted">No purchases found yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout_footer.php'; ?>