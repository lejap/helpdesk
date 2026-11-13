<?php
// --- 1. ENABLE ERROR REPORTING ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';
require 'includes/email_helper.php'; 

checkLogin();

if (!isset($_GET['id'])) { 
    echo "<script>window.location.href='dashboard.php';</script>"; 
    exit(); 
}
$ticket_id = intval($_GET['id']);

// --- FETCH DATA ---
$sql = "SELECT t.*, 
                u.username as requester_name, 
                u.email as requester_email, 
                u.phone as requester_phone, 
                a.username as assigned_name 
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN users a ON t.assigned_to = a.id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) die("Ticket not found.");

// --- PERMISSION & LOCK VARIABLES ---
$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$assigned_to = $ticket['assigned_to'];
$current_status = $ticket['status'];

// --- FIX: Handle Empty/Broken Status in Database ---
if (empty($current_status)) {
    $current_status = 'Open'; 
}

// 1. Employee Access Check
if ($user_role == 'employee' && $ticket['user_id'] != $current_user_id) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied</div></div>";
    require 'includes/footer.php';
    exit();
}

// 2. Define Allowed Next Steps (STRICT FLOW)
$allowed_transitions = [];
switch ($current_status) {
    case 'Open': 
        $allowed_transitions = ['In Progress']; 
        break;
    case 'In Progress': 
        $allowed_transitions = ['Resolved']; 
        break;
    default: 
        $allowed_transitions = []; 
        break;
}

// 3. LOCKING LOGIC
$can_update_status = false;
$lock_message = "";

if ($user_role == 'employee') {
    $can_update_status = false;
} 
elseif ($user_role == 'super_admin') {
    $can_update_status = true; 
} 
elseif (is_null($assigned_to) || $assigned_to == 0) {
    $can_update_status = true; 
} 
elseif ($assigned_to == $current_user_id) {
    $can_update_status = true; 
} 
else {
    $can_update_status = false; 
    $lock_message = "This ticket is currently being handled by: <strong>" . htmlspecialchars($ticket['assigned_name']) . "</strong>";
}

// --- HANDLE POST ACTIONS ---

// 1. Admin Update Status
if (isset($_POST['update_status']) && $can_update_status) {
    $new_status = $_POST['status'];
    $remarks = cleanInput($_POST['remarks']); 

    // IDEMPOTENCY CHECK
    if ($ticket['status'] === $new_status) {
        echo "<script>window.location.href='ticket_details.php?id=$ticket_id';</script>";
        exit();
    }

    if (($new_status == 'Resolved') && empty($remarks)) {
        echo "<script>alert('Please provide a Recommendation or Remark before resolving.');</script>";
    } 
    elseif (!in_array($new_status, $allowed_transitions) && $user_role != 'super_admin') {
        echo "<script>alert('Invalid status transition!');</script>";
    } else {
        $current_admin_id = $_SESSION['user_id'];
        
        $sql = "UPDATE tickets SET status = ?, assigned_to = ?, resolution_remarks = ?";
        if ($new_status == 'Resolved') {
            $sql .= ", resolved_at = NOW()";
        }
        $sql .= " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $new_status, $current_admin_id, $remarks, $ticket_id);
        
        if ($stmt->execute()) {
            $log_msg = "Changed status to " . $new_status . ". Remarks: " . $remarks;
            $log_stmt = $conn->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action_type, message) VALUES (?, ?, 'status_change', ?)");
            $log_stmt->bind_param("iis", $ticket_id, $current_admin_id, $log_msg);
            $log_stmt->execute();
            
            // Email Notification
            if (!empty($ticket['requester_email'])) {
                $to = $ticket['requester_email'];
                $name = $ticket['requester_name'];
                $subj = "Ticket Update: #$ticket_id - $new_status";
                
                $email_body = "Hello $name,<br><br>The status of your ticket <strong>#$ticket_id</strong> has been updated to: <span style='color: #0077b6; font-weight: bold;'>$new_status</span>.<br>";
                if (!empty($remarks)) { $email_body .= "<br><strong>Admin Remarks:</strong><br><em>" . nl2br($remarks) . "</em><br>"; }
                
                if ($new_status == 'Resolved') { 
                    $email_body .= "<br><br><strong style='color: #dc3545;'>ACTION REQUIRED:</strong><br>Please log in to confirm resolution and rate the service.<br>"; 
                }
                
                sendEmailNotification($to, $name, $subj, $email_body);
            }
        }
        echo "<script>window.location.href='ticket_details.php?id=$ticket_id';</script>";
        exit();
    }
}

// 2. Post Comment
if (isset($_POST['post_comment'])) {
    $msg = cleanInput($_POST['comment']);
    $stmt = $conn->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action_type, message) VALUES (?, ?, 'comment', ?)");
    $stmt->bind_param("iis", $ticket_id, $_SESSION['user_id'], $msg);
    $stmt->execute();
    echo "<script>window.location.href='ticket_details.php?id=$ticket_id';</script>";
    exit();
}

// 3. User Confirm
if (isset($_POST['confirm_resolution'])) {
    $rating = (int)$_POST['rating']; 
    $feedback = cleanInput($_POST['feedback']);
    $stmt = $conn->prepare("UPDATE tickets SET status = 'Closed', confirmed_at = NOW(), rating = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("isi", $rating, $feedback, $ticket_id);
    $stmt->execute();
    $conn->query("INSERT INTO ticket_logs (ticket_id, user_id, action_type, message) VALUES ($ticket_id, {$_SESSION['user_id']}, 'log', 'User confirmed resolution.')");
    echo "<script>window.location.href='ticket_details.php?id=$ticket_id';</script>";
    exit();
}

// Fetch Logs
$logs = $conn->query("SELECT l.*, u.username FROM ticket_logs l LEFT JOIN users u ON l.user_id = u.id WHERE ticket_id = $ticket_id ORDER BY created_at ASC");

// Badge Logic
$badgeClass = 'secondary';
switch($ticket['status']) {
    case 'Open': $badgeClass = 'danger'; break;
    case 'In Progress': $badgeClass = 'warning'; break;
    case 'Resolved': $badgeClass = 'success'; break;
    case 'Closed': $badgeClass = 'dark'; break;
    case 'Rejected': $badgeClass = 'secondary'; break;
}
?>

<style>
    .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 5px; }
    .star-rating input { display: none; }
    .star-rating label { font-size: 2.5rem; color: #ddd; cursor: pointer; transition: color 0.2s; }
    .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffc107; }
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0">#<?= $ticket['id'] ?>: <?= htmlspecialchars($ticket['subject']) ?></h5>
                    <span class="badge bg-<?= $badgeClass ?>"><?= !empty($ticket['status']) ? $ticket['status'] : 'Unknown' ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($ticket['resolution_remarks'])): ?>
                        <?php if($ticket['status'] == 'Rejected'): ?>
                            <div class="alert alert-danger border-danger shadow-sm">
                                <h6 class="fw-bold text-danger"><i class="fas fa-ban me-2"></i>Reason for Rejection:</h6>
                                <?= nl2br(htmlspecialchars($ticket['resolution_remarks'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info border-info shadow-sm">
                                <h6 class="fw-bold text-info"><i class="fas fa-clipboard-check me-2"></i>Admin Recommendation / Resolution:</h6>
                                <?= nl2br(htmlspecialchars($ticket['resolution_remarks'])) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <p class="text-muted mb-1">Description:</p>
                    <div class="alert alert-secondary border bg-light"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div>
                    
                    <?php if($ticket['attachment']): ?>
                        <p><strong>Attachment:</strong> <a href="uploads/<?= $ticket['attachment'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-paperclip"></i> View File</a></p>
                    <?php endif; ?>

                    <?php if($ticket['status'] == 'Resolved' && $ticket['user_id'] == $_SESSION['user_id']): ?>
                        <div class="alert alert-success mt-4 shadow-sm border-success">
                            <h4><i class="fas fa-check-circle"></i> Is your issue resolved?</h4>
                            <p>The admin has marked this ticket as resolved. Please confirm to close it.</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal">Yes, Confirm & Rate Service</button>
                        </div>
                    <?php endif; ?>

                     <?php if($ticket['status'] == 'Closed'): ?>
                        <div class="alert alert-info mt-3">
                            <strong>User Feedback:</strong> <?= htmlspecialchars($ticket['feedback']) ?> <br>
                            <strong class="text-warning" style="font-size: 1.2rem;"><?= str_repeat("★", $ticket['rating']) . str_repeat("☆", 5 - $ticket['rating']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white"><i class="fas fa-history"></i> Activity Timeline</div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                    
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                <i class="fas fa-door-open"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">System <small class="text-muted" style="font-size: 0.8em;"><?= date('M d, Y h:i A', strtotime($ticket['created_at'])) ?></small></h6>
                            <div class="p-2 mt-1 rounded bg-white border shadow-sm fw-bold text-success" style="display:inline-block;">
                                <span class="badge bg-danger">Open</span> Ticket Created
                            </div>
                        </div>
                    </div>

                    <?php if ($logs->num_rows > 0): ?>
                        <?php while($log = $logs->fetch_assoc()): ?>
                            <?php
                                // Badge formatting for logs
                                $raw_msg = $log['message'];
                                $is_rejection = (strpos($raw_msg, 'Changed status to Rejected') !== false);
                                
                                // Default styling
                                $bubble_class = "bg-white border text-dark"; 
                                
                                // RED BACKGROUND FOR REJECTION
                                if ($is_rejection) {
                                    $bubble_class = "bg-danger text-white border-danger";
                                }

                                $formatted_msg = htmlspecialchars($raw_msg);
                                
                                // --- HIDE REMARKS IF IN PROGRESS ---
                                if (strpos($formatted_msg, 'Changed status to In Progress') !== false) {
                                    $parts = explode('. Remarks:', $formatted_msg);
                                    $formatted_msg = $parts[0]; // Remove remarks for In Progress
                                }
                                // --- END HIDE LOGIC ---

                                // Apply badges only if NOT rejected (since rejection has red bg already)
                                if (!$is_rejection) {
                                    $formatted_msg = str_replace("Open", "<span class='badge bg-danger'>Open</span>", $formatted_msg);
                                    $formatted_msg = str_replace("In Progress", "<span class='badge bg-warning text-dark'>In Progress</span>", $formatted_msg);
                                    $formatted_msg = str_replace("Resolved", "<span class='badge bg-success'>Resolved</span>", $formatted_msg);
                                    $formatted_msg = str_replace("Closed", "<span class='badge bg-dark'>Closed</span>", $formatted_msg);
                                    $formatted_msg = str_replace("On Hold", "<span class='badge bg-info text-dark'>On Hold</span>", $formatted_msg);
                                } else {
                                    // For Rejection log, clean it up
                                    $formatted_msg = str_replace("Changed status to Rejected. Reason:", "<strong>REJECTED:</strong>", $formatted_msg);
                                }
                            ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($log['username'] ?? 'S', 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?= htmlspecialchars($log['username'] ?? 'System') ?> <small class="text-muted" style="font-size: 0.8em;"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></small></h6>
                                    <div class="p-2 mt-1 rounded shadow-sm <?= $bubble_class ?>" style="display:inline-block;">
                                        <?= nl2br($formatted_msg) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                
                <?php if($ticket['status'] != 'Closed' && $ticket['status'] != 'Rejected'): ?>
                <div class="card-footer bg-white">
                    <form method="POST" class="no-double-submit">
                        <div class="input-group">
                            <textarea name="comment" class="form-control" placeholder="Type a message..." required></textarea>
                            <button type="submit" name="post_comment" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-info text-white">Ticket Info</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Category:</strong> <?= htmlspecialchars($ticket['category']) ?></li>
                    <li class="list-group-item"><strong>Priority:</strong> <span class="badge bg-<?= $ticket['priority'] == 'High' ? 'danger' : ($ticket['priority'] == 'Medium' ? 'warning' : 'success') ?>"><?= $ticket['priority'] ?></span></li>
                    <li class="list-group-item"><strong>Assigned:</strong> <?= $ticket['assigned_name'] ?? '<span class="text-muted">Unassigned</span>' ?></li>
                    <li class="list-group-item"><strong>Created:</strong> <?= date('M d, Y', strtotime($ticket['created_at'])) ?></li>
                    
                    <li class="list-group-item bg-light"><strong>Requester Info:</strong></li>
                    <li class="list-group-item"><i class="fas fa-user me-2 text-muted"></i> <?= htmlspecialchars($ticket['requester_name']) ?></li>
                    <li class="list-group-item"><i class="fas fa-envelope me-2 text-muted"></i> <?= htmlspecialchars($ticket['requester_email']) ?></li>
                    <li class="list-group-item">
                        <i class="fas fa-phone me-2 text-muted"></i> 
                        <?= !empty($ticket['requester_phone']) ? htmlspecialchars($ticket['requester_phone']) : '<span class="text-muted">N/A</span>' ?>
                    </li>
                </ul>
            </div>

            <?php if($user_role != 'employee' && $ticket['status'] != 'Closed' && $ticket['status'] != 'Rejected'): ?>
                <?php if ($can_update_status): ?>
                    <div class="card shadow-sm border-primary">
                        <div class="card-header bg-primary text-white"><i class="fas fa-tools"></i> Admin Actions</div>
                        <div class="card-body">
                            <?php if (!empty($allowed_transitions)): ?>
                                <form method="POST" class="mb-3 no-double-submit">
                                    <label class="form-label fw-bold">Next Step</label>
                                    <select name="status" id="statusSelect" class="form-select mb-3">
                                        <option value="" disabled selected>Current: <?= !empty($current_status) ? $current_status : 'Unknown' ?></option>
                                        <?php foreach($allowed_transitions as $next_status): ?>
                                            <option value="<?= $next_status ?>"><?= $next_status ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Remarks / Recommendation</label>
                                        <textarea name="remarks" id="remarksField" class="form-control" rows="3" placeholder="Required if resolving..."></textarea>
                                        <small class="text-danger d-none" id="remarksAlert">* Required</small>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-warning w-100 fw-bold">Update Status</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-success text-center"><i class="fas fa-check-circle"></i> Ticket is <strong><?= $current_status ?></strong>.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white"><i class="fas fa-lock"></i> Locked</div>
                        <div class="card-body text-center text-danger">
                            <i class="fas fa-user-lock fa-3x mb-3"></i><br>
                            <?= $lock_message ?><br>
                            <small class="text-muted">Only the assigned staff member can update this ticket.</small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="no-double-submit">
                <div class="modal-header bg-success text-white"><h5 class="modal-title">Confirm & Rate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <p class="mb-3">Please rate the service provided:</p>
                    <div class="star-rating mb-3">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Excellent">★</label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Good">★</label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Average">★</label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Poor">★</label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Terrible">★</label>
                    </div>
                    <textarea name="feedback" class="form-control" placeholder="Write your feedback here..." rows="3" required></textarea>
                </div>
                <div class="modal-footer"><button type="submit" name="confirm_resolution" class="btn btn-success w-100">Submit Review & Close Ticket</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    var statusSelect = document.getElementById('statusSelect');
    if(statusSelect){
        statusSelect.addEventListener('change', function() {
            var status = this.value;
            var remarksField = document.getElementById('remarksField');
            var remarksAlert = document.getElementById('remarksAlert');
            if (status === 'Resolved') {
                remarksField.classList.add('border-danger');
                remarksAlert.classList.remove('d-none');
                remarksField.setAttribute('required', 'required');
            } else {
                remarksField.classList.remove('border-danger');
                remarksAlert.classList.add('d-none');
                remarksField.removeAttribute('required');
            }
        });
    }
    document.querySelectorAll('.no-double-submit').forEach(form => {
        form.addEventListener('submit', function(e) {
            var btn = this.querySelector('button[type="submit"]');
            if(btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                btn.style.opacity = '0.7';
                setTimeout(() => { btn.disabled = true; }, 10);
            }
        });
    });
    if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
</script>

<?php require 'includes/footer.php'; ?>