<?php
// 1. Start Session Safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Connection
if (file_exists(__DIR__ . '/../config/db.php')) {
    include_once __DIR__ . '/../config/db.php';
} elseif (file_exists(__DIR__ . '/../db.php')) {
    include_once __DIR__ . '/../db.php';
}

// 3. Notification Logic
$pending_alert = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] != 'employee' && isset($conn)) {
    $notify_sql = "SELECT COUNT(*) as count FROM tickets WHERE status = 'Open'";
    $notify_res = $conn->query($notify_sql);
    if ($notify_res) {
        $row = $notify_res->fetch_assoc();
        $pending_alert = $row['count'];
    }
}

// 4. Profile Picture Logic
$navbar_username = isset($_SESSION['username']) ? $_SESSION['username'] : "Guest";
$navbar_pic = "assets/images/default_avatar.png"; 

if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT username, profile_image FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $navbar_username = $row['username'];
        if (!empty($row['profile_image']) && file_exists(__DIR__ . '/../uploads/' . $row['profile_image'])) {
            $navbar_pic = 'uploads/' . $row['profile_image'];
        }
    }
}
?>

<style>
    /* --- LAYOUT VARIABLES --- */
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 70px;
        --topbar-height: 70px;
        --sidebar-bg: linear-gradient(180deg, var(--dark-blue, #0d6efd) 0%, var(--primary-blue, #0a58ca) 100%);
    }

    /* PRELOAD CLASS: Prevents animation flicker on page load */
    body.preload * {
        transition: none !important;
        -webkit-transition: none !important;
        -moz-transition: none !important;
        -ms-transition: none !important;
        -o-transition: none !important;
    }

    /* --- SIDEBAR STYLES --- */
    .sidebar {
        height: 100vh;
        width: var(--sidebar-width);
        position: fixed;
        top: var(--topbar-height);
        left: 0;
        background: var(--sidebar-bg);
        transition: width 0.3s ease, margin-left 0.3s ease; /* Specific transitions only */
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        overflow-y: auto;
        padding-bottom: 50px;
        overflow-x: hidden;
    }

    /* Collapsed State */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        margin-top: 10px;
    }

    .sidebar-nav .nav-item {
        margin-bottom: 5px;
        width: 100%;
        position: relative;
    }

    .sidebar-nav .nav-link {
        color: rgba(255,255,255,0.85);
        padding: 12px 20px;
        font-size: 1rem;
        display: flex;
        align-items: center;
        transition: background 0.2s, color 0.2s; /* Only transition colors on hover */
        border-left: 4px solid transparent;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        position: relative;
    }

    .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
        color: white;
        background: rgba(255,255,255,0.15);
        border-left-color: white;
    }

    .sidebar-nav .nav-link i {
        min-width: 30px; 
        text-align: center;
        margin-right: 15px;
        font-size: 1.1rem;
    }

    /* --- LOGIC FOR COLLAPSED SIDEBAR --- */
    
    .sidebar.collapsed .sidebar-nav .nav-link span:not(.badge-alert),
    .sidebar.collapsed .sidebar-nav .nav-link .fa-chevron-down {
        display: none !important;
    }
    
    .sidebar.collapsed .sidebar-nav .nav-link {
        padding: 15px 0;
        justify-content: center;
    }
    .sidebar.collapsed .sidebar-nav .nav-link i {
        margin-right: 0;
    }

    /* Floating Badge Positioning (Collapsed) */
    .sidebar.collapsed .sidebar-nav .nav-link .badge-alert {
        position: absolute;
        top: 8px;
        right: 12px;
        font-size: 0.6rem;
        padding: 2px 5px;
        border-radius: 6px;
        min-width: 18px;
        text-align: center;
        margin-left: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        display: inline-block !important; 
        z-index: 10;
    }

    .sidebar.collapsed .collapse {
        display: none !important; 
    }
    .sidebar-submenu {
        list-style: none;
        padding-left: 0;
        background: rgba(0,0,0,0.1);
    }
    .sidebar-submenu .nav-link {
        padding-left: 54px;
        font-size: 0.9rem;
    }

    /* --- TOP BAR STYLES --- */
    .topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--topbar-height);
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        /* No transition on topbar width to prevent jumpiness */
    }

    .brand-area {
        display: flex;
        align-items: center;
        min-width: 200px;
    }
    
    .brand-link {
        text-decoration: none;
        color: #333;
        display: flex;
        align-items: center;
        font-weight: 700;
        font-size: 1.2rem;
    }
    .brand-link img {
        height: 35px;
        width: auto;
        margin-right: 10px;
    }

    #menu-toggle {
        background: none;
        border: none;
        font-size: 1.4rem;
        color: #555;
        cursor: pointer;
        margin-right: 15px;
        padding: 5px;
        transition: 0.2s;
    }
    #menu-toggle:hover { color: var(--primary-blue, #0a58ca); }

    /* Adjust Body Padding */
    body {
        padding-left: var(--sidebar-width);
        padding-top: var(--topbar-height);
        transition: padding-left 0.3s ease; /* Only animate padding */
        background-color: #f8f9fa; 
    }

    body.sidebar-collapsed {
        padding-left: var(--sidebar-collapsed-width);
    }

    /* --- BADGES --- */
    .badge-alert {
        background-color: #dc3545;
        color: white;
        font-size: 0.75rem;
        padding: 2px 7px;
        border-radius: 6px;
        margin-left: auto; 
        font-weight: bold;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }

    .user-badge {
        display: flex;
        align-items: center;
        padding: 5px 12px;
        background: #f1f3f5;
        border-radius: 30px;
        transition: 0.2s;
        border: 1px solid #e9ecef;
    }
    .user-badge:hover { background: #e9ecef; }
    
    .navbar-profile-pic {
        width: 32px;
        height: 32px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 10px;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
        .sidebar { left: calc(var(--sidebar-width) * -1); top: var(--topbar-height); width: var(--sidebar-width); } 
        .sidebar.mobile-open { left: 0; }
        body, body.sidebar-collapsed { padding-left: 0; }
        .sidebar-overlay {
            display: none;
            position: fixed; top: var(--topbar-height); left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 900;
        }
        .sidebar.mobile-open ~ .sidebar-overlay { display: block; }
        .brand-text { display: none; }
    }
</style>

<script>document.body.classList.add('preload');</script>

<nav class="topbar">
    <div class="brand-area">
        <button id="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <a href="dashboard.php" class="brand-link">
            <img src="assets/images/proflogo.png" alt="Logo">
            <span class="brand-text">BC Helpdesk</span>
        </a>
    </div>

    <div class="d-flex align-items-center">
        <a href="profile.php" class="text-decoration-none me-3">
            <div class="user-badge">
                <img src="<?= $navbar_pic ?>" alt="Profile" class="navbar-profile-pic">
                <div class="d-flex flex-column" style="line-height: 1.1;">
                    <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($navbar_username) ?></span>
                    <?php if(isset($_SESSION['role'])): ?>
                        <small class="text-muted" style="font-size: 0.7rem;"><?= ucfirst($_SESSION['role']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-circle" title="Logout" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<div class="sidebar" id="sidebar-wrapper">
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php" title="Dashboard">
                <i class="fas fa-home"></i> 
                <span>Dashboard</span>
                <?php if($pending_alert > 0): ?>
                    <span class="badge-alert"><?= $pending_alert ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="ticket_create.php" title="New Ticket">
                <i class="fas fa-plus-circle"></i> <span>New Ticket</span>
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] != 'employee'): ?>
        <li class="nav-item">
            <a class="nav-link" href="reports.php" title="Reports">
                <i class="fas fa-chart-bar"></i> <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'manager')): ?>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#adminSubmenu" role="button" aria-expanded="false" title="Admin Panel">
                <i class="fas fa-cogs"></i> <span>Admin Panel</span>
                <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
            </a>
            <div class="collapse" id="adminSubmenu">
                <ul class="sidebar-submenu">
                    <?php if($_SESSION['role'] == 'super_admin'): ?>
                        <li><a class="nav-link" href="admin_departments.php">Departments</a></li>
                        <li><a class="nav-link" href="admin_users.php">Users</a></li>
                    <?php endif; ?>
                    <li><a class="nav-link" href="admin_categories.php">Categories</a></li>
                    <?php if($_SESSION['role'] == 'super_admin'): ?>
                        <li><a class="nav-link" href="admin_logs.php">Activity Logs</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <li class="nav-item mt-4">
            <a class="nav-link" href="about.php" title="About Us">
                <i class="fas fa-info-circle"></i> <span>About Us</span>
            </a>
        </li>
    </ul>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
    (function() {
        // Read state immediately during parsing
        if (window.innerWidth > 768) {
            const savedState = localStorage.getItem('sidebarState');
            if (savedState === 'collapsed') {
                document.getElementById('sidebar-wrapper').classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            }
        }
    })();

    // Remove preload class after page load
    window.addEventListener('load', function() {
        document.body.classList.remove('preload');
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar-wrapper');
        const body = document.body;
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-open');
        } else {
            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
            
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
        }
    }
</script>