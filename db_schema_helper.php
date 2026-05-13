<?php
/**
 * Database Schema Helper
 * Detects actual database columns to ensure compatibility across different database versions
 */

/**
 * Get all column names for a table
 */
function get_table_columns($conn, $table_name) {
    $columns = [];
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conn, $table_name) . "`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
        mysqli_free_result($result);
    }
    return $columns;
}

/**
 * Check if a specific column exists in a table
 */
function column_exists($conn, $table_name, $column_name) {
    $cols = get_table_columns($conn, $table_name);
    return in_array($column_name, $cols);
}

/**
 * Build INSERT query with only existing columns
 */
function build_insert_query($conn, $table, $data) {
    $existing = get_table_columns($conn, $table);
    $valid_data = [];
    foreach ($data as $col => $val) {
        if (in_array($col, $existing)) {
            $valid_data[$col] = $val;
        }
    }
    if (empty($valid_data)) {
        return false;
    }
    $cols = array_keys($valid_data);
    $placeholders = array_fill(0, count($cols), '?');
    $sql = "INSERT INTO " . $table . " (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    return ['sql' => $sql, 'data' => array_values($valid_data), 'columns' => $cols];
}

/**
 * Get appointments table available columns
 */
function get_appointments_columns($conn) {
    return get_table_columns($conn, 'appointments');
}

/**
 * Check if appointments table has clinic_id
 */
function appointments_has_clinic_id($conn) {
    return column_exists($conn, 'appointments', 'clinic_id');
}

/**
 * Check if appointments table has vet_id
 */
function appointments_has_vet_id($conn) {
    return column_exists($conn, 'appointments', 'vet_id');
}