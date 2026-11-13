<?php
require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';
checkLogin();

// 1. SECURITY: Block Staff and Employees
if ($_SESSION['role'] != 'super_admin' && $_SESSION['role'] != 'manager') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require 'includes/footer.php';
    exit();
}

$current_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$manager_dept_id = 0;
$manager_dept_name = "Unknown";

// 2. IF MANAGER, GET THEIR DEPARTMENT NAME AND ID
if ($current_role == 'manager') {
    // Join with departments table to get the name
    $d_check = $conn->query("SELECT u.dept_id, d.name as dept_name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.id = $user_id");
    $row = $d_check->fetch_assoc();
    
    $manager_dept_id = intval($row['dept_id']);
    $manager_dept_name = $row['dept_name'];

    if ($manager_dept_id == 0) {
        echo "<div class='container mt-5'><div class='alert alert-warning'>Error: You are a Manager but not assigned to a Department. Contact Super Admin.</div></div>";
        require 'includes/footer.php';
        exit();
    }
}

$msg = "";
$edit_mode = false;
$edit_data = ['name' => '', 'default_priority' => 'Medium', 'id' => '', 'dept_id' => ''];

// --- HANDLE ACTIONS ---

// SAVE CATEGORY
if (isset($_POST['save_category'])) {
    $name = cleanInput($_POST['name']);
    $priority = cleanInput($_POST['default_priority']);
    
    // Determine Department: Super Admin picks from list, Manager uses their own
    if ($current_role == 'super_admin') {
        $target_dept = intval($_POST['dept_id']);
    } else {
        $target_dept = $manager_dept_id;
    }

    if (!empty($_POST['category_id'])) {
        // UPDATE
        $id = intval($_POST['category_id']);
        
        // Security: Manager can only update their own
        $check = ($current_role == 'manager') ? "AND dept_id = $manager_dept_id" : "";
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, default_priority = ?, dept_id = ? WHERE id = ? $check");
        $stmt->bind_param("ssii", $name, $priority, $target_dept, $id);
        if($stmt->execute() && $stmt->affected_rows > 0) {
            $msg = "Category updated!";
        } else {
            $msg = ($current_role == 'manager') ? "Error: Access Denied or No Change." : "Category updated!";
        }
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO categories (name, default_priority, dept_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $priority, $target_dept);
        $stmt->execute();
        $msg = "New category added!";
    }
}

// DELETE CATEGORY
if (isset($_POST['delete_category'])) {
    $id = intval($_POST['category_id']);
    // Security: Manager can only delete their own
    $check = ($current_role == 'manager') ? "AND dept_id = $manager_dept_id" : "";
    $conn->query("DELETE FROM categories WHERE id = $id $check");
    $msg = "Category deleted.";
}

// EDIT MODE LOAD
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    // Security: Manager can only edit their own
    $check = ($current_role == 'manager') ? "AND dept_id = $manager_dept_id" : "";
    $res = $conn->query("SELECT * FROM categories WHERE id = $id $check");
    if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
        $edit_mode = true;
    }
}

// --- FETCH DATA FOR DISPLAY ---

// 1. Get Departments for Dropdown (Super Admin Only)
$depts = $conn->query("SELECT * FROM departments ORDER BY name ASC");

// 2. Get Categories (Filtered)
if ($current_role == 'manager') {
    // Manager: ONLY see their department
    $sql = "SELECT c.*, d.name as dept_name FROM categories c 
            LEFT JOIN departments d ON c.dept_id = d.id 
            WHERE c.dept_id = $manager_dept_id 
            ORDER BY c.name ASC";
} else {
    // Super Admin: See ALL
    $sql = "SELECT c.*, d.name as dept_name FROM categories c 
            LEFT JOIN departments d ON c.dept_id = d.id 
            ORDER BY d.name ASC, c.name ASC";
}
$categories = $conn->query($sql);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
    .theme-header { background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); color: white; border-bottom: none; font-weight: 600; }
    .theme-table-head { background-color: #caf0f8 !important; color: #0077b6 !important; }
    .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .card-header { border-radius: 12px 12px 0 0 !important; padding: 1rem 1.25rem; }
    .btn-primary { background: #0077b6; border-color: #0077b6; }
    .btn-primary:hover { background: #023e8a; border-color: #023e8a; }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="fw-bold" style="color: #0077b6;">Category Manager</h3>
        <?php if($current_role == 'manager'): ?>
            <span class="badge bg-info text-dark fs-6 shadow-sm"><i class="fas fa-building me-1"></i> <?= htmlspecialchars($manager_dept_name) ?></span>
        <?php else: ?>
            <span class="badge bg-danger fs-6 shadow-sm">Super Admin Mode</span>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header theme-header">
                    <i class="fas fa-edit me-2"></i> <?= $edit_mode ? 'Edit Category' : 'Add New Category' ?>
                </div>
                <div class="card-body">
                    <?php if($msg): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= $msg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="category_id" value="<?= $edit_data['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Category Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_data['name']) ?>" required placeholder="e.g. Hardware Issue">
                        </div>
                        
                        <?php if ($current_role == 'super_admin'): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Department</label>
                                <select name="dept_id" class="form-select" required>
                                    <option value="">-- Select Dept --</option>
                                    <?php 
                                        $depts->data_seek(0); 
                                        while($d = $depts->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $d['id'] ?>" <?= ($edit_data['dept_id'] == $d['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Default Priority</label>
                            <select name="default_priority" class="form-select">
                                <option value="Low" <?= $edit_data['default_priority']=='Low'?'selected':'' ?>>Low</option>
                                <option value="Medium" <?= $edit_data['default_priority']=='Medium'?'selected':'' ?>>Medium</option>
                                <option value="High" <?= $edit_data['default_priority']=='High'?'selected':'' ?>>High</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="save_category" class="btn btn-primary w-100 fw-bold shadow-sm">
                            <?= $edit_mode ? 'Update Category' : 'Add Category' ?>
                        </button>
                        <?php if($edit_mode): ?>
                            <a href="admin_categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header theme-header">
                    <i class="fas fa-list me-2"></i> Category List
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="theme-table-head">
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Priority</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($row['dept_name'] ?? 'Unassigned') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $row['default_priority']=='High'?'danger':($row['default_priority']=='Medium'?'warning':'info') ?> bg-opacity-75">
                                            <?= $row['default_priority'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete category?');">
                                            <input type="hidden" name="category_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($categories->num_rows == 0): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No categories found for this department.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>