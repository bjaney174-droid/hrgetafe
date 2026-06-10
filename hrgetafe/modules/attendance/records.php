<?php
/**
 * ATTENDANCE RECORDS PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

$current_user = getCurrentUser();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($employee_id) {
    $where .= " AND a.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

if ($date_from) {
    $where .= " AND a.date_attendance >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where .= " AND a.date_attendance <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($status) {
    $where .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM attendance a $where";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// Get records
$query = "SELECT a.*, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department
          FROM attendance a
          JOIN employees e ON a.employee_id = e.id
          $where
          ORDER BY a.date_attendance DESC, a.time_in DESC
          LIMIT $offset, $limit";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get employees for filter
$emp_query = "SELECT id, employee_id, first_name, last_name FROM employees WHERE status = 1 ORDER BY first_name";
$emp_result = $conn->query($emp_query);
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
                        <h2><i class="fas fa-list"></i> Attendance Records</h2>
                        <p class="text-muted">View and manage attendance records</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
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
                                    <div class="col-md-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" 
                                               value="<?php echo htmlspecialchars($date_from); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" 
                                               value="<?php echo htmlspecialchars($date_to); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="Present" <?php echo $status === 'Present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="Absent" <?php echo $status === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="Late" <?php echo $status === 'Late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="Early Leave" <?php echo $status === 'Early Leave' ? 'selected' : ''; ?>>Early Leave</option>
                                            <option value="On Leave" <?php echo $status === 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                        <a href="records.php" class="btn btn-secondary w-100 mt-2">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Info -->
                <div class="row mb-3">
                    <div class="col-12">
                        <p class="text-muted">
                            Showing <strong><?php echo $offset + 1; ?></strong> to 
                            <strong><?php echo min($offset + $limit, $total); ?></strong> 
                            of <strong><?php echo $total; ?></strong> records
                        </p>
                    </div>
                </div>

                <!-- Records Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                            <?php if (hasRole(ROLE_HR_MANAGER)): ?>
                                            <th>Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($record = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($record['date_attendance'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['emp_id']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($record['position']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $record['time_in'] ? date('H:i:s', strtotime($record['time_in'])) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $record['time_out'] ? date('H:i:s', strtotime($record['time_out'])) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($record['time_in'] && $record['time_out']) {
                                                        $time_in = new DateTime($record['date_attendance'] . ' ' . $record['time_in']);
                                                        $time_out = new DateTime($record['date_attendance'] . ' ' . $record['time_out']);
                                                        $interval = $time_in->diff($time_out);
                                                        echo htmlspecialchars($interval->format('%h:%I'));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'secondary';
                                                    if ($record['status'] == 'Present') $status_class = 'success';
                                                    elseif ($record['status'] == 'Absent') $status_class = 'danger';
                                                    elseif ($record['status'] == 'Late') $status_class = 'warning';
                                                    elseif ($record['status'] == 'On Leave') $status_class = 'info';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></small>
                                                </td>
                                                <?php if (hasRole(ROLE_HR_MANAGER)): ?>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="editRecord(<?php echo $record['id']; ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteRecord(<?php echo $record['id']; ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-5">
                                                    <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No records found</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php $total_pages = ceil($total / $limit); ?>
                <?php if ($total_pages > 1): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&employee_id=<?php echo $employee_id; ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=<?php echo urlencode($status); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function editRecord(id) {
    alert('Edit functionality coming soon');
}

function deleteRecord(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/attendance/api.php?action=delete',
            method: 'POST',
            data: { id: id },
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
                alert('Error deleting record');
            }
        });
    }
}
</script>