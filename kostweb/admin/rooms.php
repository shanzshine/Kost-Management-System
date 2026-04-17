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
$floor_filter = "";

if($_SERVER["REQUEST_METHOD"] == "GET"){
    if(isset($_GET["search"]) && !empty(trim($_GET["search"]))){
        $search = trim($_GET["search"]);
    }
    
    if(isset($_GET["status"]) && !empty(trim($_GET["status"]))){
        $status_filter = trim($_GET["status"]);
    }
    
    if(isset($_GET["floor"]) && trim($_GET["floor"]) != ""){
        $floor_filter = trim($_GET["floor"]);
    }
}

// Get rooms with pagination and filters
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM residents WHERE room_id = r.id AND status = 'active') as occupants
        FROM rooms r 
        WHERE 1=1 ";

$params = [];
$types = "";

if(!empty($search)){
    $sql .= "AND room_number LIKE ? ";
    $params[] = "%$search%";
    $types .= "s";
}

if(!empty($status_filter)){
    $sql .= "AND status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if($floor_filter !== ""){
    $sql .= "AND floor = ? ";
    $params[] = $floor_filter;
    $types .= "i";
}

$sql .= "ORDER BY room_number ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);

if($params){
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$rooms = mysqli_stmt_get_result($stmt);

// Get total number of records for pagination
$count_sql = "SELECT COUNT(*) as total FROM rooms WHERE 1=1 ";

$count_params = [];
$count_types = "";

if(!empty($search)){
    $count_sql .= "AND room_number LIKE ? ";
    $count_params[] = "%$search%";
    $count_types .= "s";
}

if(!empty($status_filter)){
    $count_sql .= "AND status = ? ";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if($floor_filter !== ""){
    $count_sql .= "AND floor = ? ";
    $count_params[] = $floor_filter;
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

// Get floors for filter
$floors_sql = "SELECT DISTINCT floor FROM rooms ORDER BY floor ASC";
$floors_result = mysqli_query($conn, $floors_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rooms Management - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <h1>Rooms Management</h1>
        <?php displayMessage(); ?>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Rooms List</span>
                    <a href="room_add.php" class="btn btn-success">Add New Room</a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter and Search -->
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                        <div>
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Room number..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="available" <?php echo $status_filter == "available" ? "selected" : ""; ?>>Available</option>
                                <option value="occupied" <?php echo $status_filter == "occupied" ? "selected" : ""; ?>>Occupied</option>
                                <option value="maintenance" <?php echo $status_filter == "maintenance" ? "selected" : ""; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div>
                            <label for="floor">Floor</label>
                            <select name="floor" id="floor" class="form-control">
                                <option value="">All Floors</option>
                                <?php while($floor = mysqli_fetch_assoc($floors_result)): ?>
                                <option value="<?php echo $floor['floor']; ?>" <?php echo $floor_filter == $floor['floor'] ? "selected" : ""; ?>>
                                    <?php echo $floor['floor']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="rooms.php" class="btn btn-secondary ml-2">Reset</a>
                        </div>
                    </div>
                </form>
                
                <?php if(mysqli_num_rows($rooms) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Floor</th>
                                <th>Price</th>
                                <th>Occupants</th>
                                <th>Status</th>
                                <th>Facilities</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($rooms)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td><?php echo htmlspecialchars($row["floor"]); ?></td>
                                <td>Rp <?php echo number_format($row["price"], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($row["occupants"]); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($row["status"]) {
                                        case 'available':
                                            $status_class = 'text-success';
                                            break;
                                        case 'occupied':
                                            $status_class = 'text-primary';
                                            break;
                                        case 'maintenance':
                                            $status_class = 'text-danger';
                                            break;
                                    }
                                    echo "<span class='{$status_class}'>" . ucfirst(htmlspecialchars($row["status"])) . "</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row["facilities"] ?? "N/A"); ?></td>
                                <td>
                                    <a href="room_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
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
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&floor=<?php echo urlencode($floor_filter); ?>" class="btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&floor=<?php echo urlencode($floor_filter); ?>" class="btn <?php echo $i == $page ? 'btn-primary' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&floor=<?php echo urlencode($floor_filter); ?>" class="btn">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <p>No rooms found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>