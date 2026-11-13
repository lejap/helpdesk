<?php
require 'config/db.php';
// 1. Check Login FIRST
checkLogin(); 

// --- Require Email Helper ---
require 'includes/email_helper.php'; 

$msg = "";

// 2. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // SECURITY FIX: Ensure we trim whitespace, but we rely on Prepared Statements for SQL safety
    // and htmlspecialchars for Output safety.
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category_name = isset($_POST['category']) ? trim($_POST['category']) : '';
    $dept_id = intval($_POST['dept_id']);
    $user_id = $_SESSION['user_id'];
    
    // AUTO-GENERATE SUBJECT
    $subject = $category_name . " Support Request";

    // Determine Priority based on Category (Already Secure: Prepared Statement)
    $cat_stmt = $conn->prepare("SELECT default_priority FROM categories WHERE name = ?");
    $cat_stmt->bind_param("s", $category_name);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    
    if ($cat_result->num_rows > 0) {
        $priority = $cat_result->fetch_assoc()['default_priority'];
    } else {
        $priority = 'Low'; 
    }
    $cat_stmt->close(); // Good practice to close statements

    // Determine Priority Color
    $p_color = '#6c757d'; 
    if ($priority === 'High') { $p_color = '#dc3545'; } 
    if ($priority === 'Medium') { $p_color = '#fd7e14'; } 
    if ($priority === 'Low') { $p_color = '#198754'; } 

    // File Upload Handling
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        // SECURITY FIX: Define Allowed MIME Types to prevent renamed malicious files
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $filename = $_FILES['attachment']['name'];
        $tmp_name = $_FILES['attachment']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check Extension AND Mime Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($tmp_name);

        // Verify Extension is allowed and MIME type matches extension
        if (in_array($ext, $allowed_exts) && isset($allowed_mimes[$ext]) && 
            (strpos($mime_type, $allowed_mimes[$ext]) !== false || 
            ($ext == 'docx' && strpos($mime_type, 'word') !== false) || // Loose check for docx variations
            ($ext == 'doc' && strpos($mime_type, 'msword') !== false))) {
            
            $new_name = uniqid() . "." . $ext;
            if (move_uploaded_file($tmp_name, "uploads/" . $new_name)) {
                $attachment = $new_name;
            }
        } else {
            $msg = "<div class='alert alert-danger'>Invalid file type or corrupted file. Allowed: jpg, png, pdf, doc</div>";
        }
    }

    if (empty($msg)) {
        // --- FORCE STATUS 'Open' HERE ---
        // (Already Secure: Prepared Statement)
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, dept_id, subject, description, priority, category, status, attachment) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?)");
        $stmt->bind_param("iisssss", $user_id, $dept_id, $subject, $description, $priority, $category_name, $attachment);
        
        if ($stmt->execute()) {
            $new_ticket_id = $conn->insert_id;
            $stmt->close();

            // SECURITY FIX: Sanitize variables for HTML Email Output
            $safe_subject = htmlspecialchars($subject);
            $safe_category = htmlspecialchars($category_name);
            $safe_desc = nl2br(htmlspecialchars($description)); // Convert newlines to <br> safely

            // --- 1. NOTIFY THE SUBMITTING USER ---
            // SECURITY FIX: Changed raw query to Prepared Statement
            $u_stmt = $conn->prepare("SELECT email, username FROM users WHERE id = ?");
            $u_stmt->bind_param("i", $user_id);
            $u_stmt->execute();
            $u_res = $u_stmt->get_result();

            if ($u_res && $u_data = $u_res->fetch_assoc()) {
                $to = $u_data['email'];
                $name = $u_data['username']; // Assumed safe from DB, but htmlspecialchars below is safer
                $subj = "Ticket Received: $safe_subject";
                
                $email_body = "
                    <div style='font-family: Arial, sans-serif; color: #333;'>
                        <p>Hello " . htmlspecialchars($name) . ",</p>
                        <p>This is to notify you that we have received your ticket. Your ticket number for this concern is <strong>#$new_ticket_id</strong>.</p>
                        <p><strong>Type:</strong> $safe_category</p>
                        <p><strong>Priority:</strong> <span style='color: $p_color; font-weight: bold;'>$priority</span></p>
                        <p>Our support team will review it shortly.</p>
                    </div>";
                
                sendEmailNotification($to, $name, $subj, $email_body);
            }
            $u_stmt->close();

            // --- 2. NOTIFY MANAGER & STAFF OF THE TARGET DEPARTMENT ---
            // SECURITY FIX: Changed raw query to Prepared Statement
            $role_manager = 'manager';
            $role_staff = 'staff';
            
            $notify_stmt = $conn->prepare("
                SELECT email, username, role 
                FROM users 
                WHERE dept_id = ? 
                AND (role = ? OR role = ?)
            ");
            $notify_stmt->bind_param("iss", $dept_id, $role_manager, $role_staff);
            $notify_stmt->execute();
            $notify_res = $notify_stmt->get_result();

            if ($notify_res && $notify_res->num_rows > 0) {
                $staff_subj = "Action Required: New Ticket #$new_ticket_id Filed";
                $base_url = "http://localhost/it_ticketing"; 

                while ($staff = $notify_res->fetch_assoc()) {
                    $staff_email = $staff['email'];
                    $staff_name = htmlspecialchars($staff['username']);
                    $staff_role = ucwords(str_replace('_', ' ', $staff['role']));

                    $staff_body = "
                        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                            <h2 style='color: #0077b6;'>New Department Ticket</h2>
                            <p>Hello <strong>$staff_name</strong> ($staff_role),</p>
                            <p>A new ticket has been assigned to your department. Please check it as soon as possible.</p>
                            <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #0077b6; border-radius: 4px;'>
                                <strong>Ticket ID:</strong> #$new_ticket_id<br>
                                <strong>Category:</strong> $safe_category<br>
                                <strong>Priority:</strong> <span style='background-color: $p_color; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.9em; font-weight: bold;'>$priority</span><br>
                                <strong>Description:</strong> $safe_desc
                            </div>
                           
                        </div>
                    ";

                    sendEmailNotification($staff_email, $staff['username'], $staff_subj, $staff_body);
                }
            }
            $notify_stmt->close();

            header("Location: dashboard.php");
            exit();
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>"; // Note: In production, show generic error
        }
    }
}

// 3. FETCH DATA (Departments and Categories)
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

require 'includes/header.php';
require 'includes/navbar.php';
?>

<style>
    .theme-header {
        background: linear-gradient(90deg, #023e8a 0%, #0077b6 100%);
        color: white !important; 
        border-bottom: none;
        font-weight: 700;
        border-radius: 15px 15px 0 0;
        text-shadow: 0px 2px 4px rgba(0,0,0,0.3);
    }
    .form-label {
        font-weight: 600;
        color: #023e8a;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-disabled {
        background-color: #6c757d !important;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header theme-header py-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-plus-circle me-2"></i>Create New Ticket</h5>
                </div>
                <div class="card-body p-4">
                    <?= $msg ?>
                    <form method="POST" enctype="multipart/form-data" id="createTicketForm">
                        
                        <div class="mb-4">
                            <label class="form-label text-primary">Target Department</label>
                            <select name="dept_id" id="deptSelect" class="form-select border-primary" required>
                                <option value="">Which department do you need help from?</option>
                                <?php while($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category" id="categorySelect" class="form-select" required disabled>
                                    <option value="" data-priority="">-- Select Department First --</option>
                                    <?php 
                                    $categories->data_seek(0); 
                                    while($cat = $categories->fetch_assoc()): 
                                    ?>
                                        <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                data-priority="<?= $cat['default_priority'] ?>"
                                                data-dept-id="<?= $cat['dept_id'] ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Priority Level</label>
                                <input type="text" id="priorityDisplay" class="form-control bg-light fw-bold text-muted" value="-" readonly style="border-style: dashed;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="5" required placeholder="Please describe the issue in detail..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" name="attachment" class="form-control">
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Allowed: JPG, PNG, PDF, DOC</small>
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="background-color: #0077b6; border: none;">
                            <i class="fas fa-paper-plane me-2"></i> Submit Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('deptSelect').addEventListener('change', function() {
        var selectedDeptId = this.value;
        var categorySelect = document.getElementById('categorySelect');
        var options = categorySelect.getElementsByTagName('option');
        
        categorySelect.value = "";
        document.getElementById('priorityDisplay').value = "-";
        
        if (selectedDeptId) {
            categorySelect.disabled = false;
            var hasVisibleOptions = false;
            
            for (var i = 0; i < options.length; i++) {
                var opt = options[i];
                var catDeptId = opt.getAttribute('data-dept-id');

                if (opt.value === "") {
                    opt.innerHTML = "Select Category...";
                    opt.hidden = false;
                    continue; 
                }

                if (catDeptId == selectedDeptId) {
                    opt.hidden = false;
                    hasVisibleOptions = true;
                } else {
                    opt.hidden = true;
                }
            }
            if(!hasVisibleOptions) categorySelect.options[0].innerHTML = "No Categories Found";
        } else {
            categorySelect.disabled = true;
            categorySelect.options[0].innerHTML = "-- Select Department First --";
        }
    });

    document.getElementById('categorySelect').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var priority = selectedOption.getAttribute('data-priority');
        var displayBox = document.getElementById('priorityDisplay');
        
        if (priority) {
            displayBox.value = priority;
            displayBox.classList.remove('text-success', 'text-warning', 'text-danger', 'text-muted');
            displayBox.style.borderStyle = "solid";
            
            if(priority === 'Low') { displayBox.classList.add('text-success'); displayBox.style.borderColor = "#198754"; }
            if(priority === 'Medium') { displayBox.classList.add('text-warning'); displayBox.style.borderColor = "#ffc107"; }
            if(priority === 'High') { displayBox.classList.add('text-danger'); displayBox.style.borderColor = "#dc3545"; }
        } else {
            displayBox.value = "-";
            displayBox.classList.add('text-muted');
            displayBox.style.borderStyle = "dashed";
        }
    });

    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        var btn = document.getElementById('submitBtn');
        if (this.checkValidity()) {
            btn.disabled = true;
            btn.classList.add('btn-disabled');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending Ticket To Designated Department, Please Wait!';
        }
    });
</script>

<?php require 'includes/footer.php'; ?>