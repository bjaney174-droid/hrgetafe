<?php
/**
 * EDIT EMPLOYEE PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_MANAGER);

$current_user = getCurrentUser();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $employee) {
    // Get old data for audit log
    $old_data = $employee;
    
    // Prepare data
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $middle_name = htmlspecialchars(trim($_POST['middle_name'] ?? ''));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : NULL;
    $gender = htmlspecialchars(trim($_POST['gender'] ?? ''));
    $civil_status = htmlspecialchars(trim($_POST['civil_status'] ?? ''));
    $address = htmlspecialchars(trim($_POST['address'] ?? ''));
    $city = htmlspecialchars(trim($_POST['city'] ?? ''));
    $province = htmlspecialchars(trim($_POST['province'] ?? ''));
    $zip_code = htmlspecialchars(trim($_POST['zip_code'] ?? ''));
    $position = htmlspecialchars(trim($_POST['position']));
    $department = htmlspecialchars(trim($_POST['department']));
    $employment_type = htmlspecialchars(trim($_POST['employment_type']));
    $hire_date = $_POST['hire_date'];
    $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : 0;
    
    // Update employee
    $query = "UPDATE employees SET 
              first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ?, 
              birth_date = ?, gender = ?, civil_status = ?, address = ?, city = ?, 
              province = ?, zip_code = ?, position = ?, department = ?, 
              employment_type = ?, hire_date = ?, salary = ?, updated_at = NOW()
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ssssssssssssssssdi", 
            $first_name, $middle_name, $last_name, $email, $phone, 
            $birth_date, $gender, $civil_status, $address, $city, $province, $zip_code, 
            $position, $department, $employment_type, $hire_date, $salary, $id);
        
        if ($stmt->execute()) {
            $new_data = $_POST;
            logAudit('Update Employee', 'Employees', $id, $old_data, $new_data);
            $success = 'Employee updated successfully!';
            // Refresh employee data
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Error updating employee: ' . $stmt->error;
        }
    } else {
        $error = 'Database error: ' . $conn->error;
    }
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
                        <h2><i class="fas fa-edit"></i> Edit Employee</h2>
                        <p class="text-muted">Update employee information</p>
                    </div>
                </div>

                <!-- Error/Success Messages -->
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

                <!-- Form -->
                <div class="row">
                    <div class="col-12">
                        <form method="POST" id="editEmployeeForm">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Employee ID</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['employee_id']); ?>" 
                                                   disabled>
                                            <small class="text-muted">Cannot be changed</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="hire_date">Hire Date</label>
                                            <input type="date" class="form-control" id="hire_date" 
                                                   name="hire_date" 
                                                   value="<?php echo $employee['hire_date']; ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="first_name">First Name</label>
                                            <input type="text" class="form-control" id="first_name" 
                                                   name="first_name" 
                                                   value="<?php echo htmlspecialchars($employee['first_name']); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="middle_name">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name" 
                                                   name="middle_name"
                                                   value="<?php echo htmlspecialchars($employee['middle_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="last_name">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" 
                                                   name="last_name"
                                                   value="<?php echo htmlspecialchars($employee['last_name']); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="email">Email Address</label>
                                            <input type="email" class="form-control" id="email" 
                                                   name="email"
                                                   value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="phone">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" 
                                                   name="phone"
                                                   value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="birth_date">Birth Date</label>
                                            <input type="date" class="form-control" id="birth_date" 
                                                   name="birth_date"
                                                   value="<?php echo $employee['birth_date'] ?? ''; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="gender">Gender</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo $employee['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo $employee['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo $employee['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="civil_status">Civil Status</label>
                                            <select class="form-select" id="civil_status" name="civil_status">
                                                <option value="">Select Status</option>
                                                <option value="Single" <?php echo $employee['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                                <option value="Married" <?php echo $employee['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                                <option value="Widowed" <?php echo $employee['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                                <option value="Separated" <?php echo $employee['civil_status'] === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                                <option value="Divorced" <?php echo $employee['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Address Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="address">Street Address</label>
                                        <input type="text" class="form-control" id="address" 
                                               name="address"
                                               value="<?php echo htmlspecialchars($employee['address'] ?? ''); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="city">City/Municipality</label>
                                            <input type="text" class="form-control" id="city" 
                                                   name="city"
                                                   value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="province">Province</label>
                                            <input type="text" class="form-control" id="province" 
                                                   name="province"
                                                   value="<?php echo htmlspecialchars($employee['province'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="zip_code">ZIP Code</label>
                                            <input type="text" class="form-control" id="zip_code" 
                                                   name="zip_code"
                                                   value="<?php echo htmlspecialchars($employee['zip_code'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-briefcase"></i> Employment Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="position">Position</label>
                                            <input type="text" class="form-control" id="position" 
                                                   name="position"
                                                   value="<?php echo htmlspecialchars($employee['position']); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="department">Department</label>
                                            <input type="text" class="form-control" id="department" 
                                                   name="department"
                                                   value="<?php echo htmlspecialchars($employee['department']); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="employment_type">Employment Type</label>
                                            <select class="form-select" id="employment_type" name="employment_type">
                                                <option value="Permanent" <?php echo $employee['employment_type'] === 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                                <option value="Contractual" <?php echo $employee['employment_type'] === 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                                <option value="Temporary" <?php echo $employee['employment_type'] === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                                                <option value="Casual" <?php echo $employee['employment_type'] === 'Casual' ? 'selected' : ''; ?>>Casual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="salary">Monthly Salary</label>
                                            <input type="number" class="form-control" id="salary" 
                                                   name="salary"
                                                   value="<?php echo htmlspecialchars($employee['salary'] ?? ''); ?>"
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
