<?php
/**
 * ADD EMPLOYEE PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_MANAGER);

$current_user = getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required = ['employee_id', 'first_name', 'last_name', 'position', 'department', 'hire_date', 'employment_type'];
    foreach($required as $field) {
        if (empty($_POST[$field])) {
            $error = "Field '$field' is required";
            break;
        }
    }
    
    if (empty($error)) {
        // Check if employee_id exists
        $check_query = "SELECT id FROM employees WHERE employee_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $_POST['employee_id']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Employee ID already exists';
        } else {
            // Prepare data
            $employee_id = htmlspecialchars(trim($_POST['employee_id']));
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
            
            // Insert employee
            $query = "INSERT INTO employees 
                      (employee_id, first_name, middle_name, last_name, email, phone, birth_date, 
                       gender, civil_status, address, city, province, zip_code, position, 
                       department, employment_type, hire_date, salary, status) 
                      VALUES 
                      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("sssssssssssssssssd", 
                    $employee_id, $first_name, $middle_name, $last_name, $email, $phone, 
                    $birth_date, $gender, $civil_status, $address, $city, $province, $zip_code, 
                    $position, $department, $employment_type, $hire_date, $salary);
                
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    logAudit('Add Employee', 'Employees', $new_id, null, 
                        ['employee_id' => $employee_id, 'name' => "$first_name $last_name"]);
                    $success = 'Employee added successfully!';
                    // Redirect after 2 seconds
                    header("refresh:2;url=list.php");
                } else {
                    $error = 'Error adding employee: ' . $stmt->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    }
}

// Get departments for select
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
                        <h2><i class="fas fa-user-plus"></i> Add New Employee</h2>
                        <p class="text-muted">Fill in all required fields to add a new employee</p>
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
                        <form method="POST" id="addEmployeeForm">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="employee_id">
                                                Employee ID <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="employee_id" 
                                                   name="employee_id" placeholder="e.g., EMP001" required>
                                            <small class="text-muted">Unique identifier for the employee</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="hire_date">
                                                Hire Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="hire_date" 
                                                   name="hire_date" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="first_name">
                                                First Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="first_name" 
                                                   name="first_name" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="middle_name">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name" 
                                                   name="middle_name">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="last_name">
                                                Last Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="last_name" 
                                                   name="last_name" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="email">Email Address</label>
                                            <input type="email" class="form-control" id="email" 
                                                   name="email" placeholder="employee@hrgetafe.com">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="phone">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" 
                                                   name="phone" placeholder="+63 (XXX) XXX-XXXX">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="birth_date">Birth Date</label>
                                            <input type="date" class="form-control" id="birth_date" 
                                                   name="birth_date">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="gender">Gender</label>
                                            <select class="form-select" id="gender" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="civil_status">Civil Status</label>
                                            <select class="form-select" id="civil_status" name="civil_status">
                                                <option value="">Select Status</option>
                                                <option value="Single">Single</option>
                                                <option value="Married">Married</option>
                                                <option value="Widowed">Widowed</option>
                                                <option value="Separated">Separated</option>
                                                <option value="Divorced">Divorced</option>
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
                                               name="address" placeholder="House/Block Number, Street Name">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="city">City/Municipality</label>
                                            <input type="text" class="form-control" id="city" name="city">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="province">Province</label>
                                            <input type="text" class="form-control" id="province" name="province">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label" for="zip_code">ZIP Code</label>
                                            <input type="text" class="form-control" id="zip_code" name="zip_code">
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
                                            <label class="form-label" for="position">
                                                Position <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="position" 
                                                   name="position" placeholder="e.g., Administrative Officer" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="department">
                                                Department <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="department" 
                                                   name="department" placeholder="e.g., Human Resources" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="employment_type">
                                                Employment Type <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="employment_type" 
                                                    name="employment_type" required>
                                                <option value="">Select Type</option>
                                                <option value="Permanent">Permanent</option>
                                                <option value="Contractual">Contractual</option>
                                                <option value="Temporary">Temporary</option>
                                                <option value="Casual">Casual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" for="salary">Monthly Salary</label>
                                            <input type="number" class="form-control" id="salary" 
                                                   name="salary" placeholder="0.00" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="list.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="reset" class="btn btn-outline-secondary">
                                            <i class="fas fa-redo"></i> Clear
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Employee
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

<script>
document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Basic validation
    const required = ['employee_id', 'first_name', 'last_name', 'position', 'department', 'hire_date', 'employment_type'];
    let valid = true;
    
    required.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (valid) {
        this.submit();
    }
});
</script>
