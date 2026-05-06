<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email_input = trim($_POST['email'] ?? '');
$password_input = $_POST['password'] ?? '';

if ($email_input === '' || $password_input === '') {
    header('Location: login.php?error=required');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT user_id, first_name, role, password FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email_input);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    $password_ok = $password_input === $user['password'];

    if ($password_ok) {
        
        // Success! Create the session "ID Badge"
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['role'] = $user['role']; 

        // The updated "Traffic Cop" logic using the ENUM strings
        if ($user['role'] == 'Admin') {
            header("Location: adminDashboard.php");
            exit();
        } else if ($user['role'] == 'Customer') {
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: dashboard.php");
            exit();
        }

    } else {
        mysqli_stmt_close($stmt);
        header('Location: login.php?error=invalid');
        exit();
    }
} else {
    mysqli_stmt_close($stmt);
    header('Location: login.php?error=invalid');
    exit();
}

mysqli_stmt_close($stmt);
?>