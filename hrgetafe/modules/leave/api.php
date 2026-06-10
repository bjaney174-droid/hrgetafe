<?php
/**
 * LEAVE MANAGEMENT - API ENDPOINTS
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route to appropriate function
switch($action) {
    case 'list':
        getLeaveRequestsList();
        break;
    case 'get':
        getLeaveRequest();
        break;
    case 'request':
        requestLeave();
        break;
    case 'approve':
        approveLeave();
        break;
    case 'reject':
        rejectLeave();
        break;
    case 'cancel':
        cancelLeave();
        break;
    case 'get_balance':
        getLeaveBalance();
        break;
    case 'get_types':
        getLeaveTypes();
        break;
    case 'add_type':
        addLeaveType();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get leave requests list
 */
function getLeaveRequestsList() {
    global $conn;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
    
    $where = "WHERE 1=1";
    $params = [];
    $types = "";
    
    // Only show own requests unless HR Manager
    if (!hasRole(ROLE_HR_MANAGER)) {
        $where .= " AND lr.employee_id = ?";
        $params[] = getCurrentUser()['id'];
        $types .= "i";
    }
    
    if ($status) {
        $where .= " AND lr.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM leave_requests lr $where";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Get records
    $query = "SELECT lr.*, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department,
              lt.leave_name, u.username as approved_by_user
              FROM leave_requests lr
              JOIN employees e ON lr.employee_id = e.id
              JOIN leave_types lt ON lr.leave_type_id = lt.id
              LEFT JOIN users u ON lr.approved_by = u.id
              $where
              ORDER BY lr.created_at DESC
              LIMIT $offset, $limit";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get single leave request
 */
function getLeaveRequest() {
    global $conn;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Leave request ID required']);
        return;
    }
    
    $query = "SELECT lr.*, e.employee_id as emp_id, e.first_name, e.last_name,
              lt.leave_name, u.username as approved_by_user
              FROM leave_requests lr
              JOIN employees e ON lr.employee_id = e.id
              JOIN leave_types lt ON lr.leave_type_id = lt.id
              LEFT JOIN users u ON lr.approved_by = u.id
              WHERE lr.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Leave request not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ]);
}

/**
 * Request leave
 */
function requestLeave() {
    global $conn;
    
    $current_user = getCurrentUser();
    $employee_id = $current_user['id'];
    $leave_type_id = isset($_POST['leave_type_id']) ? (int)$_POST['leave_type_id'] : 0;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
    
    // Validate
    if (!$leave_type_id || !$start_date || !$end_date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    if (strtotime($start_date) > strtotime($end_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        return;
    }
    
    // Calculate number of days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $number_of_days = $interval->days + 1;
    
    // Check if leave type exists
    $check_query = "SELECT id, days_allowed, is_paid FROM leave_types WHERE id = ? AND status = 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $leave_type_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid leave type']);
        return;
    }
    
    $leave_type = $check_result->fetch_assoc();
    
    // Check for overlapping requests
    $overlap_query = "SELECT COUNT(*) as count FROM leave_requests 
                      WHERE employee_id = ? AND status != 'Rejected' 
                      AND ((start_date <= ? AND end_date >= ?) OR 
                           (start_date <= ? AND end_date >= ?) OR
                           (start_date >= ? AND end_date <= ?))";
    $overlap_stmt = $conn->prepare($overlap_query);
    $overlap_stmt->bind_param("issssss", $employee_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $overlap_stmt->execute();
    $overlap_result = $overlap_stmt->get_result();
    $overlap = $overlap_result->fetch_assoc();
    
    if ($overlap['count'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You already have a leave request during this period']);
        return;
    }
    
    // Insert request
    $insert_query = "INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, number_of_days, reason, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iissis", $employee_id, $leave_type_id, $start_date, $end_date, $number_of_days, $reason);
    
    if ($insert_stmt->execute()) {
        $new_id = $insert_stmt->insert_id;
        logAudit('Request Leave', 'Leave', $new_id, null, 
            ['leave_type' => $leave_type_id, 'days' => $number_of_days, 'reason' => $reason]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Leave request submitted successfully',
            'id' => $new_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error submitting request']);
    }
}

/**
 * Approve leave request
 */
function approveLeave() {
    global $conn;
    
    requireRole(ROLE_HR_MANAGER);
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Leave request ID required']);
        return;
    }
    
    $current_user = getCurrentUser();
    $approved_by = $current_user['id'];
    $approved_date = date('Y-m-d H:i:s');
    
    // Get old data
    $query = "SELECT * FROM leave_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Leave request not found']);
        return;
    }
    
    $old_data = $result->fetch_assoc();
    
    // Update request
    $update_query = "UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_date = ?, remarks = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("issi", $approved_by, $approved_date, $remarks, $id);
    
    if ($update_stmt->execute()) {
        logAudit('Approve Leave', 'Leave', $id, $old_data, ['status' => 'Approved', 'remarks' => $remarks]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Leave request approved successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error approving request']);
    }
}

/**
 * Reject leave request
 */
function rejectLeave() {
    global $conn;
    
    requireRole(ROLE_HR_MANAGER);
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Leave request ID required']);
        return;
    }
    
    if (!$remarks) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        return;
    }
    
    $current_user = getCurrentUser();
    $approved_by = $current_user['id'];
    $approved_date = date('Y-m-d H:i:s');
    
    // Get old data
    $query = "SELECT * FROM leave_requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Leave request not found']);
        return;
    }
    
    $old_data = $result->fetch_assoc();
    
    // Update request
    $update_query = "UPDATE leave_requests SET status = 'Rejected', approved_by = ?, approved_date = ?, remarks = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("issi", $approved_by, $approved_date, $remarks, $id);
    
    if ($update_stmt->execute()) {
        logAudit('Reject Leave', 'Leave', $id, $old_data, ['status' => 'Rejected', 'remarks' => $remarks]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Leave request rejected'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error rejecting request']);
    }
}

/**
 * Cancel leave request
 */
function cancelLeave() {
    global $conn;
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $current_user = getCurrentUser();
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Leave request ID required']);
        return;
    }
    
    // Get request
    $query = "SELECT * FROM leave_requests WHERE id = ? AND employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $id, $current_user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Leave request not found or you do not have permission']);
        return;
    }
    
    $request = $result->fetch_assoc();
    
    if ($request['status'] != 'Pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
        return;
    }
    
    // Delete request
    $delete_query = "DELETE FROM leave_requests WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        logAudit('Cancel Leave', 'Leave', $id, $request);
        
        echo json_encode([
            'success' => true,
            'message' => 'Leave request cancelled'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error cancelling request']);
    }
}

/**
 * Get leave balance for employee
 */
function getLeaveBalance() {
    global $conn;
    
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    // Get all leave types
    $query = "SELECT lt.id, lt.leave_name, lt.days_allowed,
              COALESCE(SUM(CASE WHEN lr.status = 'Approved' THEN lr.number_of_days ELSE 0 END), 0) as used_days,
              (lt.days_allowed - COALESCE(SUM(CASE WHEN lr.status = 'Approved' THEN lr.number_of_days ELSE 0 END), 0)) as remaining_days
              FROM leave_types lt
              LEFT JOIN leave_requests lr ON lt.id = lr.leave_type_id AND lr.employee_id = ? AND YEAR(lr.start_date) = YEAR(CURDATE())
              WHERE lt.status = 1
              GROUP BY lt.id, lt.leave_name, lt.days_allowed";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $balance = [];
    while($row = $result->fetch_assoc()) {
        $balance[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $balance
    ]);
}

/**
 * Get leave types
 */
function getLeaveTypes() {
    global $conn;
    
    $query = "SELECT id, leave_name, description, days_allowed, is_paid FROM leave_types WHERE status = 1 ORDER BY leave_name";
    $result = $conn->query($query);
    
    $types = [];
    while($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $types
    ]);
}

/**
 * Add leave type (Admin only)
 */
function addLeaveType() {
    global $conn;
    
    requireRole(ROLE_ADMIN);
    
    $leave_name = isset($_POST['leave_name']) ? sanitizeInput($_POST['leave_name']) : '';
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $days_allowed = isset($_POST['days_allowed']) ? (int)$_POST['days_allowed'] : 0;
    $is_paid = isset($_POST['is_paid']) ? (int)$_POST['is_paid'] : 1;
    
    if (!$leave_name || !$days_allowed) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Leave name and days allowed are required']);
        return;
    }
    
    $query = "INSERT INTO leave_types (leave_name, description, days_allowed, is_paid, status) VALUES (?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssii", $leave_name, $description, $days_allowed, $is_paid);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        logAudit('Add Leave Type', 'Leave', $new_id, null, ['leave_name' => $leave_name]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Leave type added successfully',
            'id' => $new_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding leave type']);
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

?>