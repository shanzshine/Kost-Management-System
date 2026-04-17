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
$type_filter = "";
$room_filter = "";

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(isset($_GET["search"]) && !empty(trim($_GET["search"]))){
        $search = trim($_GET["search"]);
    }
    
    if(isset($_GET["status"]) && !empty(trim($_GET["status"]))){
        $status_filter = trim($_GET["status"]);
    }
    
    if(isset($_GET["type"]) && !empty(trim($_GET["type"]))){
        $type_filter = trim($_GET["type"]);
    }
    
    if(isset($_GET["room"]) && !empty(trim($_GET["room"]))){
        $room_filter = trim($_GET["room"]);
    }
}

// Get maintenance requests with pagination and filters
$sql = "SELECT mr.*, r.room_number, res.full_name 
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        JOIN residents res ON mr.resident_id = res.id
        WHERE 1=1 ";

$params = [];
$types = "";

if(!empty($search)){
    $sql .= "AND (res.full_name LIKE ? OR mr.description LIKE ? OR r.room_number LIKE ?) ";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if(!empty($status_filter)){
    $sql .= "AND mr.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($type_filter)){
    $sql .= "AND mr.request_type = ? ";
    $params[] = $type_filter;
    $types .= "s";
}

if(!empty($room_filter)){
    $sql .= "AND mr.room_id = ? ";
    $params[] = $room_filter;
    $types .= "i";
}

$sql .= "ORDER BY mr.request_date DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);

if($params){
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$maintenance_requests = mysqli_stmt_get_result($stmt);

// Get total number of records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM maintenance_requests mr
              JOIN rooms r ON mr.room_id = r.id
              JOIN residents res ON mr.resident_id = res.id
              WHERE 1=1 ";

$count_params = [];
$count_types = "";

if(!empty($search)){
    $count_sql .= "AND (res.full_name LIKE ? OR mr.description LIKE ? OR r.room_number LIKE ?) ";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if(!empty($status_filter)){
    $count_sql .= "AND mr.status = ? ";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if(!empty($type_filter)){
    $count_sql .= "AND mr.request_type = ? ";
    $count_params[] = $type_filter;
    $count_types .= "s";
}

if(!empty($room_filter)){
    $count_sql .= "AND mr.room_id = ? ";
    $count_params[] = $room_filter;
    $count_types .= "i";
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
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <h1>Maintenance Requests</h1>
        <?php displayMessage(); ?>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Maintenance Requests List</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter and Search -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Name, Description..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == "pending" ? "selected" : ""; ?>>Pending</option>
                                <option value="in_progress" <?php echo $status_filter == "in_progress" ? "selected" : ""; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter == "completed" ? "selected" : ""; ?>>Completed</option>
                                <option value="rejected" <?php echo $status_filter == "rejected" ? "selected" : ""; ?>>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label for="type">Type</label>
                            <select name="type" id="type" class="form-control">
                                <option value="">All Types</option>
                                <option value="plumbing" <?php echo $type_filter == "plumbing" ? "selected" : ""; ?>>Plumbing</option>
                                <option value="electrical" <?php echo $type_filter == "electrical" ? "selected" : ""; ?>>Electrical</option>
                                <option value="furniture" <?php echo $type_filter == "furniture" ? "selected" : ""; ?>>Furniture</option>
                                <option value="cleaning" <?php echo $type_filter == "cleaning" ? "selected" : ""; ?>>Cleaning</option>
                                <option value="other" <?php echo $type_filter == "other" ? "selected" : ""; ?>>Other</option>
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
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="maintenance.php" class="btn btn-secondary ml-2">Reset</a>
                        </div>
                    </div>
                </form>
                
                <?php if(mysqli_num_rows($maintenance_requests) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Resident</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Resolved Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($maintenance_requests)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($row["request_type"])); ?></td>
                                <td><?php echo htmlspecialchars(substr($row["description"], 0, 50)) . (strlen($row["description"]) > 50 ? "..." : ""); ?></td>
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
                                <td>
                                    <a href="maintenance_update.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Update</a>
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
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn <?php echo $i == $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn">Next</a>
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