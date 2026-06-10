<?php
/**
 * SYSTEM DASHBOARD - ENHANCED
 * HRGetafe - Human Resources Information System
 */

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$current_user = getCurrentUser();

// Get dashboard statistics
$stats = [];

// Total employees
$emp_query = "SELECT COUNT(*) as total FROM employees WHERE status = 1";
$stats['total_employees'] = $conn->query($emp_query)->fetch_assoc()['total'];

// Today's attendance
$today = date('Y-m-d');
$att_query = "SELECT 
              COUNT(DISTINCT employee_id) as present,
              SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
              FROM attendance WHERE date_attendance = '$today'";
$att_result = $conn->query($att_query)->fetch_assoc();
$stats['present_today'] = $att_result['present'];
$stats['absent_today'] = $att_result['absent'];

// Pending leave requests
$leave_query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'Pending'";
$stats['pending_leaves'] = $conn->query($leave_query)->fetch_assoc()['total'];

// Current month payroll
$month = date('Y-m-d');
$payroll_query = "SELECT COUNT(*) as total FROM payroll WHERE DATE_FORMAT(payroll_period, '%Y-%m') = DATE_FORMAT('$month', '%Y-%m')";
$stats['payroll_count'] = $conn->query($payroll_query)->fetch_assoc()['total'];

// Recent activities (audit logs)
$audit_query = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10";
$activities = $conn->query($audit_query);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Welcome Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h1>Welcome, <?php echo htmlspecialchars($current_user['first_name']); ?>! 👋</h1>
                        <p class="text-muted">Dashboard - <?php echo date('l, F d, Y'); ?></p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">Total Employees</p>
                                        <h3 class="mb-0"><?php echo $stats['total_employees']; ?></h3>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">Present Today</p>
                                        <h3 class="mb-0"><?php echo $stats['present_today']; ?></h3>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">Pending Leaves</p>
                                        <h3 class="mb-0"><?php echo $stats['pending_leaves']; ?></h3>
                                    </div>
                                    <i class="fas fa-hourglass-half fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">This Month Payroll</p>
                                        <h3 class="mb-0"><?php echo $stats['payroll_count']; ?></h3>
                                    </div>
                                    <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Quick Actions</h5>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <a href="<?php echo BASE_URL; ?>modules/employees/add.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user-plus"></i> Add Employee
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo BASE_URL; ?>modules/attendance/log.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-clock"></i> Log Attendance
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo BASE_URL; ?>modules/leave/request.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-calendar-plus"></i> Request Leave
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?php echo BASE_URL; ?>modules/payroll/manage.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-file-invoice-dollar"></i> Payroll
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activities</h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php if ($activities->num_rows > 0): ?>
                                    <?php while($activity = $activities->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($activity['action'] . ' - ' . $activity['module']); ?></h6>
                                                <small class="text-muted">Record ID: <?php echo $activity['record_id']; ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="list-group-item text-center text-muted py-3">
                                        No activities found
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>