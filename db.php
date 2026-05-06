<?php
$host = 'localhost';
$user = 'root';      // Default XAMPP username
$password = '';      // Default XAMPP password is blank
$dbname = 'pawhealth'; // Change this if your database has a different name!

// Create the connection
$conn = mysqli_connect($host, $user, $password, $dbname);

// Check if the connection failed
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>