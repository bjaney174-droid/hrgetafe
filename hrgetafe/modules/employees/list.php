<?php
/**
 * EMPLOYEE LIST PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

$current_user = getCurrentUser();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitizeInput($_GET['department']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE status = 1";
$params = [];

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?)";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

if (!empty($department)) {
    $where .= " AND department = ?";
    $params[] = $department;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM employees $where";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// Get employees
$query = "SELECT * FROM employees $where ORDER BY first_name ASC LIMIT $offset, $limit";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get departments for filter
$dept_query = "SELECT DISTINCT department FROM employees WHERE status = 1 ORDER BY department";
$dept_result = $conn->query($dept_query);
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
                            <h2><i class="fas fa-users"></i> Employee Management</h2>
                            <?php if (hasRole(ROLE_HR_MANAGER)): ?>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Employee
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-5">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search by name, email, or ID..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <select name="department" class="form-select">
                                            <option value="">All Departments</option>
                                            <?php while($dept = $dept_result->fetch_assoc()): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                                    <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                        <a href="list.php" class="btn btn-secondary w-100 mt-2">
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
                            of <strong><?php echo $total; ?></strong> employees
                        </p>
                    </div>
                </div>

                <!-- Employees Table -->
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
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Employment Type</th>
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
                                                    <small><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($employee['phone'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($employee['employment_type']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="view.php?id=<?php echo $employee['id']; ?>" 
                                                           class="btn btn-outline-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (hasRole(ROLE_HR_MANAGER)): ?>
                                                        <a href="edit.php?id=<?php echo $employee['id']; ?>" 
                                                           class="btn btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteEmployee(<?php echo $employee['id']; ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
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

                <!-- Pagination -->
                <?php $total_pages = ceil($total / $limit); ?>
                <?php if ($total_pages > 1): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department); ?>">
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

<script>
function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        showLoading('deleteBtn');
        deleteRecordAjax(
            '<?php echo BASE_URL; ?>modules/employees/api.php?action=delete',
            id,
            function(response) {
                hideLoading('deleteBtn', '<i class="fas fa-trash"></i>');
                if (response.success) {
                    showAlert('Employee deleted successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message || 'Error deleting employee', 'danger');
                }
            }
        );
    }
}
</script>
s