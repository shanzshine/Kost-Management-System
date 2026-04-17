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
    $sql = "SELECT * FROM rooms WHERE id = ?";
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
                $room_number = $row["room_number"];
                $floor = $row["floor"];
                $price = $row["price"];
                $capacity = $row["capacity"];
                $facilities = $row["facilities"];
                $status = $row["status"];
                
                // Check if room has occupants
                $sql_check = "SELECT COUNT(*) as occupants FROM residents WHERE room_id = ? AND status = 'active'";
                $stmt_check = mysqli_prepare($conn, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "i", $id);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                $row_check = mysqli_fetch_assoc($result_check);
                $has_occupants = ($row_check['occupants'] > 0);
                
            } else{
                // URL doesn't contain valid id parameter
                redirectWithMessage("rooms.php", "Invalid room ID.");
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
    redirectWithMessage("rooms.php", "Room ID is required.");
    exit();
}

// Define error variables
$room_number_err = $floor_err = $price_err = $capacity_err = $status_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate room number
    if(empty(trim($_POST["room_number"]))){
        $room_number_err = "Please enter a room number.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM rooms WHERE room_number = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_room_number, $param_id);
            
            // Set parameters
            $param_room_number = trim($_POST["room_number"]);
            $param_id = $id;
            
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
    
    // Validate capacity
    if(empty(trim($_POST["capacity"]))){
        $capacity_err = "Please enter the room capacity.";
    } elseif(!is_numeric($_POST["capacity"]) || $_POST["capacity"] < 1){
        $capacity_err = "Capacity must be a positive number.";
    } else{
        $capacity = trim($_POST["capacity"]);
    }
    
    // Validate status
    if(empty(trim($_POST["status"]))){
        $status_err = "Please select the room status.";
    } else{
        // Check if room has occupants and trying to set as available
        if($has_occupants && $_POST["status"] == "available"){
            $status_err = "Cannot set room as available while it has occupants.";
        } else {
            $status = cleanInput($_POST["status"]);
        }
    }
    
    // Get facilities
    $facilities = cleanInput($_POST["facilities"]);
    
    // Check input errors before updating in database
    if(empty($room_number_err) && empty($floor_err) && empty($price_err) && empty($capacity_err) && empty($status_err)){
        // Prepare an update statement
        $sql = "UPDATE rooms SET room_number = ?, floor = ?, price = ?, capacity = ?, facilities = ?, status = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sidissi", $param_room_number, $param_floor, $param_price, $param_capacity, $param_facilities, $param_status, $param_id);
            
            // Set parameters
            $param_room_number = $room_number;
            $param_floor = $floor;
            $param_price = $price;
            $param_capacity = $capacity;
            $param_facilities = $facilities;
            $param_status = $status;
            $param_id = $id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to rooms page
                redirectWithMessage("rooms.php", "Room updated successfully.", "success");
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
    <title>Edit Room - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Edit Room</h2>
            <p>Please edit the room information below.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post">
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
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control <?php echo (!empty($capacity_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $capacity; ?>" min="1">
                    <span class="invalid-feedback"><?php echo $capacity_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Facilities</label>
                    <textarea name="facilities" class="form-control" rows="3"><?php echo $facilities; ?></textarea>
                    <small>E.g., "Air conditioner, Private bathroom, Bed, Desk, Wardrobe"</small>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                        <option value="available" <?php echo $status == "available" ? "selected" : ""; ?>>Available</option>
                        <option value="occupied" <?php echo $status == "occupied" ? "selected" : ""; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $status == "maintenance" ? "selected" : ""; ?>>Maintenance</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $status_err; ?></span>
                    <?php if($has_occupants): ?>
                    <small class="text-warning">Note: This room currently has occupants. It cannot be set to 'Available' status.</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Room">
                    <a href="rooms.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>