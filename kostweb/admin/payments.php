<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in and is admin, if not then redirect to login page
if(!isLoggedIn() || !isAdmin()){
    header("location: ../login.php");
    exit;
}

// Define pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Define search variables
$search = "";
$status_filter = "";
$method_filter = "";
$room_filter = "";
$date_from = "";
$date_to = "";

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(isset($_GET["search"]) && !empty(trim($_GET["search"]))){
        $search = trim($_GET["search"]);
    }
    
    if(isset($_GET["status"]) && !empty(trim($_GET["status"]))){
        $status_filter = trim($_GET["status"]);
    }
    
    if(isset($_GET["method"]) && !empty(trim($_GET["method"]))){
        $method_filter = trim($_GET["method"]);
    }
    
    if(isset($_GET["room"]) && !empty(trim($_GET["room"]))){
        $room_filter = trim($_GET["room"]);
    }
    
    if(isset($_GET["date_from"]) && !empty(trim($_GET["date_from"]))){
        $date_from = trim($_GET["date_from"]);
    }
    
    if(isset($_GET["date_to"]) && !empty(trim($_GET["date_to"]))){
        $date_to = trim($_GET["date_to"]);
    }
}

// Get payments with pagination and filters
$sql = "SELECT p.*, r.room_number, res.full_name 
        FROM payments p
        JOIN rooms r ON p.room_id = r.id
        JOIN residents res ON p.resident_id = res.id
        WHERE 1=1 ";

$params = [];
$types = "";

if(!empty($search)){
    $sql .= "AND (res.full_name LIKE ? OR r.room_number LIKE ? OR p.description LIKE ?) ";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if(!empty($status_filter)){
    $sql .= "AND p.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($method_filter)){
    $sql .= "AND p.payment_method = ? ";
    $params[] = $method_filter;
    $types .= "s";
}

if(!empty($room_filter)){
    $sql .= "AND p.room_id = ? ";
    $params[] = $room_filter;
    $types .= "i";
}

if(!empty($date_from)){
    $sql .= "AND p.payment_date >= ? ";
    $params[] = $date_from;
    $types .= "s";
}

if(!empty($date_to)){
    $sql .= "AND p.payment_date <= ? ";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= "ORDER BY p.payment_date DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);

if($params){
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$payments = mysqli_stmt_get_result($stmt);

// Get total number of records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM payments p
              JOIN rooms r ON p.room_id = r.id
              JOIN residents res ON p.resident_id = res.id
              WHERE 1=1 ";

$count_params = [];
$count_types = "";

if(!empty($search)){
    $count_sql .= "AND (res.full_name LIKE ? OR r.room_number LIKE ? OR p.description LIKE ?) ";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if(!empty($status_filter)){
    $count_sql .= "AND p.status = ? ";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if(!empty($method_filter)){
    $count_sql .= "AND p.payment_method = ? ";
    $count_params[] = $method_filter;
    $count_types .= "s";
}

if(!empty($room_filter)){
    $count_sql .= "AND p.room_id = ? ";
    $count_params[] = $room_filter;
    $count_types .= "i";
}

if(!empty($date_from)){
    $count_sql .= "AND p.payment_date >= ? ";
    $count_params[] = $date_from;
    $count_types .= "s";
}

if(!empty($date_to)){
    $count_sql .= "AND p.payment_date <= ? ";
    $count_params[] = $date_to;
    $count_types .= "s";
}

$count_stmt = mysqli_prepare($conn, $count_sql);

if($count_params){
    mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$row = mysqli_fetch_assoc($count_result);
$total_records = $row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get rooms for filter
$rooms_sql = "SELECT id, room_number FROM rooms ORDER BY room_number ASC";
$rooms_result = mysqli_query($conn, $rooms_sql);

// Get total amount
$total_sql = "SELECT SUM(amount) as total FROM payments WHERE status = 'confirmed'";
$total_result = mysqli_query($conn, $total_sql);
$total_amount = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// Get total amount this month
$month_sql = "SELECT SUM(amount) as total FROM payments WHERE status = 'confirmed' AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())";
$month_result = mysqli_query($conn, $month_sql);
$month_amount = mysqli_fetch_assoc($month_result)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments Management - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <h1>Payments Management</h1>
        <?php displayMessage(); ?>
        
        <div class="dashboard-cards">
            <div class="card stat-card">
                <div class="stat-value">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Confirmed Payments</div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value">Rp <?php echo number_format($month_amount, 0, ',', '.'); ?></div>
                <div class="stat-label">This Month's Payments</div>
            </div>
            
            <?php if($status_filter == 'pending'): ?>
            <div class="card stat-card">
                <div class="stat-value"><?php echo $total_records; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Payments List</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter and Search -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Name, Room..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == "pending" ? "selected" : ""; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter == "confirmed" ? "selected" : ""; ?>>Confirmed</option>
                                <option value="rejected" <?php echo $status_filter == "rejected" ? "selected" : ""; ?>>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label for="method">Method</label>
                            <select name="method" id="method" class="form-control">
                                <option value="">All Methods</option>
                                <option value="cash" <?php echo $method_filter == "cash" ? "selected" : ""; ?>>Cash</option>
                                <option value="transfer" <?php echo $method_filter == "transfer" ? "selected" : ""; ?>>Transfer</option>
                                <option value="other" <?php echo $method_filter == "other" ? "selected" : ""; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="room">Room</label>
                            <select name="room" id="room" class="form-control">
                                <option value="">All Rooms</option>
                                <?php 
                                mysqli_data_seek($rooms_result, 0);
                                while($room = mysqli_fetch_assoc($rooms_result)): ?>
                                <option value="<?php echo $room['id']; ?>" <?php echo $room_filter == $room['id'] ? "selected" : ""; ?>>
                                    <?php echo $room['room_number']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-top: 10px;">
                        <div>
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div>
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="payments.php" class="btn btn-secondary ml-2">Reset</a>
                        </div>
                    </div>
                </form>
                
                <?php if(mysqli_num_rows($payments) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Resident</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Method</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($payments)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                <td>Rp <?php echo number_format($row["amount"], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($row["payment_date"]); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($row["payment_method"])); ?></td>
                                <td><?php echo htmlspecialchars(substr($row["description"], 0, 50)) . (strlen($row["description"]) > 50 ? "..." : ""); ?></td>
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
                                <td>
                                    <?php if($row["status"] == "pending"): ?>
                                    <a href="payment_update.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Update</a>
                                    <?php endif; ?>
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
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&method=<?php echo urlencode($method_filter); ?>&room=<?php echo urlencode($room_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&method=<?php echo urlencode($method_filter); ?>&room=<?php echo urlencode($room_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn <?php echo $i == $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&method=<?php echo urlencode($method_filter); ?>&room=<?php echo urlencode($room_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn">Next</a>
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