<?php
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = $full_name = $phone = $id_card = $emergency_contact = "";
$username_err = $password_err = $confirm_password_err = $email_err = $full_name_err = $phone_err = $id_card_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = cleanInput($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Invalid email format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = cleanInput($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter your full name.";     
    } else{
        $full_name = cleanInput($_POST["full_name"]);
    }
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter your phone number.";     
    } else{
        $phone = cleanInput($_POST["phone"]);
    }
    
    // Validate ID card
    if(empty(trim($_POST["id_card"]))){
        $id_card_err = "Please enter your ID card number.";     
    } else{
        $id_card = cleanInput($_POST["id_card"]);
    }
    
    // Get emergency contact
    $emergency_contact = cleanInput($_POST["emergency_contact"]);
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($email_err) && empty($full_name_err) && empty($phone_err) && empty($id_card_err)){
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Prepare an insert statement for users table
            $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'resident')";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_email);
                
                // Set parameters
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_email = $email;
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Get the user_id
                    $user_id = mysqli_insert_id($conn);
                    
                    // Prepare an insert statement for residents table
                    $sql = "INSERT INTO residents (user_id, full_name, phone, emergency_contact, id_card_number) VALUES (?, ?, ?, ?, ?)";
                    
                    if($stmt_resident = mysqli_prepare($conn, $sql)){
                        // Bind variables to the prepared statement as parameters
                        mysqli_stmt_bind_param($stmt_resident, "issss", $param_user_id, $param_full_name, $param_phone, $param_emergency_contact, $param_id_card);
                        
                        // Set parameters
                        $param_user_id = $user_id;
                        $param_full_name = $full_name;
                        $param_phone = $phone;
                        $param_emergency_contact = $emergency_contact;
                        $param_id_card = $id_card;
                        
                        // Attempt to execute the prepared statement
                        if(mysqli_stmt_execute($stmt_resident)){
                            // Commit transaction
                            mysqli_commit($conn);
                            
                            // Redirect to login page
                            redirectWithMessage("login.php", "Registration successful! You can now login.", "success");
                        } else{
                            // Roll back transaction
                            mysqli_rollback($conn);
                            echo "Something went wrong with resident registration. Please try again later.";
                        }
                        
                        // Close statement
                        mysqli_stmt_close($stmt_resident);
                    }
                } else{
                    // Roll back transaction
                    mysqli_rollback($conn);
                    echo "Something went wrong with user registration. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
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
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Kost Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Register</h2>
            <p>Please fill this form to create an account.</p>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                    <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone; ?>">
                    <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                </div>
                <div class="form-group">
                    <label>ID Card Number</label>
                    <input type="text" name="id_card" class="form-control <?php echo (!empty($id_card_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $id_card; ?>">
                    <span class="invalid-feedback"><?php echo $id_card_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Emergency Contact (Optional)</label>
                    <input type="text" name="emergency_contact" class="form-control" value="<?php echo $emergency_contact; ?>">
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <input type="reset" class="btn btn-secondary ml-2" value="Reset">
                </div>
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </form>
        </div>
    </div>    
</body>
</html>