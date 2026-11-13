<?php
// --- 1. SETUP & SECURITY ---
require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';
checkLogin();

// STRICT SECURITY: Only 'super_admin' is allowed.
// If a regular admin (Tech Support) tries to access, kick them out.
if ($_SESSION['role'] != 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// --- 2. PAGINATION & FILTERS ---
$limit = 20; // Number of logs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Capture inputs for "Sticky" filters
$filter_user = isset($_GET['user']) ? cleanInput($_GET['user']) : '';
$filter_action = isset($_GET['action']) ? cleanInput($_GET['action']) : '';

// Build Dynamic Query
$where = ["1=1"]; // Default true condition
if($filter_user) { 
    $where[] = "u.username LIKE '%$filter_user%'"; 
}
if($filter_action) { 
    $where[] = "l.action_type = '$filter_action'"; 
}
$where_sql = implode(' AND ', $where);

// Fetch Total Count (Needed for Pagination Buttons)
$count_sql = "SELECT COUNT(*) as total FROM ticket_logs l LEFT JOIN users u ON l.user_id = u.id WHERE $where_sql";
$total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Actual Data
$sql = "SELECT l.*, u.username, u.role, t.subject 
        FROM ticket_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        LEFT JOIN tickets t ON l.ticket_id = t.id 
        WHERE $where_sql
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset";

$logs = $conn->query($sql);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
    .theme-header { background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); color: white; border-bottom: none; font-weight: 600; }
    .theme-table-head { background-color: #caf0f8 !important; color: #0077b6 !important; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .card-header { border-radius: 12px 12px 0 0 !important; padding: 1rem 1.25rem; }
    .log-icon { width: 30px; text-align: center; font-size: 1.1rem; }
    .btn-primary { background: #0077b6; border-color: #0077b6; }
    .btn-primary:hover { background: #023e8a; border-color: #023e8a; }
</style>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm h-100">
        <div class="card-header theme-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-white"><i class="fas fa-history me-2"></i>System Activity Logs</h5>
            <span class="badge bg-light text-primary shadow-sm fw-bold"><?= number_format($total_rows) ?> Activities</span>
        </div>
        
        <div class="card-body p-4">
            
            <form method="GET" class="row g-3 mb-4 p-3 bg-light rounded border align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">SEARCH USER</label>
                    <input type="text" name="user" class="form-control" placeholder="Enter Username..." value="<?= htmlspecialchars($filter_user) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">ACTION TYPE</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="status_change" <?= $filter_action=='status_change'?'selected':'' ?>>Status Changes</option>
                        <option value="comment" <?= $filter_action=='comment'?'selected':'' ?>>Comments</option>
                        <option value="log" <?= $filter_action=='log'?'selected':'' ?>>General System Logs</option>
                        <option value="login" <?= $filter_action=='login'?'selected':'' ?>>User Logins</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100 fw-bold shadow-sm">
                        <i class="fas fa-filter me-1"></i> Filter Logs
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="admin_logs.php" class="btn btn-outline-secondary w-100" title="Reset Filters"><i class="fas fa-undo me-1"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="theme-table-head">
                        <tr>
                            <th class="ps-3">Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th class="text-center">Ref #</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs->num_rows > 0): ?>
                            <?php while($row = $logs->fetch_assoc()): ?>
                            <?php
                                // Icon Logic
                                $icon = '<i class="fas fa-info-circle text-secondary log-icon"></i>';
                                if($row['action_type'] == 'status_change') $icon = '<i class="fas fa-sync-alt text-warning log-icon"></i>';
                                if($row['action_type'] == 'comment') $icon = '<i class="fas fa-comment-dots text-info log-icon"></i>';
                                if($row['action_type'] == 'login') $icon = '<i class="fas fa-sign-in-alt text-success log-icon"></i>';
                                
                                // Role Badge
                                $role_badge = ($row['role'] == 'admin' || $row['role'] == 'super_admin') 
                                    ? '<span class="badge bg-danger bg-opacity-75 ms-2" style="font-size:0.65rem;">ADMIN</span>' 
                                    : '<span class="badge bg-secondary bg-opacity-75 ms-2" style="font-size:0.65rem;">USER</span>';
                            ?>
                            <tr>
                                <td class="text-muted small ps-3" style="min-width: 140px;">
                                    <span class="fw-bold text-dark"><?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                    <br>
                                    <small><?= date('h:i A', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong class="text-dark"><?= htmlspecialchars($row['username']) ?></strong>
                                            <?= $role_badge ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        <?= $icon ?> 
                                        <?= ucfirst(str_replace('_', ' ', $row['action_type'])) ?>
                                    </div>
                                </td>
                                <td class="text-muted small fst-italic">
                                    "<?= htmlspecialchars(substr($row['message'], 0, 80)) . (strlen($row['message'])>80?'...':'') ?>"
                                </td>
                                <td class="text-center">
                                    <?php if($row['ticket_id']): ?>
                                        <a href="ticket_details.php?id=<?= $row['ticket_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-0 fw-bold" style="font-size: 0.8rem;">
                                            #<?= $row['ticket_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center p-5 text-muted">No activity logs found matching your criteria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&user=<?= $filter_user ?>&action=<?= $filter_action ?>">Previous</a>
                    </li>
                    
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&user=<?= $filter_user ?>&action=<?= $filter_action ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&user=<?= $filter_user ?>&action=<?= $filter_action ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>