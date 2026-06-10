<?php
/**
 * REQUEST LEAVE PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

$current_user = getCurrentUser();
$error = '';
$success = '';

// Get leave types
$types_query = "SELECT id, leave_name, description, days_allowed FROM leave_types WHERE status = 1 ORDER BY leave_name";
$types_result = $conn->query($types_query);

// Get leave balance
$balance_query = "SELECT lt.id, lt.leave_name, lt.days_allowed,
                  COALESCE(SUM(CASE WHEN lr.status = 'Approved' THEN lr.number_of_days ELSE 0 END), 0) as used_days,
                  (lt.days_allowed - COALESCE(SUM(CASE WHEN lr.status = 'Approved' THEN lr.number_of_days ELSE 0 END), 0)) as remaining_days
                  FROM leave_types lt
                  LEFT JOIN leave_requests lr ON lt.id = lr.leave_type_id AND lr.employee_id = ? AND YEAR(lr.start_date) = YEAR(CURDATE())
                  WHERE lt.status = 1
                  GROUP BY lt.id, lt.leave_name, lt.days_allowed";

$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->bind_param("i", $current_user['id']);
$balance_stmt->execute();
$balance_result = $balance_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_id = isset($_POST['leave_type_id']) ? (int)$_POST['leave_type_id'] : 0;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $reason = isset($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '';
    
    // Validate
    if (!$leave_type_id || !$start_date || !$end_date) {
        $error = 'All fields are required';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'End date must be after start date';
    } else {
        // Calculate days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $number_of_days = $interval->days + 1;
        
        // Check leave type exists
        $check_query = "SELECT id, days_allowed FROM leave_types WHERE id = ? AND status = 1";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $leave_type_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $error = 'Invalid leave type';
        } else {
            $leave_type = $check_result->fetch_assoc();
            
            // Check for overlapping requests
            $overlap_query = "SELECT COUNT(*) as count FROM leave_requests 
                              WHERE employee_id = ? AND status != 'Rejected' 
                              AND ((start_date <= ? AND end_date >= ?) OR 
                                   (start_date <= ? AND end_date >= ?) OR
                                   (start_date >= ? AND end_date <= ?))";
            $overlap_stmt = $conn->prepare($overlap_query);
            $emp_id = $current_user['id'];
            $overlap_stmt->bind_param("issssss", $emp_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
            $overlap_stmt->execute();
            $overlap_result = $overlap_stmt->get_result();
            $overlap = $overlap_result->fetch_assoc();
            
            if ($overlap['count'] > 0) {
                $error = 'You already have a leave request during this period';
            } else {
                // Insert request
                $insert_query = "INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, number_of_days, reason, status)
                                 VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
                
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iissis", $emp_id, $leave_type_id, $start_date, $end_date, $number_of_days, $reason);
                
                if ($insert_stmt->execute()) {
                    $new_id = $insert_stmt->insert_id;
                    logAudit('Request Leave', 'Leave', $new_id, null, 
                        ['leave_type' => $leave_type_id, 'days' => $number_of_days, 'reason' => $reason]);
                    $success = 'Leave request submitted successfully!';
                    // Redirect after 2 seconds
                    header("refresh:2;url=my_requests.php");
                } else {
                    $error = 'Error submitting request';
                }
            }
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2><i class="fas fa-calendar-plus"></i> Request Leave</h2>
                        <p class="text-muted">Submit a leave request</p>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Leave Request Form -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-clipboard"></i> Leave Details</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="leaveRequestForm">
                                    <div class="mb-3">
                                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                                        <select name="leave_type_id" class="form-select" required id="leaveTypeSelect">
                                            <option value="">-- Select Leave Type --</option>
                                            <?php 
                                            $types_result->data_seek(0);
                                            while($type = $types_result->fetch_assoc()): 
                                            ?>
                                            <option value="<?php echo $type['id']; ?>" data-days="<?php echo $type['days_allowed']; ?>">
                                                <?php echo htmlspecialchars($type['leave_name'] . ' (' . $type['days_allowed'] . ' days)'); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <small class="text-muted">Select the type of leave you are requesting</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" name="start_date" class="form-control" required id="startDate">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                                            <input type="date" name="end_date" class="form-control" required id="endDate">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Reason for Leave</label>
                                        <textarea name="reason" class="form-control" rows="4" placeholder="Please provide a brief reason for your leave request"></textarea>
                                    </div>

                                    <div class="mb-3" id="calculatedDaysSection" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Number of Days:</strong> <span id="calculatedDays">0</span> days
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="my_requests.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back
                                        </a>
                                        <button type="reset" class="btn btn-outline-secondary">
                                            <i class="fas fa-redo"></i> Clear
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Leave Balance -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Leave Balance (2026)</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($balance_result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Leave Type</th>
                                                    <th class="text-center">Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($balance = $balance_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><small><?php echo htmlspecialchars($balance['leave_name']); ?></small></td>
                                                    <td class="text-center">
                                                        <?php
                                                        $remaining = $balance['remaining_days'];
                                                        $badge_class = $remaining > 5 ? 'success' : ($remaining > 0 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                                            <?php echo $remaining; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle"></i> No leave types available
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="card border-0 shadow-sm bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-lightbulb"></i> Tips</h6>
                                <ul class="small mb-0">
                                    <li>Submit your request at least 2 weeks in advance</li>
                                    <li>You can have only one active request per leave type</li>
                                    <li>Managers will review and notify you of approval</li>
                                    <li>You can cancel pending requests anytime</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Calculate days when dates change
    function calculateDays() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            $('#calculatedDays').text(diffDays);
            $('#calculatedDaysSection').show();
            
            if (diffDays < 0) {
                $('#leaveRequestForm').attr('data-invalid', 'true');
            } else {
                $('#leaveRequestForm').removeAttr('data-invalid');
            }
        }
    }
    
    $('#startDate, #endDate').on('change', calculateDays);
    
    // Validate form on submit
    $('#leaveRequestForm').on('submit', function(e) {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (!startDate || !endDate) {
            e.preventDefault();
            alert('Please select both start and end dates');
            return false;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            e.preventDefault();
            alert('End date must be after start date');
            return false;
        }
    });
});
</script>s