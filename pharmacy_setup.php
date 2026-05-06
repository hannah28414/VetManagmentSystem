<?php
function ensure_pharmacy_schema($conn) {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS refill_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            pet_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL,
            note VARCHAR(255) DEFAULT '',
            status VARCHAR(30) NOT NULL DEFAULT 'Pending',
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS pharmacy_orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            pet_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'Placed',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS pharmacy_order_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            medicine_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            line_total DECIMAL(10,2) NOT NULL DEFAULT 0
        )"
    );

    // Ensure medicines table has stock tracking.
    $stock_col = mysqli_query($conn, "SHOW COLUMNS FROM medicines LIKE 'stock_qty'");
    if ($stock_col && mysqli_num_rows($stock_col) === 0) {
        mysqli_query($conn, "ALTER TABLE medicines ADD COLUMN stock_qty INT NOT NULL DEFAULT 100");
    }

    $initialized = true;
}
