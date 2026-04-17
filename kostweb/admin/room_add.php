<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in and is admin, if not then redirect to login page
if(!isLoggedIn() || !isAdmin()){
    header("location: ../login.php");
    exit;
}

// Define variables and initialize with empty values
$room_number = $floor = $price = $capacity = $facilities = "";
$room_number_err = $floor_err = $price_err = $capacity_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate room number
    if(empty(trim($_POST["room_number"]))){
        $room_number_err = "Please enter a room number.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM rooms WHERE room_number = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_room_number);
            
            // Set parameters
            $param_room_number = trim($_POST["room_number"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $room_number_err = "This room number already exists.";
                } else{
                    $room_number = cleanInput($_POST["room_number"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate floor
    if(empty(trim($_POST["floor"]))){
        $floor_err = "Please enter the floor number.";
    } elseif(!is_numeric($_POST["floor"])){
        $floor_err = "Floor must be a number.";
    } else{
        $floor = trim($_POST["floor"]);
    }
    
    // Validate price
    if(empty(trim($_POST["price"]))){
        $price_err = "Please enter the room price.";
    } elseif(!is_numeric($_POST["price"])){
        $price_err = "Price must be a number.";
    } else{
        $price = trim($_POST["price"]);
    }
    
    
    // Get facilities
    $facilities = cleanInput($_POST["facilities"]);
    
    // Check input errors before inserting in database
    if(empty($room_number_err) && empty($floor_err) && empty($price_err) && empty($capacity_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO rooms (room_number, floor, price, facilities, status) VALUES (?, ?, ?, ?, 'available')";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sids", $param_room_number, $param_floor, $param_price, $param_facilities);
            
            // Set parameters
            $param_room_number = $room_number;
            $param_floor = $floor;
            $param_price = $price;
            $param_facilities = $facilities;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to rooms page
                redirectWithMessage("rooms.php", "Room added successfully.", "success");
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
    <title>Add New Room - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Add New Room</h2>
            <p>Please fill this form to create a new room.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" class="form-control <?php echo (!empty($room_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $room_number; ?>">
                    <span class="invalid-feedback"><?php echo $room_number_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Floor</label>
                    <input type="number" name="floor" class="form-control <?php echo (!empty($floor_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $floor; ?>">
                    <span class="invalid-feedback"><?php echo $floor_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Price (Rp)</label>
                    <input type="number" name="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price; ?>">
                    <span class="invalid-feedback"><?php echo $price_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Facilities</label>
                    <textarea name="facilities" class="form-control" rows="3"><?php echo $facilities; ?></textarea>
                    <small>E.g., "Air conditioner, Private bathroom, Bed, Desk, Wardrobe"</small>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Add Room">
                    <a href="rooms.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>