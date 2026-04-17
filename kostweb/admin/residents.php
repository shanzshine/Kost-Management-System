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
$room_filter = "";

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(isset($_GET["search"]) && !empty(trim($_GET["search"]))){
        $search = trim($_GET["search"]);
    }
    
    if(isset($_GET["status"]) && !empty(trim($_GET["status"]))){
        $status_filter = trim($_GET["status"]);
    }
    
    if(isset($_GET["room"]) && !empty(trim($_GET["room"]))){
        $room_filter = trim($_GET["room"]);
    }
}

// Get residents with pagination and filters
$sql = "SELECT r.*, rm.room_number, u.username, u.email 
        FROM residents r 
        LEFT JOIN rooms rm ON r.room_id = rm.id
        JOIN users u ON r.user_id = u.id
        WHERE 1=1 ";

$params = [];
$types = "";

if(!empty($search)){
    $sql .= "AND (r.full_name LIKE ? OR r.phone LIKE ? OR r.id_card_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sssss";
}

if(!empty($status_filter)){
    $sql .= "AND r.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if(!empty($room_filter)){
    $sql .= "AND r.room_id = ? ";
    $params[] = $room_filter;
    $types .= "i";
}

$sql .= "ORDER BY r.full_name ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);

if($params){
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$residents = mysqli_stmt_get_result($stmt);

// Get total number of records for pagination
$count_sql = "SELECT COUNT(*) as total FROM residents r 
              LEFT JOIN rooms rm ON r.room_id = rm.id
              JOIN users u ON r.user_id = u.id
              WHERE 1=1 ";

$count_params = [];
$count_types = "";

if(!empty($search)){
    $count_sql .= "AND (r.full_name LIKE ? OR r.phone LIKE ? OR r.id_card_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?) ";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sssss";
}

if(!empty($status_filter)){
    $count_sql .= "AND r.status = ? ";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if(!empty($room_filter)){
    $count_sql .= "AND r.room_id = ? ";
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
    <title>Residents Management - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <h1>Residents Management</h1>
        <?php displayMessage(); ?>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Residents List</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter and Search -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Name, Phone, ID..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == "active" ? "selected" : ""; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == "inactive" ? "selected" : ""; ?>>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label for="room">Room</label>
                            <select name="room" id="room" class="form-control">
                                <option value="">All Rooms</option>
                                <option value="NULL" <?php echo $room_filter === "NULL" ? "selected" : ""; ?>>Not Assigned</option>
                                <?php while($room = mysqli_fetch_assoc($rooms_result)): ?>
                                <option value="<?php echo $room['id']; ?>" <?php echo $room_filter == $room['id'] ? "selected" : ""; ?>>
                                    <?php echo $room['room_number']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="residents.php" class="btn btn-secondary ml-2">Reset</a>
                        </div>
                    </div>
                </form>
                
                <?php if(mysqli_num_rows($residents) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($residents)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["id"]); ?></td>
                                <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                <td><?php echo htmlspecialchars($row["username"]); ?></td>
                                <td><?php echo htmlspecialchars($row["email"]); ?></td>
                                <td><?php echo htmlspecialchars($row["phone"]); ?></td>
                                <td><?php echo $row["room_number"] ? htmlspecialchars($row["room_number"]) : "Not Assigned"; ?></td>
                                <td><?php echo $row["check_in_date"] ? htmlspecialchars($row["check_in_date"]) : "N/A"; ?></td>
                                <td><?php echo $row["check_out_date"] ? htmlspecialchars($row["check_out_date"]) : "N/A"; ?></td>
                                <td>
                                    <?php 
                                    $status_class = $row["status"] == "active" ? "text-success" : "text-danger";
                                    echo "<span class='{$status_class}'>" . ucfirst(htmlspecialchars($row["status"])) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <a href="resident_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
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
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn <?php echo $i == $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&room=<?php echo urlencode($room_filter); ?>" class="btn">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <p>No residents found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>