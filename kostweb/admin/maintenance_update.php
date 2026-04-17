<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in and is admin, if not then redirect to login page
if(!isLoggedIn() || !isAdmin()){
    header("location: ../login.php");
    exit;
}

// Check existence of id parameter before processing further
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Get URL parameter
    $id = trim($_GET["id"]);
    
    // Prepare a select statement
    $sql = "SELECT mr.*, r.room_number, res.full_name 
            FROM maintenance_requests mr
            JOIN rooms r ON mr.room_id = r.id
            JOIN residents res ON mr.resident_id = res.id
            WHERE mr.id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        
        // Set parameters
        $param_id = $id;
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
    
            if(mysqli_num_rows($result) == 1){
                // Fetch result row as an associative array
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                
                // Retrieve individual field value
                $resident_id = $row["resident_id"];
                $room_id = $row["room_id"];
                $room_number = $row["room_number"];
                $resident_name = $row["full_name"];
                $request_type = $row["request_type"];
                $description = $row["description"];
                $request_date = $row["request_date"];
                $status = $row["status"];
                $resolved_date = $row["resolved_date"];
                $notes = $row["notes"];
                
            } else{
                // URL doesn't contain valid id parameter
                redirectWithMessage("maintenance.php", "Invalid maintenance request ID.");
                exit();
            }
            
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        mysqli_stmt_close($stmt);
    }
} else{
    // URL doesn't contain id parameter
    redirectWithMessage("maintenance.php", "Maintenance request ID is required.");
    exit();
}

// Define error variables
$status_err = $notes_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate status
    if(empty(trim($_POST["status"]))){
        $status_err = "Please select a status.";
    } else{
        $status = cleanInput($_POST["status"]);
    }
    
    // Get notes
    $notes = cleanInput($_POST["notes"]);
    
    // Get resolved date
    $resolved_date = null;
    if($status == "completed" || $status == "rejected"){
        $resolved_date = date("Y-m-d H:i:s");
    }
    
    // Check input errors before updating in database
    if(empty($status_err)){
        // Prepare an update statement
        $sql = "UPDATE maintenance_requests SET status = ?, resolved_date = ?, notes = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssi", $param_status, $param_resolved_date, $param_notes, $param_id);
            
            // Set parameters
            $param_status = $status;
            $param_resolved_date = $resolved_date;
            $param_notes = $notes;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to maintenance page
                redirectWithMessage("maintenance.php", "Maintenance request updated successfully.", "success");
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
    <title>Update Maintenance Request - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Update Maintenance Request</h2>
            <p>Please update the maintenance request status below.</p>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">Request Details</div>
                <div class="card-body">
                    <p><strong>Room:</strong> <?php echo htmlspecialchars($room_number); ?></p>
                    <p><strong>Resident:</strong> <?php echo htmlspecialchars($resident_name); ?></p>
                    <p><strong>Type:</strong> <?php echo ucfirst(htmlspecialchars($request_type)); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
                    <p><strong>Request Date:</strong> <?php echo htmlspecialchars($request_date); ?></p>
                </div>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                        <option value="pending" <?php echo $status == "pending" ? "selected" : ""; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status == "in_progress" ? "selected" : ""; ?>>In Progress</option>
                        <option value="completed" <?php echo $status == "completed" ? "selected" : ""; ?>>Completed</option>
                        <option value="rejected" <?php echo $status == "rejected" ? "selected" : ""; ?>>Rejected</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $status_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="5"><?php echo htmlspecialchars($notes ?? ""); ?></textarea>
                    <small>Provide any details about the maintenance work or reason for rejection.</small>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Request">
                    <a href="maintenance.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>