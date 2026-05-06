<?php
// 1. Initialize the session so PHP knows which session to destroy
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session entirely
session_destroy();

// 4. Redirect the user back to the login page
header("Location: login.php");
exit();
?>