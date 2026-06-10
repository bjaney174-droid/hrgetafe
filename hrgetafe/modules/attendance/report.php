<?php
/**
 * ATTENDANCE REPORT PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_MANAGER);

$current_user = getCurrentUser();
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

// Get employees for filter
$emp_query = "SELECT id, employee_id, first_name, last_name FROM employees WHERE status = 1 ORDER BY first_name";
$emp_result = $conn->query($emp_query);

// Build report query
$where = "WHERE DATE_FORMAT(a.date_attendance, '%Y-%m') = ?";
$params = [$month];
$types = "s";

if ($employee_id) {
    $where .= " AND a.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

$query = "SELECT 
          a.employee_id,
          e.first_name,
          e.last_name,
          e.employee_id as emp_id,
          e.position,
          e.department,
          COUNT(*) as total_days,
          SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
          SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
          SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days,
          SUM(CASE WHEN a.status = 'Early Leave' THEN 1 ELSE 0 END) as early_leave_days,
          SUM(CASE WHEN a.status = 'On Leave' THEN 1 ELSE 0 END) as on_leave_days
          FROM attendance a
          JOIN employees e ON a.employee_id = e.id
          $where
          GROUP BY a.employee_id, e.first_name, e.last_name, e.employee_id, e.position, e.department
          ORDER BY e.first_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
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
                        <h2><i class="fas fa-chart-bar"></i> Attendance Report</h2>
                        <p class="text-muted">Monthly attendance summary</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Month</label>
                                        <input type="month" name="month" class="form-control" 
                                               value="<?php echo htmlspecialchars($month); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Employee</label>
                                        <select name="employee_id" class="form-select">
                                            <option value="">All Employees</option>
                                            <?php while($emp = $emp_result->fetch_assoc()): ?>
                                            <option value="<?php echo $emp['id']; ?>" 
                                                    <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Generate
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <strong>Report Period:</strong> <?php echo date('F Y', strtotime($month . '-01')); ?>
                        </div>
                    </div>
                </div>

                <!-- Report Table -->
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
                                            <th class="text-center">Total</th>
                                            <th class="text-center">
                                                <span class="badge bg-success">Present</span>
                                            </th>
                                            <th class="text-center">
                                                <span class="badge bg-danger">Absent</span>
                                            </th>
                                            <th class="text-center">
                                                <span class="badge bg-warning">Late</span>
                                            </th>
                                            <th class="text-center">
                                                <span class="badge bg-secondary">Early Leave</span>
                                            </th>
                                            <th class="text-center">
                                                <span class="badge bg-info">On Leave</span>
                                            </th>
                                            <th>Attendance Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php 
                                            $grand_total_days = 0;
                                            $grand_present = 0;
                                            $grand_absent = 0;
                                            $grand_late = 0;
                                            $grand_early_leave = 0;
                                            $grand_on_leave = 0;
                                            ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['emp_id']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($row['position']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($row['department']); ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?php echo (int)$row['total_days']; ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo (int)$row['present_days']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger"><?php echo (int)$row['absent_days']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning"><?php echo (int)$row['late_days']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo (int)$row['early_leave_days']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo (int)$row['on_leave_days']; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $attendance_rate = $row['total_days'] > 0 ? 
                                                        round(($row['present_days'] / $row['total_days']) * 100, 2) : 0;
                                                    $rate_class = $attendance_rate >= 90 ? 'success' : ($attendance_rate >= 75 ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?php echo $rate_class; ?>">
                                                        <?php echo number_format($attendance_rate, 2); ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php 
                                            $grand_total_days += (int)$row['total_days'];
                                            $grand_present += (int)$row['present_days'];
                                            $grand_absent += (int)$row['absent_days'];
                                            $grand_late += (int)$row['late_days'];
                                            $grand_early_leave += (int)$row['early_leave_days'];
                                            $grand_on_leave += (int)$row['on_leave_days'];
                                            ?>
                                            <?php endwhile; ?>
                                            
                                            <!-- Grand Total -->
                                            <tr class="table-light fw-bold">
                                                <td colspan="4">TOTAL</td>
                                                <td class="text-center"><?php echo $grand_total_days; ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo $grand_present; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger"><?php echo $grand_absent; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning"><?php echo $grand_late; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo $grand_early_leave; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo $grand_on_leave; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $overall_rate = $grand_total_days > 0 ? 
                                                        round(($grand_present / $grand_total_days) * 100, 2) : 0;
                                                    $overall_class = $overall_rate >= 90 ? 'success' : ($overall_rate >= 75 ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?php echo $overall_class; ?>">
                                                        <?php echo number_format($overall_rate, 2); ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-5">
                                                    <i class="fas fa-chart-line" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No data available for this period</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Print Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="button" class="btn btn-info" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function exportToCSV() {
    alert('Export functionality coming soon');
}
</script>