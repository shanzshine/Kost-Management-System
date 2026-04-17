<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'kost_management');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Function to check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
}

// Function to redirect with message
function redirectWithMessage($location, $message, $type = "danger") {
    $_SESSION["flash_message"] = $message;
    $_SESSION["flash_type"] = $type;
    header("location: $location");
    exit;
}

// Function to display flash messages
function displayMessage() {
    if(isset($_SESSION["flash_message"])) {
        $message = $_SESSION["flash_message"];
        $type = $_SESSION["flash_type"];
        echo "<div class='alert alert-$type'>$message</div>";
        unset($_SESSION["flash_message"]);
        unset($_SESSION["flash_type"]);
    }
}

// Function to clean data
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}
?>