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
    $sql = "SELECT r.*, u.username, u.email 
            FROM residents r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?";
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
                $user_id = $row["user_id"];
                $room_id = $row["room_id"];
                $full_name = $row["full_name"];
                $phone = $row["phone"];
                $emergency_contact = $row["emergency_contact"];
                $id_card_number = $row["id_card_number"];
                $check_in_date = $row["check_in_date"];
                $check_out_date = $row["check_out_date"];
                $status = $row["status"];
                $username = $row["username"];
                $email = $row["email"];
                
            } else{
                // URL doesn't contain valid id parameter
                redirectWithMessage("residents.php", "Invalid resident ID.");
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
    redirectWithMessage("residents.php", "Resident ID is required.");
    exit();
}

// Define error variables
$full_name_err = $phone_err = $id_card_err = $room_id_err = $check_in_date_err = $check_out_date_err = $status_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter a full name.";
    } else{
        $full_name = cleanInput($_POST["full_name"]);
    }
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter a phone number.";
    } else{
        $phone = cleanInput($_POST["phone"]);
    }
    
    // Validate ID card
    if(empty(trim($_POST["id_card_number"]))){
        $id_card_err = "Please enter an ID card number.";
    } else{
        $id_card_number = cleanInput($_POST["id_card_number"]);
    }
    
    // Get emergency contact
    $emergency_contact = cleanInput($_POST["emergency_contact"]);
    
    // Validate room
    if(isset($_POST["room_id"]) && !empty($_POST["room_id"])){
        // Check if room is available or already assigned to this resident
        $sql = "SELECT status FROM rooms WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $_POST["room_id"]);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $room = mysqli_fetch_assoc($result);
                if($room["status"] == "available" || $_POST["room_id"] == $room_id){
                    $room_id = $_POST["room_id"];
                } else {
                    $room_id_err = "This room is not available.";
                }
            } else {
                $room_id_err = "Invalid room ID.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $room_id = NULL;
    }
    
    // Validate check-in date
    if(isset($_POST["check_in_date"]) && !empty($_POST["check_in_date"])){
        $check_in_date = $_POST["check_in_date"];
    } else {
        $check_in_date = NULL;
    }
    
    // Validate check-out date
    if(isset($_POST["check_out_date"]) && !empty($_POST["check_out_date"])){
        if($check_in_date && $_POST["check_out_date"] < $check_in_date){
            $check_out_date_err = "Check-out date cannot be earlier than check-in date.";
        } else {
            $check_out_date = $_POST["check_out_date"];
        }
    } else {
        $check_out_date = NULL;
    }
    
    // Validate status
    if(empty(trim($_POST["status"]))){
        $status_err = "Please select a status.";
    } else{
        $status = cleanInput($_POST["status"]);
    }
    
    // Check input errors before updating in database
    if(empty($full_name_err) && empty($phone_err) && empty($id_card_err) && empty($room_id_err) && empty($check_in_date_err) && empty($check_out_date_err) && empty($status_err)){
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Update residents table
            $sql = "UPDATE residents SET full_name = ?, phone = ?, emergency_contact = ?, id_card_number = ?, room_id = ?, check_in_date = ?, check_out_date = ?, status = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssssisssi", $param_full_name, $param_phone, $param_emergency_contact, $param_id_card, $param_room_id, $param_check_in, $param_check_out, $param_status, $param_id);
                
                // Set parameters
                $param_full_name = $full_name;
                $param_phone = $phone;
                $param_emergency_contact = $emergency_contact;
                $param_id_card = $id_card_number;
                $param_room_id = $room_id;
                $param_check_in = $check_in_date;
                $param_check_out = $check_out_date;
                $param_status = $status;
                $param_id = $id;
                
                // Attempt to execute the prepared statement
                if(!mysqli_stmt_execute($stmt)){
                    throw new Exception("Error updating resident information.");
                }
                
                // Update room status
                if($room_id){
                    // If assigning a new room, update its status
                    if($room_id != $row["room_id"]){
                        // Set the new room as occupied
                        $sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $room_id);
                        if(!mysqli_stmt_execute($stmt)){
                            throw new Exception("Error updating new room status.");
                        }
                        
                        // If the resident was previously assigned a room, check if it should be set as available
                        if($row["room_id"]){
                            // Check if any other active residents are using the old room
                            $sql = "SELECT COUNT(*) as count FROM residents WHERE room_id = ? AND id != ? AND status = 'active'";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "ii", $row["room_id"], $id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $count = mysqli_fetch_assoc($result)['count'];
                            
                            // If no other residents are using the old room, set it as available
                            if($count == 0){
                                $sql = "UPDATE rooms SET status = 'available' WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "i", $row["room_id"]);
                                if(!mysqli_stmt_execute($stmt)){
                                    throw new Exception("Error updating old room status.");
                                }
                            }
                        }
                    }
                } else if($row["room_id"]){
                    // If removing room assignment, check if the old room should be set as available
                    $sql = "SELECT COUNT(*) as count FROM residents WHERE room_id = ? AND id != ? AND status = 'active'";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $row["room_id"], $id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $count = mysqli_fetch_assoc($result)['count'];
                    
                    // If no other residents are using the old room, set it as available
                    if($count == 0){
                        $sql = "UPDATE rooms SET status = 'available' WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $row["room_id"]);
                        if(!mysqli_stmt_execute($stmt)){
                            throw new Exception("Error updating old room status.");
                        }
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Redirect to residents page
                redirectWithMessage("residents.php", "Resident updated successfully.", "success");
            }
        } catch (Exception $e) {
            // Roll back transaction
            mysqli_rollback($conn);
            echo "Error: " . $e->getMessage();
        }
    }
    
    // Close connection
    mysqli_close($conn);
}

// Get available rooms
$sql = "SELECT id, room_number, status FROM rooms WHERE status = 'available'";
if($room_id){
    $sql .= " OR id = ?";
}
$sql .= " ORDER BY room_number ASC";

if($stmt = mysqli_prepare($conn, $sql)){
    if($room_id){
        mysqli_stmt_bind_param($stmt, "i", $room_id);
    }
    mysqli_stmt_execute($stmt);
    $available_rooms = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Resident - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <div class="form-container">
            <h2>Edit Resident</h2>
            <p>Please edit the resident information below.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                    <small>Username cannot be changed.</small>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                    <small>Email cannot be changed.</small>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($full_name); ?>">
                    <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone); ?>">
                    <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                </div>
                <div class="form-group">
                    <label>ID Card Number</label>
                    <input type="text" name="id_card_number" class="form-control <?php echo (!empty($id_card_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($id_card_number); ?>">
                    <span class="invalid-feedback"><?php echo $id_card_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Emergency Contact (Optional)</label>
                    <input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($emergency_contact ?? ""); ?>">
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select name="room_id" class="form-control <?php echo (!empty($room_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">-- Not Assigned --</option>
                        <?php while($room = mysqli_fetch_array($available_rooms)): ?>
                        <option value="<?php echo $room['id']; ?>" <?php echo ($room_id == $room['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($room['room_number']) . ($room['status'] == 'occupied' ? ' (Currently Assigned)' : ''); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $room_id_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Check-in Date</label>
                    <input type="date" name="check_in_date" class="form-control <?php echo (!empty($check_in_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($check_in_date ?? ""); ?>">
                    <span class="invalid-feedback"><?php echo $check_in_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Check-out Date</label>
                    <input type="date" name="check_out_date" class="form-control <?php echo (!empty($check_out_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($check_out_date ?? ""); ?>">
                    <span class="invalid-feedback"><?php echo $check_out_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                        <option value="active" <?php echo $status == "active" ? "selected" : ""; ?>>Active</option>
                        <option value="inactive" <?php echo $status == "inactive" ? "selected" : ""; ?>>Inactive</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $status_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Resident">
                    <a href="residents.php" class="btn btn-secondary ml-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>