<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require 'config/db.php';
require 'includes/header.php';
require 'includes/navbar.php';
checkLogin();

// 1. SECURITY (Updated: Only Employees are denied access now)
if ($_SESSION['role'] == 'employee') { 
    echo "<div class='container mt-5'><div class='alert alert-danger'>Access Denied.</div></div>";
    require 'includes/footer.php';
    exit(); 
}

$current_role = $_SESSION['role'];
$user_id = intval($_SESSION['user_id']);

// 2. FILTERS
$start = !empty($_GET['start']) ? $_GET['start'] : '2020-01-01';
$end   = !empty($_GET['end'])   ? $_GET['end']   : date('Y-m-d');
$status_filter = !empty($_GET['status_filter']) ? $_GET['status_filter'] : '';
$dept_filter_id = 0;
$manager_dept_name = "";

// Filter by Assigned Staff (Default logic)
$assigned_filter_id = !empty($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;

// 3. LOGIC
if ($current_role == 'manager') {
    // MANAGER: Must ONLY see tickets from their own department
    $stmt = $conn->prepare("SELECT u.dept_id, d.name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if ($row) {
        $dept_filter_id = intval($row['dept_id']); 
        $manager_dept_name = $row['name'];
    } else {
        echo "<div class='container mt-5'><div class='alert alert-warning'>Error: Manager account is not assigned to a valid department.</div></div>";
        require 'includes/footer.php'; exit();
    }
}
else if ($current_role == 'staff') {
    // STAFF: Must ONLY see tickets assigned to THEM
    // We force the assigned_filter_id to be their own ID
    $assigned_filter_id = $user_id;
    
    // Optional: Get their department name for display purposes if needed
    $stmt = $conn->prepare("SELECT u.dept_id, d.name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $dept_filter_id = intval($row['dept_id']); 
        $manager_dept_name = $row['name']; // Reusing variable for display
    }
} 
else if ($current_role == 'super_admin') {
    // SUPER ADMIN: Sees ALL by default, but can filter via dropdown
    if (!empty($_GET['dept_id'])) {
        $dept_filter_id = intval($_GET['dept_id']);
    }
}

// 4. SQL BUILDER
$where = ["DATE(t.created_at) BETWEEN '$start' AND '$end'"];

// Filter by Department (Strict for Manager/Staff, Optional for Admin)
if ($dept_filter_id > 0) { 
    $where[] = "t.dept_id = $dept_filter_id"; 
}

// Filter by Assigned Staff (Strict for Staff, Optional for others)
if ($assigned_filter_id > 0) { 
    $where[] = "t.assigned_to = $assigned_filter_id"; 
}

if (!empty($status_filter)) { $where[] = "t.status = '" . $conn->real_escape_string($status_filter) . "'"; }
$where_sql = implode(" AND ", $where);

// --- QUERY 1: STATS ---
$stats = $conn->query("
    SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN t.status IN ('Open', 'In Progress') THEN 1 ELSE 0 END) as open_cnt, 
        SUM(CASE WHEN t.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) as closed_cnt, 
        SUM(CASE WHEN t.status = 'Rejected' THEN 1 ELSE 0 END) as rejected_cnt,
        AVG(NULLIF(t.rating, 0)) as avg_rating 
    FROM tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE $where_sql
")->fetch_assoc();

// --- QUERY 2: CHART DATA ---
$chart_res = $conn->query("SELECT rating, COUNT(*) as count FROM tickets t LEFT JOIN users u ON t.user_id = u.id WHERE t.rating > 0 AND $where_sql GROUP BY rating ORDER BY rating DESC");
$star_data = [0, 0, 0, 0, 0];
while($r = $chart_res->fetch_assoc()) { $rt=intval($r['rating']); if($rt>=1&&$rt<=5) $star_data[5-$rt]=intval($r['count']); }

// --- QUERY 3: MAIN LIST ---
$result = $conn->query("
    SELECT t.id, t.subject, t.status, t.rating, t.created_at, t.resolved_at,
           u.username as requester, 
           d.name as dept_name,
           assignee.username as assigned_agent
    FROM tickets t 
    LEFT JOIN users u ON t.user_id = u.id 
    LEFT JOIN departments d ON t.dept_id = d.id 
    LEFT JOIN users assignee ON t.assigned_to = assignee.id
    WHERE $where_sql 
    ORDER BY t.created_at DESC
");

$dept_list = $conn->query("SELECT * FROM departments ORDER BY name ASC");

// --- STAFF LIST ---
$staff_sql = "SELECT id, username FROM users WHERE role IN ('staff', 'manager')";
if ($dept_filter_id > 0) {
    $staff_sql .= " AND dept_id = $dept_filter_id";
}
$staff_sql .= " ORDER BY username ASC";
$staff_list = $conn->query($staff_sql);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* --- SCREEN STYLES --- */
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
    
    .glass-panel {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .kpi-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: transform 0.3s;
    }
    .kpi-card:hover { transform: translateY(-5px); }

    .glass-header {
        background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .agent-badge {
        background-color: #e0f7fa; color: #006064; border: 1px solid #b2ebf2;
        padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px;
    }

    .print-only-header { display: none; }

    /* --- UPDATED PROFESSIONAL PRINT STYLES --- */
    @media print {
        @page { size: landscape; margin: 15mm; }
        
        /* Force browsers to print background colors and graphics */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .navbar, nav, header, footer, .btn, form, .glass-header {
            display: none !important;
        }
        
        body { 
            background: white !important; 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000 !important; 
            margin: 0 !important; padding: 0 !important;
        }
        
        .container { max-width: 100% !important; width: 100% !important; padding: 0 !important; margin: 0 !important; }

        /* Enhanced Print Header */
        .print-only-header {
            display: flex !important;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 3px solid #005f73;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .print-title h1 { font-size: 26px; font-weight: 800; margin: 0; color: #005f73 !important; text-transform: uppercase; letter-spacing: 1px; }
        .print-title p { font-size: 13px; font-weight: 600; margin: 3px 0 0 0; color: #555 !important; }
        .print-meta { text-align: right; font-size: 11px; color: #333 !important; line-height: 1.5; }

        /* Enhanced KPI Cards */
        .print-kpi-row {
            display: flex !important;
            justify-content: space-between;
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px 10px;
            background-color: #f8f9fa !important;
        }
        .print-kpi-item { text-align: center; flex: 1; border-right: 1px solid #ccc; }
        .print-kpi-item:last-child { border-right: none; }
        .print-kpi-label { font-size: 11px; text-transform: uppercase; color: #555 !important; font-weight: 600; display: block; margin-bottom: 5px; }
        .print-kpi-value { font-size: 22px; font-weight: 800; color: #000 !important; display: block; }

        /* Layout & Table Adjustments */
        .row { display: block !important; margin: 0 !important; }
        .col-md-4 { display: none !important; } /* Hide Chart on Print */
        .col-md-8 { width: 100% !important; max-width: 100% !important; padding: 0 !important; flex: 0 0 100% !important; }

        .glass-panel { box-shadow: none !important; border: none !important; border-radius: 0 !important; padding: 0 !important; }
        .table-responsive { overflow: visible !important; }
        
        /* Formal Report Table Formatting */
        .table { 
            width: 100% !important; 
            border-collapse: collapse !important; 
            font-size: 12px !important; 
            margin-top: 0 !important; 
            border: 1px solid #ccc !important;
        }
        .table thead th { 
            background-color: #005f73 !important; 
            color: #ffffff !important; 
            text-transform: uppercase; 
            border: 1px solid #ccc !important; 
            padding: 10px 8px !important;
            font-size: 11px;
            text-align: left;
        }
        .table tbody td { 
            border: 1px solid #ccc !important; 
            padding: 8px !important; 
            vertical-align: middle !important;
        }
        .table tbody tr { page-break-inside: avoid; }
        .table tbody tr:nth-child(even) { background-color: #fdfdfd !important; }
        
        /* Text cleanup for print */
        .text-muted { color: #555 !important; }
        .agent-badge, .badge {
            border: none !important; 
            background: transparent !important; 
            color: #000 !important; 
            padding: 0 !important; 
            font-weight: bold !important;
            font-size: 12px !important;
        }

        /* Standalone status text colors for printing */
        .status-Open { color: #d00000 !important; }
        .status-In\ Progress { color: #e85d04 !important; }
        .status-Resolved { color: #2a9d8f !important; }
        .status-Closed { color: #370617 !important; }
        .status-Rejected { color: #6c757d !important; }

        /* Star rating contrast */
        .text-warning { color: #000 !important; }
        .text-opacity-25 { color: #ccc !important; }

        /* FIX: Ensure .no-print overrides everything else during print */
        .no-print { display: none !important; }
    }
</style>

<div class="container mt-4 mb-5">
    
    <div class="print-only-header">
        <div class="print-title">
            <h1>Performance Report</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #555;">BC Helpdesk</p>
        </div>
        <div class="print-meta">
            <div><strong>Generated:</strong> <?= date('F d, Y h:i A') ?></div>
            <div><strong>Date Range:</strong> <?= date('M d', strtotime($start)) ?> - <?= date('M d', strtotime($end)) ?></div>
        </div>
    </div>

    <div class="print-kpi-row d-none d-print-flex">
        <div class="print-kpi-item">
            <span class="print-kpi-label">Total Tickets</span>
            <span class="print-kpi-value"><?= number_format($stats['total']) ?></span>
        </div>
        <div class="print-kpi-item">
            <span class="print-kpi-label">Open Issues</span>
            <span class="print-kpi-value"><?= number_format($stats['open_cnt']) ?></span>
        </div>
        <div class="print-kpi-item">
            <span class="print-kpi-label">Resolved</span>
            <span class="print-kpi-value"><?= number_format($stats['closed_cnt']) ?></span>
        </div>
        <div class="print-kpi-item">
            <span class="print-kpi-label">Rejected</span>
            <span class="print-kpi-value"><?= number_format($stats['rejected_cnt']) ?></span>
        </div>
        <div class="print-kpi-item">
            <span class="print-kpi-label">Satisfaction</span>
            <span class="print-kpi-value"><?= number_format($stats['avg_rating'], 1) ?> / 5</span>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-pie me-2 text-primary"></i>Performance Overview</h2>
            <p class="mb-0 text-muted small">Generated: <?= date('F d, Y') ?></p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm fw-bold shadow-sm"><i class="fas fa-print me-2"></i>Print Report</button>
    </div>

    <div class="glass-panel p-4 mb-4 no-print">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Start Date</label>
                <input type="date" name="start" value="<?= $start ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">End Date</label>
                <input type="date" name="end" value="<?= $end ?>" class="form-control form-control-sm">
            </div>
            
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Department</label>
                <?php if ($current_role == 'manager' || $current_role == 'staff'): ?>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($manager_dept_name) ?>" readonly>
                <?php else: ?>
                    <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Departments --</option>
                        <?php while($d = $dept_list->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>" <?= ($dept_filter_id == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Assigned Staff</label>
                <?php if ($current_role == 'staff'): ?>
                    <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($_SESSION['username']) ?>" readonly>
                <?php else: ?>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">-- All Staff --</option>
                        <?php if($staff_list): while($s = $staff_list->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= ($assigned_filter_id == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['username']) ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Status</label>
                <select name="status_filter" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="Open" <?= $status_filter=='Open'?'selected':'' ?>>Open</option>
                    <option value="In Progress" <?= $status_filter=='In Progress'?'selected':'' ?>>In Progress</option>
                    <option value="Resolved" <?= $status_filter=='Resolved'?'selected':'' ?>>Resolved</option>
                    <option value="Closed" <?= $status_filter=='Closed'?'selected':'' ?>>Closed</option>
                    <option value="Rejected" <?= $status_filter=='Rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold" style="background: #0077b6; border-color: #0077b6;">
                    <i class="fas fa-filter me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-5 g-3 mb-4 no-print">
        <div class="col">
            <div class="kpi-card">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Total Tickets</div>
                <h2 class="fw-bold text-dark mb-0"><?= number_format($stats['total']) ?></h2>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Active Issues</div>
                <h2 class="fw-bold text-danger mb-0"><?= number_format($stats['open_cnt']) ?></h2>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Resolved</div>
                <h2 class="fw-bold text-success mb-0"><?= number_format($stats['closed_cnt']) ?></h2>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Rejected</div>
                <h2 class="fw-bold text-secondary mb-0"><?= number_format($stats['rejected_cnt']) ?></h2>
            </div>
        </div>
        <div class="col">
            <div class="kpi-card">
                <div class="text-secondary small fw-bold text-uppercase mb-1">Avg Rating</div>
                <h2 class="fw-bold text-warning mb-0"><?= number_format($stats['avg_rating'], 1) ?> <small class="text-muted fs-6">/ 5</small></h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4 no-print">
            <div class="glass-panel h-100 p-0">
                <div class="glass-header"><i class="fas fa-smile me-2"></i>Satisfaction Breakdown</div>
                <div class="p-4 d-flex flex-column align-items-center justify-content-center" style="min-height: 300px;">
                    <div style="width: 100%; height: 250px; position: relative;">
                        <canvas id="ratingChart"></canvas>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                            <div class="h4 fw-bold text-muted mb-0"><?= $stats['total'] > 0 ? round(($stats['avg_rating']/5)*100) . '%' : 'N/A' ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;">SATISFACTION</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="glass-panel h-100 p-0">
                <div class="glass-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list-ul me-2"></i>All Ticket Records</span>
                    <span class="badge bg-light text-primary shadow-sm no-print">Count: <?= $result->num_rows ?></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3" style="width: 25%;">Subject</th>
                                <th style="width: 20%;">Requester</th>
                                <th style="width: 15%;">Assigned To</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 10%;">Rating</th>
                                <th style="width: 10%;">Created</th>
                                <th style="width: 10%;">Resolved</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php if($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($row['subject']) ?></td>
                                    <td>
                                        <div class="small fw-bold text-dark"><?= htmlspecialchars($row['requester']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($row['dept_name']) ?></div>
                                    </td>
                                    <td>
                                        <?php if(!empty($row['assigned_agent'])): ?>
                                            <span class="agent-badge"><?= htmlspecialchars($row['assigned_agent']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = $row['status']; 
                                            $c = 'secondary';
                                            if($st=='Open') $c='danger';
                                            elseif($st=='In Progress') $c='warning';
                                            elseif($st=='Resolved') $c='success';
                                            elseif($st=='Closed') $c='dark';
                                            elseif($st=='Rejected') $c='secondary';
                                        ?>
                                        <span class="badge bg-<?= $c ?> bg-opacity-75 text-white status-<?= $st ?>"><?= $st ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $stars = intval($row['rating']);
                                            $rDesc = ['','Poor','Need Improvement','Satisfactory','Very Satisfactory','Excellent'];
                                            $rColor = ['','#dc3545','#fd7e14','#ffc107','#0d6efd','#198754'];
                                            if($stars > 0) {
                                                for($i=1; $i<=5; $i++) {
                                                    echo ($i <= $stars) ? '<span class="text-warning small">★</span>' : '<span class="text-muted text-opacity-25 small">★</span>';
                                                }
                                                echo '<br><span style="font-size:0.72rem;color:'.$rColor[$stars].';font-weight:600;">'.$rDesc[$stars].'</span>';
                                            } else {
                                                echo '<span class="text-muted small">-</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y', strtotime($row['created_at'])) ?><br>
                                        <span style="font-size: 0.75em;"><?= date('h:i A', strtotime($row['created_at'])) ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php if(!empty($row['resolved_at'])): ?>
                                            <?= date('M d, Y', strtotime($row['resolved_at'])) ?><br>
                                            <span style="font-size: 0.75em;"><?= date('h:i A', strtotime($row['resolved_at'])) ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center p-5 text-muted">No records found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(window.innerWidth > 0 && document.getElementById('ratingChart')) { 
            new Chart(document.getElementById('ratingChart'), {
                type: 'doughnut',
                data: {
                    labels: ['5 - Excellent', '4 - Very Satisfactory', '3 - Satisfactory', '2 - Need Improvement', '1 - Poor'],
                    datasets: [{
                        data: <?= json_encode($star_data) ?>,
                        backgroundColor: ['#2a9d8f', '#264653', '#e9c46a', '#f4a261', '#e76f51'],
                        borderWidth: 0, hoverOffset: 10
                    }]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, cutout: '75%', 
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } }
                }
            });
        }
    });
</script>

<?php require 'includes/footer.php'; ?>