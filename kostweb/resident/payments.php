<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in, if not then redirect to login page
if(!isLoggedIn() || isAdmin()){
    header("location: ../login.php");
    exit;
}

// Define pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get payments with pagination
$sql = "SELECT p.*, r.room_number 
        FROM payments p
        JOIN rooms r ON p.room_id = r.id
        WHERE resident_id = ? 
        ORDER BY payment_date DESC
        LIMIT ?, ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "iii", $_SESSION["resident_id"], $offset, $records_per_page);
    
    if(mysqli_stmt_execute($stmt)){
        $payments = mysqli_stmt_get_result($stmt);
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get total number of records for pagination
$sql = "SELECT COUNT(*) as total FROM payments WHERE resident_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["resident_id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_records = $row['total'];
        $total_pages = ceil($total_records / $records_per_page);
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get room information
$room_price = 0;
if(isset($_SESSION["room_id"]) && !empty($_SESSION["room_id"])){
    $sql = "SELECT price FROM rooms WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["room_id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $room = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $room_price = $room["price"];
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "resident_navbar.php"; ?>
    
    <div class="container">
        <h1>Payments</h1>
        <?php displayMessage(); ?>
        
        <?php if(isset($_SESSION["room_id"]) && !empty($_SESSION["room_id"])): ?>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Make a Payment</span>
                </div>
            </div>
            <div class="card-body">
                <p>Your monthly rent is <strong>Rp <?php echo number_format($room_price, 0, ',', '.'); ?></strong>.</p>
                <a href="payment_new.php" class="btn btn-primary">Make a Payment</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <span>Payment History</span>
            </div>
            <div class="card-body">
                <?php if(isset($payments) && mysqli_num_rows($payments) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($payments)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td>Rp <?php echo number_format($row["amount"], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($row["payment_date"]); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($row["payment_method"])); ?></td>
                                <td><?php echo htmlspecialchars($row["description"]); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($row["status"]) {
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
                                    echo "<span class='{$status_class}'>" . ucfirst(htmlspecialchars($row["status"])) . "</span>";
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>" class="btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i == $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="btn">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <p>No payment records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>