<?php
// Include config file
require_once "config.php";

// Check if the user is already logged in, if yes then redirect accordingly
if(isLoggedIn()){
    if(isAdmin()){
        header("location: admin/dashboard.php");
        exit;
    } else {
        header("location: resident/dashboard.php");
        exit;
    }
}

// Redirect to login page
header("location: login.php");
exit;
?>