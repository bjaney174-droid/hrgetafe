<?php
/**
 * EMPLOYEE MANAGEMENT - API ENDPOINTS
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is authorized
requireRole(ROLE_HR_MANAGER);

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route to appropriate function
switch($action) {
    case 'list':
        getEmployeesList();
        break;
    case 'get':
        getEmployee();
        break;
    case 'add':
        addEmployee();
        break;
    case 'update':
        updateEmployee();
        break;
    case 'delete':
        deleteEmployee();
        break;
    case 'search':
        searchEmployees();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get list of all employees
 */
function getEmployeesList() {
    global $conn;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countResult = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 1");
    $total = $countResult->fetch_assoc()['total'];
    
    // Get employees
    $query = "SELECT * FROM employees WHERE status = 1 ORDER BY first_name ASC LIMIT $offset, $limit";
    $result = $conn->query($query);
    
    $employees = [];
    while($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $employees,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get single employee
 */
function getEmployee() {
    global $conn;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $query = "SELECT * FROM employees WHERE id = ? AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ]);
}

/**
 * Add new employee
 */
function addEmployee() {
    global $conn;
    
    // Validate required fields
    $required = ['employee_id', 'first_name', 'last_name', 'position', 'department', 'hire_date', 'employment_type'];
    foreach($required as $field) {
        if (empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Check if employee_id already exists
    $check_query = "SELECT id FROM employees WHERE employee_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $_POST['employee_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID already exists']);
        return;
    }
    
    // Prepare data
    $employee_id = sanitizeInput($_POST['employee_id']);
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $birth_date = $_POST['birth_date'] ?? NULL;
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $civil_status = sanitizeInput($_POST['civil_status'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $province = sanitizeInput($_POST['province'] ?? '');
    $zip_code = sanitizeInput($_POST['zip_code'] ?? '');
    $position = sanitizeInput($_POST['position']);
    $department = sanitizeInput($_POST['department']);
    $employment_type = sanitizeInput($_POST['employment_type']);
    $hire_date = $_POST['hire_date'];
    $salary = $_POST['salary'] ?? 0;
    
    // Insert employee
    $query = "INSERT INTO employees 
              (employee_id, first_name, middle_name, last_name, email, phone, birth_date, 
               gender, civil_status, address, city, province, zip_code, position, 
               department, employment_type, hire_date, salary, status) 
              VALUES 
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("sssssssssssssssssd", 
        $employee_id, $first_name, $middle_name, $last_name, $email, $phone, 
        $birth_date, $gender, $civil_status, $address, $city, $province, $zip_code, 
        $position, $department, $employment_type, $hire_date, $salary);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        logAudit('Add Employee', 'Employees', $new_id, null, 
            ['employee_id' => $employee_id, 'name' => "$first_name $last_name"]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Employee added successfully',
            'id' => $new_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding employee']);
    }
}

/**
 * Update employee
 */
function updateEmployee() {
    global $conn;
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    // Get current employee data
    $query = "SELECT * FROM employees WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    $old_data = $result->fetch_assoc();
    
    // Prepare update data
    $fields = ['first_name', 'middle_name', 'last_name', 'email', 'phone', 'birth_date', 
               'gender', 'civil_status', 'address', 'city', 'province', 'zip_code', 
               'position', 'department', 'employment_type', 'hire_date', 'salary'];
    
    $update_data = [];
    foreach($fields as $field) {
        if (isset($_POST[$field])) {
            $update_data[$field] = sanitizeInput($_POST[$field]);
        }
    }
    
    if (empty($update_data)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    // Build update query
    $set_clause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($update_data)));
    $query = "UPDATE employees SET $set_clause, updated_at = NOW() WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $types = str_repeat('s', count($update_data)) . 'i';
    $values = array_values($update_data);
    $values[] = $id;
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        logAudit('Update Employee', 'Employees', $id, $old_data, $update_data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Employee updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating employee']);
    }
}

/**
 * Delete (deactivate) employee
 */
function deleteEmployee() {
    global $conn;
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    $query = "UPDATE employees SET status = 0, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAudit('Delete Employee', 'Employees', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting employee']);
    }
}

/**
 * Search employees
 */
function searchEmployees() {
    global $conn;
    
    $search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
    
    if (strlen($search) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Search term must be at least 2 characters']);
        return;
    }
    
    $search_term = '%' . $search . '%';
    $query = "SELECT * FROM employees 
              WHERE status = 1 AND 
              (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR employee_id LIKE ?) 
              ORDER BY first_name ASC LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $employees,
        'count' => count($employees)
    ]);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

?>
