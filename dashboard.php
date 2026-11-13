<?php
// --- 1. CONFIG & LOGIN CHECK (MUST BE FIRST) ---
require 'config/db.php';
// Do not include header/navbar yet!
checkLogin(); 

// --- 2. HANDLE REJECT ACTION (LOGIC BEFORE HTML) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_ticket_id'])) {
    // Security: Only Managers or Super Admins can reject
    if ($_SESSION['role'] == 'manager' || $_SESSION['role'] == 'super_admin') {
        $rej_id = intval($_POST['reject_ticket_id']);
        $rej_remarks = cleanInput($_POST['reject_remarks']); // Capture Remarks
        $current_uid = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        
        // Update status to 'Rejected' AND save remarks
        $update_stmt = $conn->prepare("UPDATE tickets SET status = 'Rejected', resolution_remarks = ? WHERE id = ?");
        $update_stmt->bind_param("si", $rej_remarks, $rej_id);
        
        if ($update_stmt->execute()) {
            // Log the rejection
            $log_msg = "Ticket Rejected by " . $username . ". Reason: " . $rej_remarks;
            $conn->query("INSERT INTO ticket_logs (ticket_id, user_id, action_type, message) VALUES ($rej_id, $current_uid, 'status_change', '$log_msg')");
            
            header("Location: dashboard.php?msg=rejected");
            exit();
        }
    }
}

// --- 3. INCLUDE HTML HEADER & NAVBAR ---
require 'includes/header.php';
require 'includes/navbar.php';

// --- 4. PREPARE FILTERS ---
$where = [];
$params = [];
$types = "";

if ($_SESSION['role'] == 'employee') {
    $where[] = "t.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
}

$show_my_tickets = isset($_GET['my_tickets']) ? true : false;

if ($show_my_tickets && $_SESSION['role'] != 'employee') {
    $where[] = "t.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
}

if (!empty($_GET['status'])) { 
    $where[] = "t.status = ?"; 
    $params[] = $_GET['status']; 
    $types .= "s"; 
}
if (!empty($_GET['priority'])) { 
    $where[] = "t.priority = ?"; 
    $params[] = $_GET['priority']; 
    $types .= "s"; 
}
if (!empty($_GET['branch']) && $_SESSION['role'] != 'employee') {
    $where[] = "u.branch_id = ?";
    $params[] = $_GET['branch'];
    $types .= "i";
}
if (!empty($_GET['requester']) && $_SESSION['role'] != 'employee') { 
    $where[] = "u.username LIKE ?"; 
    $params[] = "%" . $_GET['requester'] . "%"; 
    $types .= "s"; 
}

// --- 5. BUILD SQL QUERY ---
$sql = "SELECT t.*, u.username as requester_name, b.name as branch_name, a.username as assigned_name, d.name as target_dept_name 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN branch b ON u.branch_id = b.id
        LEFT JOIN users a ON t.assigned_to = a.id
        LEFT JOIN departments d ON t.dept_id = d.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("<div class='alert alert-danger'>Database Error: " . $conn->error . "</div>"); }

if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$tickets = $stmt->get_result();

// --- 6. FETCH STATS ---
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='Open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status='Rejected' THEN 1 ELSE 0 END) as rejected
    FROM tickets";

if ($_SESSION['role'] == 'employee') { $stats_sql .= " WHERE user_id=" . $_SESSION['user_id']; }
$stats = $conn->query($stats_sql)->fetch_assoc();

// --- 7. FETCH BRANCHES ---
$branches = $conn->query("SELECT * FROM branch ORDER BY name ASC");

// --- 8. FETCH USER DEPARTMENT ---
$user_department_name = "";
if ($_SESSION['role'] == 'super_admin') {
    $user_department_name = "Super Admin";
} else {
    $dept_uid = $_SESSION['user_id'];
    $dept_q = $conn->query("SELECT d.name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.id = $dept_uid");
    if ($dept_q && $d_row = $dept_q->fetch_assoc()) { $user_department_name = $d_row['name']; }
}
?>

<style>
    .theme-header { background: linear-gradient(90deg, #0077b6 0%, #00b4d8 100%); color: white; border-bottom: none; font-weight: 600; }
    .theme-table-head { background-color: #caf0f8 !important; color: #0077b6 !important; }
    .stat-card { border-left: 5px solid transparent; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-total { border-left-color: #00b4d8; }
    .stat-open { border-left-color: #dc3545; }
    .stat-progress { border-left-color: #ffc107; }
    .stat-resolved { border-left-color: #198754; }
    .stat-rejected { border-left-color: #6c757d; }
    .stat-icon { opacity: 0.2; font-size: 2.5rem; position: absolute; right: 20px; top: 20px; }
    .animate-pulse { animation: pulse-soft 2s infinite; }
    @keyframes pulse-soft { 
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); } 
        70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); } 
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); } 
    }
</style>

<div class="container-fluid mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 style="color: #0077b6; font-weight: 700;">Dashboard</h3>
            <?php if(!empty($user_department_name) && $_SESSION['role'] != 'employee'): ?>
                <span class="badge bg-secondary opacity-75 fs-6">
                    <i class="fas fa-building me-1"></i> <?= htmlspecialchars($user_department_name) ?> Department
                </span>
            <?php endif; ?>
        </div>
        <a href="ticket_create.php" class="btn btn-primary shadow-sm"><i class="fas fa-plus"></i> New Ticket</a>
    </div>

    <div class="row row-cols-1 row-cols-md-5 mb-4 g-3">
        <div class="col">
            <div class="card stat-card stat-total shadow-sm h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Total</h6>
                    <h2 class="display-5 fw-bold text-dark"><?= $stats['total'] ?? 0 ?></h2>
                    <i class="fas fa-ticket-alt stat-icon text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card stat-open shadow-sm h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Open</h6>
                    <h2 class="display-5 fw-bold text-danger"><?= $stats['open'] ?? 0 ?></h2>
                    <i class="fas fa-exclamation-circle stat-icon text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card stat-progress shadow-sm h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Processing</h6>
                    <h2 class="display-5 fw-bold text-warning"><?= $stats['in_progress'] ?? 0 ?></h2>
                    <i class="fas fa-spinner stat-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card stat-resolved shadow-sm h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Done/Closed</h6>
                    <h2 class="display-5 fw-bold text-success"><?= $stats['resolved'] ?? 0 ?></h2>
                    <?php
                    $current_uid = $_SESSION['user_id'];
                    $rate_check = $conn->query("SELECT id FROM tickets WHERE status = 'Resolved' AND user_id = $current_uid");
                    if ($rate_check->num_rows > 0): ?>
                        <div class="mt-2">
                            <a href="ticket_details.php?id=<?= $rate_check->fetch_assoc()['id'] ?>" 
                               class="badge rounded-pill bg-warning text-dark text-decoration-none border border-warning animate-pulse px-3 py-2">
                                <i class="fas fa-star me-1"></i> Rate Service Now
                            </a>
                        </div>
                    <?php endif; ?>
                    <i class="fas fa-check-circle stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card stat-card stat-rejected shadow-sm h-100">
                <div class="card-body position-relative">
                    <h6 class="text-muted text-uppercase small fw-bold">Rejected</h6>
                    <h2 class="display-5 fw-bold text-secondary"><?= $stats['rejected'] ?? 0 ?></h2>
                    <i class="fas fa-ban stat-icon text-secondary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4 shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">STATUS</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                 <label class="form-label small fw-bold text-muted">PRIORITY</label>
                 <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <?php if($_SESSION['role'] != 'employee'): ?>
            <div class="col-md-2">
                 <label class="form-label small fw-bold text-muted">BRANCH</label>
                 <select name="branch" class="form-select">
                    <option value="">All Branches</option>
                    <?php while($br = $branches->fetch_assoc()): ?>
                        <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">SEARCH REQUESTER</label>
                <input type="text" name="requester" class="form-control" placeholder="Type Name...">
            </div>
            <div class="col-md-2">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="my_tickets" value="1" id="myTicketsCheck" <?= $show_my_tickets ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold" for="myTicketsCheck">
                        My Filed Tickets Only
                    </label>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="card shadow-sm">
        <div class="card-header theme-header">
            <i class="fas fa-list me-2"></i> Recent Tickets
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="theme-table-head">
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Requester</th>
                            <th>Department</th> <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tickets->num_rows > 0): ?>
                            <?php while($row = $tickets->fetch_assoc()): ?>
                            <?php 
                                $badgeClass = 'secondary';
                                switch ($row['status']) {
                                    case 'Open': $badgeClass = 'danger'; break;
                                    case 'In Progress': $badgeClass = 'warning'; break;
                                    case 'Resolved': $badgeClass = 'success'; break;
                                    case 'Closed': $badgeClass = 'dark'; break;
                                    case 'Rejected': $badgeClass = 'secondary'; break;
                                }
                                $priorityClass = 'info';
                                $p_icon = '';
                                if ($row['priority'] == 'High') { $priorityClass = 'danger'; $p_icon='<i class="fas fa-fire me-1"></i>'; }
                                if ($row['priority'] == 'Medium') { $priorityClass = 'warning'; }
                            ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $row['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['subject']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['requester_name']) ?>
                                    <br><small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['branch_name'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?php if(!empty($row['target_dept_name'])): ?>
                                        <span class="badge bg-secondary opacity-75"><?= htmlspecialchars($row['target_dept_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['assigned_name']): ?>
                                        <span class="badge bg-light text-dark border"><i class="fas fa-user-shield me-1"></i> <?= htmlspecialchars($row['assigned_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $priorityClass ?> bg-opacity-75"><?= $p_icon ?><?= $row['priority'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= $row['status'] ?></span>
                                    <?php if ($row['status'] === 'Resolved'): ?>
                                        <div class="mt-1">
                                            <a href="ticket_details.php?id=<?= $row['id'] ?>" class="text-decoration-none">
                                                <span class="badge rounded-pill bg-warning text-dark animate-pulse" style="font-size: 0.65rem;">
                                                    <i class="fas fa-star"></i> NEED TO RATE!!
                                                </span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>       
                                <td class="small text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="ticket_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
                                        <?php if(($_SESSION['role'] == 'manager' || $_SESSION['role'] == 'super_admin') && !in_array($row['status'], ['In Progress', 'Resolved', 'Closed', 'Rejected'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                                    onclick="openRejectModal(<?= $row['id'] ?>)">
                                                Reject
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted">No tickets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white"><i class="fas fa-ban me-2"></i>Reject Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reject_ticket_id" id="modalRejectId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="reject_remarks" class="form-control" rows="4" placeholder="Please explain why this ticket is being rejected..." required></textarea>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-1"></i> This action cannot be undone. The ticket will be closed immediately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// --- HAPPY ZONE (ADMIN ONLY) ---
$current_user_id = $_SESSION['user_id'];
$admin_check = $conn->query("SELECT email FROM users WHERE id = $current_user_id");
$is_super_admin = false;

if ($admin_check && $admin_row = $admin_check->fetch_assoc()) {
    if ($admin_row['email'] === 'admin@gmail.com') {
        $is_super_admin = true;
    }
}

if ($is_super_admin): 
?>
    <div class="container-fluid mt-5 mb-4">
        <div class="card border-danger shadow-sm" style="background: rgba(255, 200, 200, 0.2); border: 1px solid #dc3545;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="text-danger fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Happy Zone</h5>
                    <p class="text-muted small mb-0">Dont click the SMILE Button <strong>HEHEHEHEHHEE</strong>.</p>
                </div>
                
                <form id="happyZoneForm" action="reset_system.php" method="POST">
                    <input type="hidden" name="confirm_wipe" value="1">
                    <button type="button" 
                            class="btn btn-danger rounded-circle shadow-lg d-flex align-items-center justify-content-center" 
                            style="width: 50px; height: 50px; padding: 0;" 
                            onclick="verifyHappyZone()">
                        <i class="fas fa-smile fa-lg"></i>
                    </button>
                </form>

            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    // Reject Modal Logic
    function openRejectModal(ticketId) {
        document.getElementById('modalRejectId').value = ticketId;
        var myModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        myModal.show();
    }

    // Happy Zone Security Logic
    function verifyHappyZone() {
        // 1. Prompt for Code
        let code = prompt("ENTER 6-DIGIT SECURITY CODE:");
        
        // 2. Check Code
        if (code === "661766") {
            // 3. Confirm Final Action
            if(confirm("CRITICAL WARNING: SYSTEM RESET.\n\nAre you absolutely sure? This cannot be undone.")) {
                document.getElementById('happyZoneForm').submit();
            }
        } else {
            // 4. Deny Access
            if (code !== null) { // Only alert if user didn't hit Cancel
                alert("INCORRECT CODE! Access Denied.");
            }
        }
    }
</script>

<?php require 'includes/footer.php'; ?>