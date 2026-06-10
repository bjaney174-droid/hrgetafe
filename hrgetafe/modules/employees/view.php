<?php
/**
 * VIEW EMPLOYEE PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Get employee data
if ($id) {
    $query = "SELECT * FROM employees WHERE id = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = 'Employee not found';
        $employee = null;
    } else {
        $employee = $result->fetch_assoc();
    }
} else {
    $error = 'Invalid employee ID';
    $employee = null;
}

if (!$employee) {
    include '../../includes/header.php';
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2">
                <?php include '../../includes/sidebar.php'; ?>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="content-wrapper p-4">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                    <a href="list.php" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit();
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
                                <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                                <p class="text-muted">Employee ID: <strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></p>
                            </div>
                            <div>
                                <?php if (hasRole(ROLE_HR_MANAGER)): ?>
                                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php endif; ?>
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-id-card"></i> Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <p>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . $employee['last_name']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Birth Date</label>
                                        <p><?php echo $employee['birth_date'] ? date('F d, Y', strtotime($employee['birth_date'])) : '-'; ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <p><?php echo htmlspecialchars($employee['email'] ?? '-'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <p><?php echo htmlspecialchars($employee['phone'] ?? '-'); ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Gender</label>
                                        <p><?php echo htmlspecialchars($employee['gender'] ?? '-'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Civil Status</label>
                                        <p><?php echo htmlspecialchars($employee['civil_status'] ?? '-'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Address Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Street Address</label>
                                        <p><?php echo htmlspecialchars($employee['address'] ?? '-'); ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City/Municipality</label>
                                        <p><?php echo htmlspecialchars($employee['city'] ?? '-'); ?></p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Province</label>
                                        <p><?php echo htmlspecialchars($employee['province'] ?? '-'); ?></p>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ZIP Code</label>
                                        <p><?php echo htmlspecialchars($employee['zip_code'] ?? '-'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-briefcase"></i> Employment Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Position</label>
                                        <p><strong><?php echo htmlspecialchars($employee['position']); ?></strong></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <p><strong><?php echo htmlspecialchars($employee['department']); ?></strong></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Employment Type</label>
                                        <p>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($employee['employment_type']); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hire Date</label>
                                        <p><?php echo date('F d, Y', strtotime($employee['hire_date'])); ?></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Monthly Salary</label>
                                        <p>
                                            <strong>
                                                <?php echo number_format($employee['salary'], 2); ?> PHP
                                            </strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle"></i> System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Created At</label>
                                        <p><?php echo date('F d, Y g:i A', strtotime($employee['created_at'])); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Updated</label>
                                        <p><?php echo date('F d, Y g:i A', strtotime($employee['updated_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
