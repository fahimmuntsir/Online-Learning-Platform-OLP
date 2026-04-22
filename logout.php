<?php
// Start the session
session_start();

// Destroy the session and clear session variables
session_unset();
session_destroy();


// Redirect to login page
header("Location: login.html");
exit();
?>
