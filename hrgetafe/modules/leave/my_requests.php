<?php
/**
 * MY LEAVE REQUESTS PAGE
 * HRGetafe - Human Resources Information System
 */

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

$current_user = getCurrentUser();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where = "WHERE lr.employee_id = ?";
$params = [$current_user['id']];
$types = "i";

if ($status) {
    $where .= " AND lr.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM leave_requests lr $where";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

// Get records
$query = "SELECT lr.*, lt.leave_name, u.username as approved_by_user
          FROM leave_requests lr
          JOIN leave_types lt ON lr.leave_type_id = lt.id
          LEFT JOIN users u ON lr.approved_by = u.id
          $where
          ORDER BY lr.created_at DESC
          LIMIT $offset, $limit";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="fas fa-file-alt"></i> My Leave Requests</h2>
                                <p class="text-muted">View and manage your leave requests</p>
                            </div>
                            <a href="request.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Request
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="my_requests.php" class="btn btn-outline-primary <?php echo !$status ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i> All Requests
                            </a>
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
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Days</th>
                                            <th>Status</th>
                                            <th>Request Date</th>
                                            <th>Decision Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($request = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['leave_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($request['start_date'])); ?> to <?php echo date('M d, Y', strtotime($request['end_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $request['number_of_days']; ?> days</span>
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
                                                    <?php if ($request['approved_date']): ?>
                                                        <small><?php echo date('M d, Y', strtotime($request['approved_date'])); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="viewRequest(<?php echo $request['id']; ?>)" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] == 'Pending'): ?>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="cancelRequest(<?php echo $request['id']; ?>)" title="Cancel">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-5">
                                                    <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                                                    <p class="mt-3">No requests found</p>
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
            <div class="modal-body" id="modalBody">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
                    <p><strong>Leave Type:</strong> ${req.leave_name}</p>
                    <p><strong>Start Date:</strong> ${new Date(req.start_date).toLocaleDateString()}</p>
                    <p><strong>End Date:</strong> ${new Date(req.end_date).toLocaleDateString()}</p>
                    <p><strong>Number of Days:</strong> ${req.number_of_days} days</p>
                    <p><strong>Reason:</strong> ${req.reason || '-'}</p>
                    <p><strong>Status:</strong> <span class="badge bg-info">${req.status}</span></p>
                    ${req.approved_date ? `<p><strong>Decision Date:</strong> ${new Date(req.approved_date).toLocaleDateString()}</p>` : ''}
                    ${req.remarks ? `<p><strong>Remarks:</strong> ${req.remarks}</p>` : ''}
                `;
                $('#modalBody').html(html);
                new bootstrap.Modal(document.getElementById('viewModal')).show();
            }
        }
    });
}

function cancelRequest(id) {
    if (confirm('Are you sure you want to cancel this request?')) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/leave/api.php?action=cancel',
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
                alert('Error cancelling request');
            }
        });
    }
}
</script>