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

// Get maintenance requests with pagination
$sql = "SELECT mr.*, r.room_number 
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        WHERE resident_id = ? 
        ORDER BY request_date DESC
        LIMIT ?, ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "iii", $_SESSION["resident_id"], $offset, $records_per_page);
    
    if(mysqli_stmt_execute($stmt)){
        $maintenance_requests = mysqli_stmt_get_result($stmt);
    } else{
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    mysqli_stmt_close($stmt);
}

// Get total number of records for pagination
$sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE resident_id = ?";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Requests - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "resident_navbar.php"; ?>
    
    <div class="container">
        <h1>Maintenance Requests</h1>
        <?php displayMessage(); ?>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Your Maintenance Requests</span>
                    <a href="maintenance_new.php" class="btn btn-success">New Request</a>
                </div>
            </div>
            <div class="card-body">
                <?php if(mysqli_num_rows($maintenance_requests) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Resolved Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($maintenance_requests)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
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
                                <td><?php echo $row["resolved_date"] ? htmlspecialchars($row["resolved_date"]) : "N/A"; ?></td>
                                <td><?php echo $row["notes"] ? htmlspecialchars($row["notes"]) : "N/A"; ?></td>
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
                <p>No maintenance requests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>