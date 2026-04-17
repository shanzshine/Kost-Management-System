<?php
// Include config file
require_once "../config.php";

// Check if the user is logged in and is admin, if not then redirect to login page
if(!isLoggedIn() || !isAdmin()){
    header("location: ../login.php");
    exit;
}

// Get statistics
// Count total rooms
$sql = "SELECT COUNT(*) as total FROM rooms";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_rooms = $row['total'];

// Count occupied rooms
$sql = "SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$occupied_rooms = $row['total'];

// Count available rooms
$sql = "SELECT COUNT(*) as total FROM rooms WHERE status = 'available'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$available_rooms = $row['total'];

// Count total residents
$sql = "SELECT COUNT(*) as total FROM residents WHERE status = 'active'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_residents = $row['total'];

// Count pending payments
$sql = "SELECT COUNT(*) as total FROM payments WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$pending_payments = $row['total'];

// Count pending maintenance requests
$sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$pending_maintenance = $row['total'];

// Get latest maintenance requests
$sql = "SELECT mr.*, r.room_number, res.full_name 
        FROM maintenance_requests mr
        JOIN rooms r ON mr.room_id = r.id
        JOIN residents res ON mr.resident_id = res.id
        ORDER BY request_date DESC LIMIT 5";
$maintenance_requests = mysqli_query($conn, $sql);

// Get latest payments
$sql = "SELECT p.*, r.room_number, res.full_name 
        FROM payments p
        JOIN rooms r ON p.room_id = r.id
        JOIN residents res ON p.resident_id = res.id
        ORDER BY payment_date DESC LIMIT 5";
$payments = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Kost Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php include "admin_navbar.php"; ?>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        <?php displayMessage(); ?>
        
        <div class="dashboard-cards">
            <div class="card stat-card">
                <div class="stat-value"><?php echo $total_rooms; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value"><?php echo $occupied_rooms; ?></div>
                <div class="stat-label">Occupied Rooms</div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value"><?php echo $available_rooms; ?></div>
                <div class="stat-label">Available Rooms</div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value"><?php echo $total_residents; ?></div>
                <div class="stat-label">Active Residents</div>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value"><?php echo $pending_payments; ?></div>
                <div class="stat-label">Pending Payments</div>
                <?php if($pending_payments > 0): ?>
                <div style="margin-top: 10px;">
                    <a href="payments.php?status=pending" class="btn btn-primary">View</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card stat-card">
                <div class="stat-value"><?php echo $pending_maintenance; ?></div>
                <div class="stat-label">Pending Maintenance</div>
                <?php if($pending_maintenance > 0): ?>
                <div style="margin-top: 10px;">
                    <a href="maintenance.php?status=pending" class="btn btn-primary">View</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="card">
                <div class="card-header">Recent Maintenance Requests</div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($maintenance_requests) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_array($maintenance_requests)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($row["request_type"])); ?></td>
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
                    <p style="margin-top: 10px;"><a href="maintenance.php" class="btn btn-primary">View All</a></p>
                    <?php else: ?>
                    <p>No maintenance requests found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Recent Payments</div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($payments) > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Resident</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_array($payments)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                    <td>Rp <?php echo number_format($row["amount"], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row["payment_date"]); ?></td>
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
                    <p style="margin-top: 10px;"><a href="payments.php" class="btn btn-primary">View All</a></p>
                    <?php else: ?>
                    <p>No payment records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>