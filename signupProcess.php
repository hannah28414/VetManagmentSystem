<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signUp.php');
    exit();
}

// Grab and sanitize data from the registration form
$full_name = trim($_POST['first_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
    echo "<h3>All fields are required.</h3><br><a href='signUp.php'>Go back</a>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<h3>Please enter a valid email address.</h3><br><a href='signUp.php'>Go back</a>";
    exit();
}

if ($password !== $confirm_password) {
    echo "<h3>Passwords do not match.</h3><br><a href='signUp.php'>Go back</a>";
    exit();
}

// Your database requires a first and last name. 
// This splits "Jane Doe" into First: "Jane", Last: "Doe"
$name_parts = explode(" ", $full_name, 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : ''; // Leaves blank if no last name provided

// 1. Check if the email is already in the database
$check_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($check_stmt, 's', $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    mysqli_stmt_close($check_stmt);
    echo "<h3>That email is already registered!</h3><br><a href='signUp.php'>Try a different one</a>";
    exit();
}
mysqli_stmt_close($check_stmt);

// 2. Insert the new user into the database as a 'Customer'
$new_role = 'Customer';
$insert_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($insert_stmt, 'sssss', $first_name, $last_name, $email, $password, $new_role);

if (mysqli_stmt_execute($insert_stmt)) {
    $new_user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert_stmt);

    // Auto-login after successful signup
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['role'] = $new_role;

    if ($new_role === 'Admin') {
        header("Location: adminDashboard.php");
        exit();
    }

    header("Location: dashboard.php");
    exit();
} else {
    $error = mysqli_error($conn);
    mysqli_stmt_close($insert_stmt);
    echo "Error inserting record: " . $error;
}
?>