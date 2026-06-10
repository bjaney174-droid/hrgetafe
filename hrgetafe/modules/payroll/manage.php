<?php
/**
 * PAYROLL MANAGEMENT PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_MANAGER);

$current_user = getCurrentUser();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE DATE_FORMAT(p.payroll_period, '%Y-%m') = ?";
$params = [$month];
$types = "s";

if ($status) {
    $where .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM payroll p $where";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// Get records
$query = "SELECT p.*, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department
          FROM payroll p
          JOIN employees e ON p.employee_id = e.id
          $where
          ORDER BY e.first_name ASC
          LIMIT $offset, $limit";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get payroll summary for the month
$summary_query = "SELECT 
                  COUNT(DISTINCT p.employee_id) as total_employees,
                  SUM(p.basic_salary) as total_basic,
                  SUM(p.allowances) as total_allowances,
                  SUM(p.deductions) as total_deductions,
                  SUM(p.net_salary) as total_net,
                  SUM(CASE WHEN p.status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                  SUM(CASE WHEN p.status = 'Draft' THEN 1 ELSE 0 END) as draft_count,
                  SUM(CASE WHEN p.status = 'Finalized' THEN 1 ELSE 0 END) as finalized_count
                  FROM payroll p
                  WHERE DATE_FORMAT(p.payroll_period, '%Y-%m') = ?";

$summary_stmt = $conn->prepare($summary_query);
$summary_stmt->bind_param("s", $month);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-money-bill-wave"></i> Payroll Management</h2>
                                <p class="text-muted">Generate and manage employee payroll</p>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="generatePayroll()">
                                <i class="fas fa-cogs"></i> Generate Payroll
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <p class="text-muted mb-0">Total Employees</p>
                                <h3 class="mb-0"><?php echo $summary['total_employees'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-peso-sign fa-2x text-success mb-2"></i>
                                <p class="text-muted mb-0">Total Net Salary</p>
                                <h3 class="mb-0">₱<?php echo number_format($summary['total_net'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                                <p class="text-muted mb-0">Paid</p>
                                <h3 class="mb-0"><?php echo $summary['paid_count'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-file-alt fa-2x text-warning mb-2"></i>
                                <p class="text-muted mb-0">Draft</p>
                                <h3 class="mb-0"><?php echo $summary['draft_count'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Payroll Period</label>
                                        <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($month); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="Draft" <?php echo $status === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                            <option value="Finalized" <?php echo $status === 'Finalized' ? 'selected' : ''; ?>>Finalized</option>
                                            <option value="Paid" <?php echo $status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payroll Table -->
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
                                            <th class="text-end">Basic Salary</th>
                                            <th class="text-end">Allowances</th>
                                            <th class="text-end">Deductions</th>
                                            <th class="text-end">Net Salary</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($payroll = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($payroll['emp_id']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></td>
                                                <td><small><?php echo htmlspecialchars($payroll['position']); ?></small></td>
                                                <td class="text-end">₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                                <td class="text-end">₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                                <td class="text-end">₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                                <td class="text-end"><strong>₱<?php echo number_format($payroll['net_salary'], 2); ?></strong></td>
                                                <td>
                                                    <?php
                                                    $status_class = 'secondary';
                                                    if ($payroll['status'] == 'Draft') $status_class = 'warning';
                                                    elseif ($payroll['status'] == 'Finalized') $status_class = 'info';
                                                    elseif ($payroll['status'] == 'Paid') $status_class = 'success';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $payroll['status']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-info" onclick="viewPayroll(<?php echo $payroll['id']; ?>)" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($payroll['status'] == 'Draft'): ?>
                                                        <button type="button" class="btn btn-outline-primary" onclick="finalizePayroll(<?php echo $payroll['id']; ?>)" title="Finalize">
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <?php if ($payroll['status'] == 'Finalized'): ?>
                                                        <button type="button" class="btn btn-outline-success" onclick="markPaid(<?php echo $payroll['id']; ?>)" title="Mark Paid">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <a href="payslips.php?payroll_id=<?php echo $payroll['id']; ?>" class="btn btn-outline-secondary" title="Payslip">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-5">
                                                    <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No payroll records found</p>
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
function generatePayroll() {
    const month = prompt('Enter payroll month (YYYY-MM):', '<?php echo $month; ?>');
    if (month) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/payroll/api.php?action=generate',
            method: 'POST',
            data: { month: month },
            dataType: 'json',
            success: function(response) {
                alert(response.message);
                location.reload();
            },
            error: function() {
                alert('Error generating payroll');
            }
        });
    }
}

function viewPayroll(id) {
    window.open('<?php echo BASE_URL; ?>modules/payroll/payslips.php?payroll_id=' + id);
}

function finalizePayroll(id) {
    if (confirm('Finalize this payroll? You will not be able to edit it.')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/payroll/api.php?action=finalize',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                }
            }
        });
    }
}

function markPaid(id) {
    const date = prompt('Payment date (YYYY-MM-DD):', '<?php echo date('Y-m-d'); ?>');
    if (date) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/payroll/api.php?action=mark_paid',
            method: 'POST',
            data: { id: id, payment_date: date },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                }
            }
        });
    }
}
</script>