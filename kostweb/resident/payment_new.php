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
    redirectWithMessage("dashboard.php", "You need to be assigned a room before submitting payments.");
}

// Get room information
$room_price = 0;
$room_number = "";
if(isset($_SESSION["room_id"]) && !empty($_SESSION["room_id"])){
    $sql = "SELECT id, room_number, price FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["room_id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $room = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $room_price = $room["price"];
                $room_number = $room["room_number"];
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Define variables and initialize with empty values
$amount = $room_price;
$payment_date = date("Y-m-d");
$payment_method = "";
$description = "Monthly rent for Room $room_number";
$amount_err = $payment_date_err = $payment_method_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate amount
    if(empty(trim($_POST["amount"]))){
        $amount_err = "Please enter the payment amount.";
    } elseif(!is_numeric($_POST["amount"])){
        $amount_err = "Please enter a valid amount.";
    } else{
        $amount = trim($_POST["amount"]);
    }
    
    // Validate payment date
    if(empty(trim($_POST["payment_date"]))){
        $payment_date_err = "Please enter the payment date.";
    } else{
        $payment_date = trim($_POST["payment_date"]);
    }
    
    // Validate payment method
    if(empty(trim($_POST["payment_method"]))){
        $payment_method_err = "Please select a payment method.";
    } else{
        $payment_method = trim($_POST["payment_method"]);
    }
    
    // Get description
    $description = cleanInput($_POST["description"]);
    
    // Check input errors before inserting in database
    if(empty($amount_err) && empty($payment_date_err) && empty($payment_method_err)){
        // Prepare an insert statement
// Prepare an insert statement
$sql = "INSERT INTO payments (resident_id, room_id, amount, payment_date, payment_method, description, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    // Update the string definition to match the 6 parameters (not 7)
    mysqli_stmt_bind_param($stmt, "iidsss", $param_resident_id, $param_room_id, $param_amount, $param_payment_date, $param_payment_method, $param_description);
    
    // Set parameters
    $param_resident_id = $_SESSION["resident_id"];
    $param_room_id = $_SESSION["room_id"];
    $param_amount = $amount;
    $param_payment_date = $payment_date;
    $param_payment_method = $payment_method;
    $param_description = $description;
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        redirectWithMessage("payments.php", "Payment submitted successfully. It will be reviewed by the admin.", "success");
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
    <title>Submit Payment - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "resident_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Submit Payment</h2>
            <?php displayMessage(); ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Room</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($room_number); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Amount (Rp)</label>
                    <input type="number" name="amount" class="form-control <?php echo (!empty($amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $amount; ?>">
                    <span class="invalid-feedback"><?php echo $amount_err; ?></span>
                    <small>Default amount is your monthly rent.</small>
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control <?php echo (!empty($payment_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $payment_date; ?>">
                    <span class="invalid-feedback"><?php echo $payment_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control <?php echo (!empty($payment_method_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo $payment_method == "" ? 'selected' : ''; ?>>Select payment method</option>
                        <option value="cash" <?php echo $payment_method == "cash" ? 'selected' : ''; ?>>Cash</option>
                        <option value="transfer" <?php echo $payment_method == "transfer" ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="other" <?php echo $payment_method == "other" ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $payment_method_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $description; ?></textarea>
                    <small>E.g., "Monthly rent for April 2023"</small>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit Payment">
                    <a href="payments.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>