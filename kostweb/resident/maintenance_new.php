<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isLoggedIn() || isAdmin()){
    header("location: ../login.php");
    exit;
}

// Check if resident has a room assigned
if(!isset($_SESSION["room_id"]) || empty($_SESSION["room_id"])){
    redirectWithMessage("dashboard.php", "You need to be assigned a room before submitting maintenance requests.");
}

// Define variables and initialize with empty values
$request_type = $description = "";
$request_type_err = $description_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate request type
    if(empty(trim($_POST["request_type"]))){
        $request_type_err = "Please select a request type.";
    } else{
        $request_type = trim($_POST["request_type"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = cleanInput($_POST["description"]);
    }
    
    // Check input errors before inserting in database
    if(empty($request_type_err) && empty($description_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO maintenance_requests (resident_id, room_id, request_type, description) VALUES (?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "iiss", $param_resident_id, $param_room_id, $param_request_type, $param_description);
            
            // Set parameters
            $param_resident_id = $_SESSION["resident_id"];
            $param_room_id = $_SESSION["room_id"];
            $param_request_type = $request_type;
            $param_description = $description;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                redirectWithMessage("maintenance.php", "Maintenance request submitted successfully.", "success");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Maintenance Request - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "resident_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Submit New Maintenance Request</h2>
            <?php displayMessage(); ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Request Type</label>
                    <select name="request_type" class="form-control <?php echo (!empty($request_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo $request_type == "" ? 'selected' : ''; ?>>Select request type</option>
                        <option value="plumbing" <?php echo $request_type == "plumbing" ? 'selected' : ''; ?>>Plumbing</option>
                        <option value="electrical" <?php echo $request_type == "electrical" ? 'selected' : ''; ?>>Electrical</option>
                        <option value="furniture" <?php echo $request_type == "furniture" ? 'selected' : ''; ?>>Furniture</option>
                        <option value="cleaning" <?php echo $request_type == "cleaning" ? 'selected' : ''; ?>>Cleaning</option>
                        <option value="other" <?php echo $request_type == "other" ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $request_type_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5"><?php echo $description; ?></textarea>
                    <span class="invalid-feedback"><?php echo $description_err; ?></span>
                    <small>Please describe the issue in detail and provide any relevant information.</small>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit Request">
                    <a href="maintenance.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>