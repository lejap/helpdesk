<?php
// 1. START PROCESSING BEFORE LOADING ANY HTML
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// --- 1. HANDLE CROPPED IMAGE UPLOAD (AJAX) ---
if (isset($_POST['cropped_image'])) {
    $data = $_POST['cropped_image'];
    $image_array_1 = explode(";", $data);
    $image_array_2 = explode(",", $image_array_1[1]);
    $data = base64_decode($image_array_2[1]);

    $new_name = "profile_" . $user_id . "_" . time() . ".png";
    $destination = "uploads/" . $new_name;

    if (file_put_contents($destination, $data)) {
        $conn->query("UPDATE users SET profile_image = '$new_name' WHERE id = $user_id");
        $msg = "<div class='alert alert-success alert-dismissible fade show'>Profile picture updated!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        header("Location: profile.php"); // Refresh to show new image immediately
        exit();
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Failed to save image.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// --- 2. HANDLE DETAILS UPDATE ---
if (isset($_POST['update_details'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->bind_param("si", $username, $user_id);
    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $msg = "<div class='alert alert-success alert-dismissible fade show'>Details updated successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// --- 3. HANDLE PASSWORD CHANGE ---
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $res = $conn->query("SELECT password FROM users WHERE id = $user_id");
    $row = $res->fetch_assoc();
    
    if (password_verify($current_pass, $row['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 8 && preg_match('/[A-Z]/', $new_pass) && preg_match('/[0-9]/', $new_pass)) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$new_hash' WHERE id = $user_id");
                $msg = "<div class='alert alert-success alert-dismissible fade show'>Password updated successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show'>Password does not meet requirements.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show'>New passwords do not match.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Current password incorrect.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$img_src = (!empty($user['profile_image']) && file_exists("uploads/" . $user['profile_image'])) ? "uploads/" . $user['profile_image'] : "assets/images/proflogo.png"; // Fallback image

require 'includes/header.php';
require 'includes/navbar.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
    
    .profile-header-bg {
        height: 150px;
        background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
        border-radius: 15px 15px 0 0;
        position: relative;
        margin-bottom: 80px; /* Space for the profile picture overlap */
    }

    /* Fixed Image Wrapper to prevent layout shift */
    .img-wrapper { 
        position: absolute; 
        bottom: -75px; 
        left: 50%; 
        transform: translateX(-50%); 
        width: 150px; 
        height: 150px; 
        z-index: 2;
    }

    .profile-img-container { 
        width: 100%; 
        height: 100%; 
        border-radius: 50%; 
        border: 5px solid white; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
        background-color: white; 
        overflow: hidden; 
        position: relative;
    }
    
    .profile-img { width: 100%; height: 100%; object-fit: cover; }
    
    .camera-btn { 
        position: absolute; 
        bottom: 5px; 
        right: 5px; 
        width: 38px; height: 38px; 
        background: #0077b6; color: white; 
        border-radius: 50%; border: 3px solid white; 
        display: flex; align-items: center; justify-content: center; 
        cursor: pointer; transition: 0.3s; z-index: 10;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .camera-btn:hover { background: #023e8a; transform: scale(1.1); }

    .theme-header { background: #fff; border-bottom: 1px solid #f0f0f0; padding: 1.25rem; }
    .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: visible; }
    
    .input-group-text { background: white; border-left: none; cursor: pointer; color: #6c757d; }
    .password-field { border-right: none; }
    .form-control:focus { border-color: #00b4d8; box-shadow: 0 0 0 0.2rem rgba(0, 180, 216, 0.25); }

    /* Checklist Animation */
    #checklistContainer { display: none; overflow: hidden; transition: all 0.3s ease-in-out; margin-top: 10px; }
    .req-list { list-style: none; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .req-item { color: #dc3545; font-size: 0.85rem; margin-bottom: 5px; transition: 0.3s; display: flex; align-items: center; }
    .req-item.valid { color: #198754; }
    .req-item i { margin-right: 8px; width: 16px; text-align: center; }
</style>

<div class="container mt-4 mb-5">
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="profile-header-bg">
                    <div class="img-wrapper">
                        <div class="profile-img-container">
                            <img src="<?= $img_src ?>" alt="Profile" class="profile-img" id="currentProfileImg">
                        </div>
                        <label for="fileUpload" class="camera-btn"><i class="fas fa-camera"></i></label>
                        <input type="file" id="fileUpload" accept="image/*" style="display: none;">
                    </div>
                </div>
                
                <div class="card-body text-center pt-2">
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['username']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <?php 
                        $badge_cls = 'secondary';
                        if($user['role']=='super_admin') $badge_cls = 'danger';
                        if($user['role']=='manager') $badge_cls = 'warning text-dark';
                        if($user['role']=='staff') $badge_cls = 'primary';
                        if($user['role']=='employee') $badge_cls = 'info text-dark';
                    ?>
                    <span class="badge bg-<?= $badge_cls ?> px-3 py-2 text-uppercase letter-spacing-1">
                        <?= str_replace('_', ' ', $user['role']) ?>
                    </span>
                    
                    <hr class="my-4 text-muted opacity-25">
                    
                    <div class="text-start px-3">
                        <small class="text-muted fw-bold text-uppercase">Account Info</small>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-secondary">Joined:</span>
                            <span class="fw-bold text-dark"><?= date("M d, Y", strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-secondary">Status:</span>
                            <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i> Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?= $msg ?>
            
            <div class="card mb-4">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold" style="color: #0077b6;"><i class="fas fa-user-edit me-2"></i>Edit Personal Details</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">USERNAME</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly title="Contact Admin to change email">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="update_details" class="btn btn-primary fw-bold px-4">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-lock me-2"></i>Security & Password</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="passwordForm">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">CURRENT PASSWORD</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control password-field" id="currPass" required placeholder="Enter current password">
                                <span class="input-group-text toggle-password" data-target="currPass"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">NEW PASSWORD</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" class="form-control password-field" id="newPass" required placeholder="New password">
                                    <span class="input-group-text toggle-password" data-target="newPass"><i class="fas fa-eye"></i></span>
                                </div>
                                <div id="checklistContainer">
                                    <ul class="req-list">
                                        <li id="req-len" class="req-item"><i class="fas fa-circle"></i> Min 8 Characters</li>
                                        <li id="req-upper" class="req-item"><i class="fas fa-circle"></i> One Uppercase Letter</li>
                                        <li id="req-num" class="req-item"><i class="fas fa-circle"></i> One Number</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">CONFIRM PASSWORD</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" class="form-control password-field" id="confPass" required placeholder="Repeat new password">
                                    <span class="input-group-text toggle-password" data-target="confPass"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end mt-2">
                            <button type="submit" name="change_password" id="passSubmit" class="btn btn-outline-danger fw-bold px-4" disabled>Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cropModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-crop-alt me-2"></i>Adjust Profile Picture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light text-center">
                <div style="max-height: 500px; overflow: hidden;">
                    <img id="imageToCrop" src="" style="display: block; max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="cropAndSave" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-save me-1"></i> Crop & Save
                </button>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="croppedForm" style="display:none;">
    <input type="hidden" name="cropped_image" id="cropped_image_data">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. PASSWORD TOGGLE ---
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // --- 2. PASSWORD STRENGTH VALIDATION ---
    const newPass = document.getElementById('newPass');
    const checklistDiv = document.getElementById('checklistContainer');
    const submitBtn = document.getElementById('passSubmit');
    const checklist = {
        len: document.getElementById('req-len'),
        upper: document.getElementById('req-upper'),
        num: document.getElementById('req-num')
    };

    newPass.addEventListener('focus', () => { checklistDiv.style.display = 'block'; });
    newPass.addEventListener('blur', () => { 
        if(newPass.value === '') checklistDiv.style.display = 'none'; 
    });

    newPass.addEventListener('input', function() {
        const val = this.value;
        const checks = {
            len: val.length >= 8,
            upper: /[A-Z]/.test(val),
            num: /[0-9]/.test(val)
        };

        Object.keys(checks).forEach(key => {
            if(checks[key]) {
                checklist[key].classList.add('valid');
                checklist[key].querySelector('i').className = 'fas fa-check-circle';
            } else {
                checklist[key].classList.remove('valid');
                checklist[key].querySelector('i').className = 'fas fa-circle';
            }
        });

        submitBtn.disabled = !(checks.len && checks.upper && checks.num);
    });

    // --- 3. CROPPER LOGIC ---
    var avatar = document.getElementById('fileUpload');
    var image = document.getElementById('imageToCrop');
    var modalEl = document.getElementById('cropModal');
    var modal = new bootstrap.Modal(modalEl);
    var cropper;

    avatar.addEventListener('change', function (e) {
        if (e.target.files && e.target.files.length > 0) {
            var reader = new FileReader();
            reader.onload = function (e) { 
                image.src = e.target.result; 
                modal.show(); 
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(image, { 
            aspectRatio: 1, 
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: false,
            center: false,
            highlight: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            toggleDragModeOnDblclick: false, 
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        avatar.value = ''; // Reset file input
    });

    document.getElementById('cropAndSave').addEventListener('click', function () {
        if (cropper) {
            var base64data = cropper.getCroppedCanvas({ width: 300, height: 300 }).toDataURL("image/png");
            document.getElementById('cropped_image_data').value = base64data;
            document.getElementById('croppedForm').submit();
        }
    });
});
</script>

<?php require 'includes/footer.php'; ?>