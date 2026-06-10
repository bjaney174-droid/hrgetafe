<?php
/**
 * LEAVE APPROVALS PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireRole(ROLE_HR_MANAGER);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'Pending';
$limit = 15;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = ?";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("s", $status);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// Get records
$query = "SELECT lr.*, e.id as employee_id, e.employee_id as emp_id, e.first_name, e.last_name, e.position, e.department, lt.leave_name
          FROM leave_requests lr
          JOIN employees e ON lr.employee_id = e.id
          JOIN leave_types lt ON lr.leave_type_id = lt.id
          WHERE lr.status = ?
          ORDER BY lr.created_at ASC
          LIMIT $offset, $limit";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $status);
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
                        <h2><i class="fas fa-check-circle"></i> Leave Approvals</h2>
                        <p class="text-muted">Review and process leave requests</p>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="?status=Pending" class="btn btn-outline-warning <?php echo $status === 'Pending' ? 'active' : ''; ?>">
                                <i class="fas fa-hourglass-half"></i> Pending
                            </a>
                            <a href="?status=Approved" class="btn btn-outline-success <?php echo $status === 'Approved' ? 'active' : ''; ?>">
                                <i class="fas fa-check-circle"></i> Approved
                            </a>
                            <a href="?status=Rejected" class="btn btn-outline-danger <?php echo $status === 'Rejected' ? 'active' : ''; ?>">
                                <i class="fas fa-times-circle"></i> Rejected
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Results Info -->
                <div class="row mb-3">
                    <div class="col-12">
                        <p class="text-muted">
                            Showing <strong><?php echo $offset + 1; ?></strong> to 
                            <strong><?php echo min($offset + $limit, $total); ?></strong> 
                            of <strong><?php echo $total; ?></strong> requests
                        </p>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee</th>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($request = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($request['emp_id']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['leave_name']); ?></td>
                                                <td>
                                                    <small><?php echo date('M d', strtotime($request['start_date'])); ?> - <?php echo date('M d', strtotime($request['end_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $request['number_of_days']; ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($request['reason'], 0, 30)); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'secondary';
                                                    $icon = 'hourglass-half';
                                                    if ($request['status'] == 'Approved') {
                                                        $status_class = 'success';
                                                        $icon = 'check-circle';
                                                    } elseif ($request['status'] == 'Rejected') {
                                                        $status_class = 'danger';
                                                        $icon = 'times-circle';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo $request['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <?php if ($request['status'] == 'Pending'): ?>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="approveRequest(<?php echo $request['id']; ?>)" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="rejectRequest(<?php echo $request['id']; ?>)" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="viewRequest(<?php echo $request['id']; ?>)" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-5">
                                                    <i class="fas fa-check" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No <?php echo strtolower($status); ?> requests</p>
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>">
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

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leave Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">Loading...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Add any remarks or notes"></textarea>
                    </div>
                    <input type="hidden" name="id" id="requestId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Leave Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Please provide a reason for rejection" required></textarea>
                    </div>
                    <input type="hidden" name="id" id="rejectRequestId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewRequest(id) {
    $.ajax({
        url: '<?php echo BASE_URL; ?>modules/leave/api.php?action=get&id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const req = response.data;
                let html = `
                    <p><strong>Employee:</strong> ${req.first_name} ${req.last_name}</p>
                    <p><strong>Leave Type:</strong> ${req.leave_name}</p>
                    <p><strong>Start Date:</strong> ${new Date(req.start_date).toLocaleDateString()}</p>
                    <p><strong>End Date:</strong> ${new Date(req.end_date).toLocaleDateString()}</p>
                    <p><strong>Number of Days:</strong> ${req.number_of_days} days</p>
                    <p><strong>Reason:</strong> ${req.reason || '-'}</p>
                    <p><strong>Status:</strong> <span class="badge bg-info">${req.status}</span></p>
                `;
                $('#modalBody').html(html);
                new bootstrap.Modal(document.getElementById('viewModal')).show();
            }
        }
    });
}

function approveRequest(id) {
    $('#requestId').val(id);
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(id) {
    $('#rejectRequestId').val(id);
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

$('#approveForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '<?php echo BASE_URL; ?>modules/leave/api.php?action=approve',
        method: 'POST',
        data: $(this).serialize(),
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
            alert('Error approving request');
        }
    });
});

$('#rejectForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '<?php echo BASE_URL; ?>modules/leave/api.php?action=reject',
        method: 'POST',
        data: $(this).serialize(),
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
            alert('Error rejecting request');
        }
    });
});
</script>