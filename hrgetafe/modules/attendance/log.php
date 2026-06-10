<?php
/**
 * ATTENDANCE LOG PAGE - Time In/Out
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_STAFF);

$current_user = getCurrentUser();
$today = date('Y-m-d');
$success = '';
$error = '';

// Get all employees for today's attendance
$query = "SELECT 
          e.id,
          e.employee_id,
          e.first_name,
          e.last_name,
          e.position,
          e.department,
          COALESCE(a.id, 0) as attendance_id,
          COALESCE(a.time_in, NULL) as time_in,
          COALESCE(a.time_out, NULL) as time_out,
          COALESCE(a.status, 'Not Recorded') as status
          FROM employees e
          LEFT JOIN attendance a ON e.id = a.employee_id AND a.date_attendance = ?
          WHERE e.status = 1
          ORDER BY e.first_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
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
                        <h2><i class="fas fa-clock"></i> Log Attendance</h2>
                        <p class="text-muted">Record time in/out for employees</p>
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

                <!-- Date Info -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <p class="mb-0">
                                    <strong>Date:</strong> <?php echo date('F d, Y', strtotime($today)); ?> 
                                    <span class="badge bg-primary"><?php echo date('l'); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Department</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($employee = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($employee['position']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($employee['department']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($employee['time_in']): ?>
                                                        <strong><?php echo date('H:i:s', strtotime($employee['time_in'])); ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($employee['time_out']): ?>
                                                        <strong><?php echo date('H:i:s', strtotime($employee['time_out'])); ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'secondary';
                                                    if ($employee['status'] == 'Present') $status_class = 'success';
                                                    elseif ($employee['status'] == 'Absent') $status_class = 'danger';
                                                    elseif ($employee['status'] == 'Late') $status_class = 'warning';
                                                    elseif ($employee['status'] == 'On Leave') $status_class = 'info';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($employee['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php if (!$employee['time_in']): ?>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="timeIn(<?php echo $employee['id']; ?>)" title="Time In">
                                                            <i class="fas fa-arrow-right"></i>
                                                        </button>
                                                        <?php else: ?>
                                                            <?php if (!$employee['time_out']): ?>
                                                            <button type="button" class="btn btn-outline-warning" 
                                                                    onclick="timeOut(<?php echo $employee['id']; ?>)" title="Time Out">
                                                                <i class="fas fa-arrow-left"></i>
                                                            </button>
                                                            <?php else: ?>
                                                            <button type="button" class="btn btn-outline-info" disabled title="Completed">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <a href="records.php?employee_id=<?php echo $employee['id']; ?>" 
                                                           class="btn btn-outline-secondary" title="View Records">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-5">
                                                    <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No employees found</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
function timeIn(employeeId) {
    if (confirm('Record Time In for this employee?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/attendance/api.php?action=timein',
            method: 'POST',
            data: {
                employee_id: employeeId,
                date_attendance: '<?php echo $today; ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error recording time in');
            }
        });
    }
}

function timeOut(employeeId) {
    if (confirm('Record Time Out for this employee?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/attendance/api.php?action=timeout',
            method: 'POST',
            data: {
                employee_id: employeeId,
                date_attendance: '<?php echo $today; ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error recording time out');
            }
        });
    }
}
</script>