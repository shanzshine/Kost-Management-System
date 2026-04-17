<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['tenant_id'])) {
    header('Location: login.php');
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// Get tenant room information
$room_query = "SELECT r.id, r.room_number, r.price 
               FROM rooms r 
               JOIN tenants t ON r.id = t.room_id 
               WHERE t.id = ?";
$room_stmt = $conn->prepare($room_query);
$room_stmt->bind_param("i", $tenant_id);
$room_stmt->execute();
$room_result = $room_stmt->get_result();
$room = $room_result->fetch_assoc();

// Get payment history
$payments_query = "SELECT p.*, 
                  CASE 
                    WHEN p.status = 'pending' THEN 'Pending'
                    WHEN p.status = 'verified' THEN 'Verified'
                    WHEN p.status = 'rejected' THEN 'Rejected'
                  END as status_text
                  FROM payments p 
                  WHERE p.tenant_id = ? 
                  ORDER BY p.payment_date DESC";
$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bind_param("i", $tenant_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();

// Handle new payment submission
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_date = date('Y-m-d');
    $amount = $room['price'];
    $payment_month = $_POST['payment_month'];
    $payment_year = $_POST['payment_year'];
    
    // Check if payment for this month/year already exists
    $check_query = "SELECT * FROM payments WHERE tenant_id = ? AND payment_month = ? AND payment_year = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iss", $tenant_id, $payment_month, $payment_year);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "You already have a payment record for $payment_month $payment_year";
    } else {
        // Process file upload
        $target_dir = "payment_proofs/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES["payment_proof"]["name"]);
        $target_file = $target_dir . $file_name;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["payment_proof"]["tmp_name"]);
        if($check === false) {
            $error_message = "File is not an image.";
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["payment_proof"]["size"] > 5000000) { // 5MB max
            $error_message = "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error_message = "Sorry, only JPG, JPEG, PNG files are allowed.";
            $uploadOk = 0;
        }
        
        // If everything is ok, try to upload file and insert payment record
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
                // Insert payment record
                $insert_query = "INSERT INTO payments (tenant_id, room_id, amount, payment_date, payment_month, payment_year, payment_proof, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iidssss", $tenant_id, $room['id'], $amount, $payment_date, $payment_month, $payment_year, $file_name);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Payment record submitted successfully! Waiting for verification.";
                    
                    // Refresh payment history
                    $payments_stmt->execute();
                    $payments_result = $payments_stmt->get_result();
                } else {
                    $error_message = "Error submitting payment record: " . $conn->error;
                }
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        }
    }
}

// Get list of months
$months = array(
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
);

// Get current year and +2 years for dropdown
$current_year = date('Y');
$years = array($current_year - 1, $current_year, $current_year + 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Kost Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>Payment History</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>Submit New Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="room_number">Room Number</label>
                                <input type="text" class="form-control" value="<?php echo $room['room_number']; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" class="form-control" value="Rp <?php echo number_format($room['price'], 0, ',', '.'); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_month">Month</label>
                                <select name="payment_month" class="form-control" required>
                                    <?php foreach ($months as $month): ?>
                                        <option value="<?php echo $month; ?>" <?php echo (date('F') == $month) ? 'selected' : ''; ?>><?php echo $month; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_year">Year</label>
                                <select name="payment_year" class="form-control" required>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo (date('Y') == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_proof">Payment Proof (Image)</label>
                                <input type="file" class="form-control-file" name="payment_proof" id="payment_proof" required>
                                <small class="form-text text-muted">Upload receipt/transfer proof. Allowed formats: JPG, JPEG, PNG. Max size: 5MB.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">Submit Payment</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Payment History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($payments_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Month/Year</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Proof</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo $payment['payment_month'] . ' ' . $payment['payment_year']; ?></td>
                                                <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <?php if ($payment['status'] == 'pending'): ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php elseif ($payment['status'] == 'verified'): ?>
                                                        <span class="badge badge-success">Verified</span>
                                                    <?php elseif ($payment['status'] == 'rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="payment_proofs/<?php echo $payment['payment_proof']; ?>" target="_blank" class="btn btn-sm btn-info">View Proof</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No payment records found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>