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
    $sql = "SELECT p.*, r.room_number, res.full_name 
            FROM payments p
            JOIN rooms r ON p.room_id = r.id
            JOIN residents res ON p.resident_id = res.id
            WHERE p.id = ?";
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
                
                // Check if payment is already processed
                if($row["status"] != "pending"){
                    redirectWithMessage("payments.php", "This payment has already been processed.");
                    exit();
                }
                
                // Retrieve individual field value
                $resident_id = $row["resident_id"];
                $room_id = $row["room_id"];
                $room_number = $row["room_number"];
                $resident_name = $row["full_name"];
                $amount = $row["amount"];
                $payment_date = $row["payment_date"];
                $payment_method = $row["payment_method"];
                $payment_proof = $row["payment_proof"];
                $description = $row["description"];
                $status = $row["status"];
                
            } else{
                // URL doesn't contain valid id parameter
                redirectWithMessage("payments.php", "Invalid payment ID.");
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
    redirectWithMessage("payments.php", "Payment ID is required.");
    exit();
}

// Define error variables
$status_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate status
    if(empty(trim($_POST["status"]))){
        $status_err = "Please select a status.";
    } else{
        $status = cleanInput($_POST["status"]);
    }
    
    // Check input errors before updating in database
    if(empty($status_err)){
        // Prepare an update statement
        $sql = "UPDATE payments SET status = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_status, $param_id);
            
            // Set parameters
            $param_status = $status;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to payments page
                redirectWithMessage("payments.php", "Payment status updated successfully.", "success");
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
    <title>Update Payment - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Update Payment</h2>
            <p>Please update the payment status below.</p>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">Payment Details</div>
                <div class="card-body">
                    <p><strong>Room:</strong> <?php echo htmlspecialchars($room_number); ?></p>
                    <p><strong>Resident:</strong> <?php echo htmlspecialchars($resident_name); ?></p>
                    <p><strong>Amount:</strong> Rp <?php echo number_format($amount, 0, ',', '.'); ?></p>
                    <p><strong>Payment Date:</strong> <?php echo htmlspecialchars($payment_date); ?></p>
                    <p><strong>Method:</strong> <?php echo ucfirst(htmlspecialchars($payment_method)); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($description); ?></p>
                    <?php if($payment_proof): ?>
                    <p><strong>Payment Proof:</strong> <a href="../uploads/payments/<?php echo htmlspecialchars($payment_proof); ?>" target="_blank">View</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                        <option value="pending" <?php echo $status == "pending" ? "selected" : ""; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status == "confirmed" ? "selected" : ""; ?>>Confirm</option>
                        <option value="rejected" <?php echo $status == "rejected" ? "selected" : ""; ?>>Reject</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $status_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Payment">
                    <a href="payments.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>