<?php
// ENABLE ERROR REPORTING
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';

// Check if user is Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require 'includes/footer.php';
    exit();
}

$message = "";
$edit_mode = false;
$edit_data = ['name' => '', 'id' => ''];

// --- 1. HANDLE ADD DEPARTMENT ---
if (isset($_POST['add_dept'])) {
    $dept_name = trim($_POST['dept_name']);
    
    if(!empty($dept_name)) {
        try {
            // Ensure table exists
            $conn->query("CREATE TABLE IF NOT EXISTS departments (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");

            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $dept_name);
            $stmt->execute();
            $message = "<div class='alert alert-success alert-dismissible fade show'>Department added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { 
                $message = "<div class='alert alert-warning alert-dismissible fade show'>Error: '$dept_name' already exists.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show'>Database Error: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    }
}

// --- 2. HANDLE UPDATE DEPARTMENT ---
if (isset($_POST['update_dept'])) {
    $dept_id = intval($_POST['dept_id']);
    $dept_name = trim($_POST['dept_name']);
    
    if(!empty($dept_name)) {
        try {
            $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $dept_name, $dept_id);
            $stmt->execute();
            $message = "<div class='alert alert-success alert-dismissible fade show'>Department updated!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $edit_mode = false;
            $edit_data = ['name' => '', 'id' => '']; // Reset form
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger alert-dismissible fade show'>Error: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- 3. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $dept_id = intval($_GET['delete']);
    
    // Check usage
    $check1 = $conn->query("SELECT id FROM users WHERE dept_id = $dept_id LIMIT 1");
    $check2 = $conn->query("SELECT id FROM tickets WHERE dept_id = $dept_id LIMIT 1");
    
    if ($check1->num_rows > 0 || $check2->num_rows > 0) {
        $message = "<div class='alert alert-warning alert-dismissible fade show'>Cannot delete: Department is in use.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $conn->query("DELETE FROM departments WHERE id = $dept_id");
        $message = "<div class='alert alert-success alert-dismissible fade show'>Department deleted.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// --- 4. HANDLE EDIT LOAD ---
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM departments WHERE id = $id");
    if($res->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $res->fetch_assoc();
    }
}

// FETCH ALL DEPARTMENTS
$depts = $conn->query("SELECT * FROM departments ORDER BY name ASC");
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
    <div class="d-flex align-items-center mb-4 gap-2">
        <h3 class="fw-bold" style="color: #0077b6;">Department Management</h3>
        <span class="badge bg-danger fs-6 shadow-sm">Super Admin</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header <?= $edit_mode ? 'bg-warning text-dark' : 'theme-header' ?>">
                    <h5 class="mb-0 fs-6 fw-bold <?= $edit_mode ? '' : 'text-white' ?>">
                        <?= $edit_mode ? '<i class="fas fa-edit me-2"></i> Edit Department' : '<i class="fas fa-plus me-2"></i> Add New Department' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    <form method="POST">
                        <?php if($edit_mode): ?>
                            <input type="hidden" name="dept_id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Department Name</label>
                            <input type="text" name="dept_name" class="form-control" 
                                   value="<?= htmlspecialchars($edit_data['name']) ?>" 
                                   placeholder="e.g. IT, HR, Finance" required>
                        </div>

                        <button type="submit" name="<?= $edit_mode ? 'update_dept' : 'add_dept' ?>" 
                                class="btn <?= $edit_mode ? 'btn-warning' : 'btn-primary' ?> w-100 fw-bold shadow-sm">
                            <?= $edit_mode ? 'Save Changes' : 'Save Department' ?>
                        </button>
                        
                        <?php if($edit_mode): ?>
                            <a href="admin_departments.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fs-6 fw-bold text-white"><i class="fas fa-list me-2"></i>Departments</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="theme-table-head">
                                <tr>
                                    <th class="ps-4">Department Name</th>
                                    <th class="text-center" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($depts && $depts->num_rows > 0): ?>
                                    <?php while($row = $depts->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                        
                                        <td class="text-center">
                                            <a href="admin_departments.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="admin_departments.php?delete=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this department?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center py-5 text-muted">No departments found. Use the form to add one.</td></tr>
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