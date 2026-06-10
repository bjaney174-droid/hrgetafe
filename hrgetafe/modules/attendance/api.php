<?php
/**
 * ATTENDANCE MANAGEMENT - API ENDPOINTS
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Check if user is authorized
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route to appropriate function
switch($action) {
    case 'list':
        getAttendanceList();
        break;
    case 'get':
        getAttendance();
        break;
    case 'timein':
        timeIn();
        break;
    case 'timeout':
        timeOut();
        break;
    case 'update':
        updateAttendance();
        break;
    case 'delete':
        deleteAttendance();
        break;
    case 'report':
        getAttendanceReport();
        break;
    case 'employee_today':
        getEmployeeAttendanceToday();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get attendance list with filters
 */
function getAttendanceList() {
    global $conn;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
    
    // Build where clause
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
    
    // Get attendance records
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_id 
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
    
    $attendance = [];
    while($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $attendance,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get single attendance record
 */
function getAttendance() {
    global $conn;
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_id as emp_id 
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id 
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Attendance record not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ]);
}

/**
 * Time In - Log employee arrival
 */
function timeIn() {
    global $conn;
    
    requireRole(ROLE_HR_STAFF);
    
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $date_attendance = isset($_POST['date_attendance']) ? $_POST['date_attendance'] : date('Y-m-d');
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    // Verify employee exists
    $emp_check = "SELECT id FROM employees WHERE id = ? AND status = 1";
    $emp_stmt = $conn->prepare($emp_check);
    $emp_stmt->bind_param("i", $employee_id);
    $emp_stmt->execute();
    
    if ($emp_stmt->get_result()->num_rows == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        return;
    }
    
    // Check if already timed in today
    $check_query = "SELECT id, time_in FROM attendance WHERE employee_id = ? AND date_attendance = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("is", $employee_id, $date_attendance);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $record = $check_result->fetch_assoc();
        if ($record['time_in']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Employee already timed in today']);
            return;
        }
        
        // Update existing record
        $time_in = date('H:i:s');
        $query = "UPDATE attendance SET time_in = ?, status = 'Present' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $time_in, $record['id']);
        
        if ($stmt->execute()) {
            logAudit('Time In', 'Attendance', $record['id'], null, 
                ['employee_id' => $employee_id, 'time_in' => $time_in]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Time In recorded successfully',
                'id' => $record['id']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error recording time in']);
        }
    } else {
        // Create new record
        $time_in = date('H:i:s');
        $query = "INSERT INTO attendance (employee_id, date_attendance, time_in, status) 
                  VALUES (?, ?, ?, 'Present')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $employee_id, $date_attendance, $time_in);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            logAudit('Time In', 'Attendance', $new_id, null, 
                ['employee_id' => $employee_id, 'time_in' => $time_in]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Time In recorded successfully',
                'id' => $new_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error recording time in']);
        }
    }
}

/**
 * Time Out - Log employee departure
 */
function timeOut() {
    global $conn;
    
    requireRole(ROLE_HR_STAFF);
    
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $date_attendance = isset($_POST['date_attendance']) ? $_POST['date_attendance'] : date('Y-m-d');
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }
    
    // Get today's attendance record
    $query = "SELECT id, time_in FROM attendance WHERE employee_id = ? AND date_attendance = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employee_id, $date_attendance);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No time in record found for today']);
        return;
    }
    
    $record = $result->fetch_assoc();
    
    if (!$record['time_in']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee has not timed in yet']);
        return;
    }
    
    // Update record with time out
    $time_out = date('H:i:s');
    $update_query = "UPDATE attendance SET time_out = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $time_out, $record['id']);
    
    if ($update_stmt->execute()) {
        logAudit('Time Out', 'Attendance', $record['id'], null, 
            ['employee_id' => $employee_id, 'time_out' => $time_out]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Time Out recorded successfully',
            'id' => $record['id']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error recording time out']);
    }
}

/**
 * Update attendance record
 */
function updateAttendance() {
    global $conn;
    
    requireRole(ROLE_HR_MANAGER);
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    // Get old data
    $query = "SELECT * FROM attendance WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Attendance record not found']);
        return;
    }
    
    $old_data = $result->fetch_assoc();
    
    // Prepare update data
    $time_in = isset($_POST['time_in']) ? $_POST['time_in'] : $old_data['time_in'];
    $time_out = isset($_POST['time_out']) ? $_POST['time_out'] : $old_data['time_out'];
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : $old_data['status'];
    $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : $old_data['remarks'];
    
    $update_query = "UPDATE attendance SET time_in = ?, time_out = ?, status = ?, remarks = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssssi", $time_in, $time_out, $status, $remarks, $id);
    
    if ($update_stmt->execute()) {
        $new_data = ['time_in' => $time_in, 'time_out' => $time_out, 'status' => $status, 'remarks' => $remarks];
        logAudit('Update Attendance', 'Attendance', $id, $old_data, $new_data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance record updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating attendance record']);
    }
}

/**
 * Delete attendance record
 */
function deleteAttendance() {
    global $conn;
    
    requireRole(ROLE_HR_MANAGER);
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }
    
    $query = "DELETE FROM attendance WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAudit('Delete Attendance', 'Attendance', $id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Attendance record deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting attendance record']);
    }
}

/**
 * Get attendance report
 */
function getAttendanceReport() {
    global $conn;
    
    $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    
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
              COUNT(*) as total_days,
              SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_days,
              SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
              SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_days,
              SUM(CASE WHEN a.status = 'Early Leave' THEN 1 ELSE 0 END) as early_leave_days,
              SUM(CASE WHEN a.status = 'On Leave' THEN 1 ELSE 0 END) as on_leave_days
              FROM attendance a
              JOIN employees e ON a.employee_id = e.id
              $where
              GROUP BY a.employee_id, e.first_name, e.last_name, e.employee_id
              ORDER BY e.first_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $report = [];
    while($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report,
        'month' => $month
    ]);
}

/**
 * Get employee attendance for today
 */
function getEmployeeAttendanceToday() {
    global $conn;
    
    $today = date('Y-m-d');
    
    $query = "SELECT 
              e.id,
              e.employee_id,
              e.first_name,
              e.last_name,
              e.position,
              e.department,
              COALESCE(a.id, 0) as attendance_id,
              COALESCE(a.time_in, '-') as time_in,
              COALESCE(a.time_out, '-') as time_out,
              COALESCE(a.status, 'Not Recorded') as status
              FROM employees e
              LEFT JOIN attendance a ON e.id = a.employee_id AND a.date_attendance = ?
              WHERE e.status = 1
              ORDER BY e.first_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $attendance,
        'date' => $today
    ]);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

?>