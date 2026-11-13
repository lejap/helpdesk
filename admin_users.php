<?php
// ENABLE ERROR REPORTING
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';
checkLogin();

// 1. ACCESS CONTROL: Block Employees only.
if ($_SESSION['role'] == 'employee') { 
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>Access Denied.</div></div>";
    require 'includes/footer.php';
    exit(); 
}

$current_role = $_SESSION['role']; 
$msg = "";

// =================================================================
// DATABASE FIX
// =================================================================
try {
    $conn->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'employee'");
} catch (Exception $e) { /* Ignore */ }
// =================================================================

// --- HELPER FUNCTION ---
function canManageUser($target_role, $current_role) {
    if ($current_role == 'super_admin') return true; 
    if ($target_role == 'super_admin') return false; 
    return true; 
}

// --- HANDLE POST ACTIONS ---

// 1. Approve User
if (isset($_POST['approve_user'])) {
    $uid = intval($_POST['user_id']);
    $new_role = isset($_POST['role']) ? trim($_POST['role']) : 'employee';
    
    // Security Check
    if ($new_role == 'super_admin' && $current_role != 'super_admin') {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Security Alert: You cannot create Super Admins.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        // LOGIC: If Employee, force Dept to NULL
        if ($new_role == 'employee') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, role = ?, dept_id = NULL WHERE id = ?");
            $stmt->bind_param("si", $new_role, $uid);
        } else {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $uid);
        }
        $stmt->execute();
        $msg = "<div class='alert alert-success alert-dismissible fade show'>User approved successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 2. Reject User
if (isset($_POST['reject_user'])) {
    $uid = intval($_POST['user_id']);
    $conn->query("DELETE FROM users WHERE id = $uid");
    $msg = "<div class='alert alert-warning alert-dismissible fade show'>User request rejected.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

// 3. Update Existing User (SUPER ADMIN ONLY)
if (isset($_POST['update_role'])) {
    if ($current_role !== 'super_admin') {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Access Denied: Only Super Admin can update existing users.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $uid = intval($_POST['user_id']);
        
        $target_q = $conn->query("SELECT role FROM users WHERE id = $uid")->fetch_assoc();
        $existing_role = $target_q['role'];
        
        $new_role = (!empty($_POST['role'])) ? trim($_POST['role']) : $existing_role;
        $new_dept = intval($_POST['dept_id']); 
        
        // LOGIC: If Employee, force Dept to NULL
        if ($new_role == 'employee') {
            $dept_sql_val = NULL;
        } else {
            $dept_sql_val = ($new_dept === 0) ? NULL : $new_dept;
        }

        if ($uid == $_SESSION['user_id']) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>You cannot change your own role.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } 
        else {
            $stmt = $conn->prepare("UPDATE users SET role = ?, dept_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_role, $dept_sql_val, $uid);
            $stmt->execute();
            $msg = "<div class='alert alert-success alert-dismissible fade show'>User updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// 4. Delete User (SUPER ADMIN ONLY)
if (isset($_POST['delete_user'])) {
    if ($current_role !== 'super_admin') {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Access Denied: Only Super Admin can delete active users.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $uid = intval($_POST['user_id']);
        if ($uid == $_SESSION['user_id']) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>You cannot delete your own account.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $conn->query("DELETE FROM users WHERE id = $uid");
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>User account deleted.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH DATA ---
$dept_res = $conn->query("SELECT * FROM departments ORDER BY name ASC");
$dept_list = [];
while ($d = $dept_res->fetch_assoc()) {
    $dept_list[] = $d;
}

$pending_users = $conn->query("SELECT * FROM users WHERE is_active = 0");

// --- FILTER & SEARCH LOGIC ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_dept  = isset($_GET['filter_dept']) ? trim($_GET['filter_dept']) : '';
$filter_role  = isset($_GET['filter_role']) ? trim($_GET['filter_role']) : '';

// Base Query
$sql = "SELECT u.*, d.name as dept_name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.is_active = 1";

// Apply Filters
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql .= " AND u.username LIKE '%$safe_search%'";
}
if (!empty($filter_dept)) {
    $dept_id_clean = intval($filter_dept);
    $sql .= " AND u.dept_id = $dept_id_clean";
}
if (!empty($filter_role)) {
    $role_clean = $conn->real_escape_string($filter_role);
    $sql .= " AND u.role = '$role_clean'";
}

$sql .= " ORDER BY FIELD(u.role, 'super_admin', 'manager', 'staff', 'employee'), u.username ASC";
$active_users = $conn->query($sql);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
    .theme-header { background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); color: white !important; border-bottom: none; font-weight: 600; }
    .theme-table-head { background-color: #caf0f8 !important; color: #0077b6 !important; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .card-header { border-radius: 12px 12px 0 0 !important; padding: 1rem 1.25rem; }
    .btn-primary { background: #0077b6; border-color: #0077b6; }
    .btn-primary:hover { background: #023e8a; border-color: #023e8a; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold" style="color: #0077b6;">User Management</h3>
        <span class="badge bg-light text-primary border shadow-sm px-3 py-2 fs-6">
            <i class="fas fa-user-shield me-1"></i> Logged in as: <?= ucfirst(str_replace('_', ' ', $current_role)) ?>
        </span>
    </div>

    <?= $msg ?>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fs-6 fw-bold text-white"><i class="fas fa-user-clock me-2"></i>Pending Approvals</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($pending_users->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="theme-table-head">
                        <tr>
                            <th class="ps-3">Name</th>
                            <th>Email</th>
                            <th>Assign Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $pending_users->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold text-dark ps-3"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form method="POST" id="form-approve-<?= $u['id'] ?>" class="d-flex">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm border-secondary" style="width: auto;">
                                        <option value="employee">Employee</option>
                                        <option value="staff">Staff</option>
                                        <option value="manager">Manager</option>
                                        <?php if($current_role == 'super_admin'): ?>
                                            <option value="super_admin">Super Admin</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <button type="submit" name="approve_user" form="form-approve-<?= $u['id'] ?>" class="btn btn-success btn-sm fw-bold me-1">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Reject user?');">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="reject_user" class="btn btn-danger btn-sm fw-bold">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-25"></i>
                    <p class="mb-0">No pending approvals.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body py-3 bg-light rounded">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-auto">
                    <span class="fw-bold text-secondary"><i class="fas fa-filter me-1"></i> Filter:</span>
                </div>
                
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="filter_dept" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        <?php foreach ($dept_list as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($filter_dept == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="filter_role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        <option value="super_admin" <?= ($filter_role == 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                        <option value="manager" <?= ($filter_role == 'manager') ? 'selected' : '' ?>>Manager</option>
                        <option value="staff" <?= ($filter_role == 'staff') ? 'selected' : '' ?>>Staff</option>
                        <option value="employee" <?= ($filter_role == 'employee') ? 'selected' : '' ?>>Employee</option>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Apply</button>
                    <a href="admin_users.php" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fs-6 fw-bold text-white"><i class="fas fa-users me-2"></i>Active Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="theme-table-head">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_users->num_rows > 0): ?>
                            <?php while($u = $active_users->fetch_assoc()): ?>
                            <?php 
                                $is_self = ($u['id'] == $_SESSION['user_id']);
                                $can_edit = !$is_self && ($current_role === 'super_admin');
                            ?>
                            
                            <tr class="<?= ($u['role'] == 'super_admin') ? 'bg-warning bg-opacity-10' : '' ?>">
                                <td class="text-muted small fw-bold ps-3">#<?= $u['id'] ?></td>
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($u['username']) ?></span>
                                    <?php if($is_self): ?> 
                                        <span class="badge bg-info text-dark ms-2">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                                
                                <?php if($can_edit): ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        
                                        <td>
                                            <select name="dept_id" class="form-select form-select-sm border-secondary dept-select" style="min-width: 140px;">
                                                <option value="0" class="text-muted">-- None --</option>
                                                <?php foreach ($dept_list as $dept): ?>
                                                    <option value="<?= $dept['id'] ?>" <?= ($u['dept_id'] == $dept['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($dept['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <select name="role" class="form-select form-select-sm border-secondary me-2 role-select" 
                                                        style="width: auto;" onchange="toggleDept(this)">
                                                    <option value="employee" <?= $u['role'] == 'employee' ? 'selected' : '' ?>>Employee</option>
                                                    <option value="staff" <?= $u['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                                                    <option value="manager" <?= $u['role'] == 'manager' ? 'selected' : '' ?>>Manager</option>
                                                    <option value="super_admin" <?= $u['role'] == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                                </select>
                                                
                                                <button type="submit" name="update_role" class="btn btn-primary btn-sm me-1" title="Save Changes">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                
                                                <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete user?');" title="Delete User">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </form>
                                
                                <?php else: ?>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= !empty($u['dept_name']) ? htmlspecialchars($u['dept_name']) : 'None' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge_cls = 'secondary';
                                            if($u['role']=='super_admin') $badge_cls = 'danger';
                                            if($u['role']=='manager') $badge_cls = 'warning text-dark';
                                            if($u['role']=='staff') $badge_cls = 'primary';
                                            if($u['role']=='employee') $badge_cls = 'info text-dark';
                                        ?>
                                        <span class="badge bg-<?= $badge_cls ?>"><?= ucwords(str_replace('_', ' ', $u['role'])) ?></span>
                                        <?php if($current_role != 'super_admin'): ?>
                                            <i class="fas fa-lock text-muted ms-2" title="Only Super Admin can edit"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 text-secondary opacity-25"></i>
                                    <p class="mb-0">No users found matching your filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDept(selectObj) {
    let row = selectObj.closest('tr');
    let deptSelect = row.querySelector('.dept-select');
    
    if (selectObj.value === 'employee') {
        deptSelect.value = '0'; 
        deptSelect.setAttribute('disabled', 'disabled'); 
    } else {
        deptSelect.removeAttribute('disabled');
    }
}
document.addEventListener("DOMContentLoaded", function() {
    let roleSelects = document.querySelectorAll('.role-select');
    roleSelects.forEach(function(select) {
        toggleDept(select);
    });
});
</script>

<?php require 'includes/footer.php'; ?>