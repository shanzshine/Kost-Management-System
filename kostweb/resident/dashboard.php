<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isLoggedIn() || isAdmin()){
    header("location: ../login.php");
    exit;
}

// Get resident info
$resident_id = $_SESSION["resident_id"];
$sql = "SELECT r.*, rm.room_number, rm.floor, rm.price 
        FROM residents r 
        LEFT JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $resident = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else{
            // Resident not found
            redirectWithMessage("../logout.php", "Resident information not found. Please contact administrator.");
        }
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get latest payment
$sql = "SELECT * FROM payments WHERE resident_id = ? ORDER BY payment_date DESC LIMIT 1";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $latest_payment = mysqli_fetch_array($result, MYSQLI_ASSOC);
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get maintenance requests
$sql = "SELECT * FROM maintenance_requests WHERE resident_id = ? ORDER BY request_date DESC LIMIT 5";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    
    if(mysqli_stmt_execute($stmt)){
        $maintenance_requests = mysqli_stmt_get_result($stmt);
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "resident_navbar.php"; ?>
    
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h1>
        <?php displayMessage(); ?>
        
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">Resident Information</div>
                <div class="card-body">
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($resident["full_name"]); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($resident["phone"]); ?></p>
                    <p><strong>ID Card:</strong> <?php echo htmlspecialchars($resident["id_card_number"]); ?></p>
                    <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($resident["emergency_contact"] ?? "Not provided"); ?></p>
                    <p><strong>Check-in Date:</strong> <?php echo htmlspecialchars($resident["check_in_date"] ?? "Not assigned"); ?></p>
                    <?php if($resident["check_out_date"]): ?>
                    <p><strong>Check-out Date:</strong> <?php echo htmlspecialchars($resident["check_out_date"]); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Room Information</div>
                <div class="card-body">
                    <?php if($resident["room_id"]): ?>
                    <p><strong>Room Number:</strong> <?php echo htmlspecialchars($resident["room_number"]); ?></p>
                    <p><strong>Floor:</strong> <?php echo htmlspecialchars($resident["floor"]); ?></p>
                    <p><strong>Monthly Rent:</strong> Rp <?php echo number_format($resident["price"], 0, ',', '.'); ?></p>
                    <?php else: ?>
                    <p>You have not been assigned a room yet. Please contact the administrator.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Payment Information</div>
                <div class="card-body">
                    <?php if(isset($latest_payment)): ?>
                    <p><strong>Last Payment:</strong> <?php echo htmlspecialchars($latest_payment["payment_date"]); ?></p>
                    <p><strong>Amount:</strong> Rp <?php echo number_format($latest_payment["amount"], 0, ',', '.'); ?></p>
                    <p><strong>Status:</strong> 
                        <?php 
                        $status_class = '';
                        switch($latest_payment["status"]) {
                            case 'confirmed':
                                $status_class = 'text-success';
                                break;
                            case 'pending':
                                $status_class = 'text-warning';
                                break;
                            case 'rejected':
                                $status_class = 'text-danger';
                                break;
                        }
                        echo "<span class='{$status_class}'>" . ucfirst(htmlspecialchars($latest_payment["status"])) . "</span>";
                        ?>
                    </p>
                    <?php else: ?>
                    <p>No payment records found.</p>
                    <?php endif; ?>
                    <p><a href="payments.php" class="btn btn-primary">View All Payments</a></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Recent Maintenance Requests</div>
            <div class="card-body">
                <?php if(mysqli_num_rows($maintenance_requests) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($maintenance_requests)): ?>
                            <tr>
                                <td><?php echo ucfirst(htmlspecialchars($row["request_type"])); ?></td>
                                <td><?php echo htmlspecialchars($row["description"]); ?></td>
                                <td><?php echo htmlspecialchars($row["request_date"]); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($row["status"]) {
                                        case 'completed':
                                            $status_class = 'text-success';
                                            break;
                                        case 'pending':
                                            $status_class = 'text-warning';
                                            break;
                                        case 'in_progress':
                                            $status_class = 'text-primary';
                                            break;
                                        case 'rejected':
                                            $status_class = 'text-danger';
                                            break;
                                    }
                                    echo "<span class='{$status_class}'>" . ucfirst(str_replace('_', ' ', htmlspecialchars($row["status"]))) . "</span>";
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p>No maintenance requests found.</p>
                <?php endif; ?>
                <p><a href="maintenance.php" class="btn btn-primary">View All Requests</a></p>
                <p><a href="maintenance_new.php" class="btn btn-success">Submit New Request</a></p>
            </div>
        </div>
    </div>
</body>
</html>