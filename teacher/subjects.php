<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$tid = (int) current_user()['id'];
$msg = "";

$allowedGrades = ['6','7','8','9','10','11','12'];

/* ✅ Default 9 subjects set (same list for all grades) */
$default9 = [
  "Mathematics",
  "Science",
  "English",
  "Sinhala",
  "Buddhism",
  "History",
  "Geography",
  "ICT",
  "Health & PE"
];

/* ---------------------------
   BULK ADD AUTO (9 subjects by grade)
----------------------------*/
if (isset($_POST['bulk_add_auto'])) {
  $grade  = trim($_POST['bulk_grade'] ?? '');
  $status = trim($_POST['bulk_status'] ?? 'approved');

  if (!in_array($grade, $allowedGrades, true)) {
    $msg = "<div class='alert-danger'>Please select a valid grade (6-12).</div>";
  } elseif (!in_array($status, ['approved','pending'], true)) {
    $msg = "<div class='alert-danger'>Invalid status.</div>";
  } else {

    // ✅ Insert 9 subjects (ignore duplicates)
    $ins = $conn->prepare("INSERT IGNORE INTO subjects(name, grade, teacher_id, status) VALUES (?,?,?,?)");

    foreach ($default9 as $name) {
      $ins->bind_param("ssis", $name, $grade, $tid, $status);
      $ins->execute();
    }

    $msg = "<div class='alert-success'>✅ 9 subjects added for Grade <b>$grade</b> (Status: <b>$status</b>).</div>";
  }
}

/* ---------------------------
   SINGLE CREATE
----------------------------*/
if (isset($_POST['create'])) {
  $name = trim($_POST['name'] ?? '');
  $grade = trim($_POST['grade'] ?? '');
  $status = trim($_POST['status'] ?? 'approved');

  if ($name === '' || !in_array($grade, $allowedGrades, true)) {
    $msg = "<div class='alert-danger'>Please enter subject name and select grade (6-12).</div>";
  } else {
    $st = $conn->prepare("INSERT INTO subjects(name, grade, teacher_id, status) VALUES (?,?,?,?)");
    $st->bind_param("ssis", $name, $grade, $tid, $status);
    $st->execute();
    $msg = "<div class='alert-success'>✅ Subject added.</div>";
  }
}

/* ---------------------------
   UPDATE
----------------------------*/
if (isset($_POST['update'])) {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $grade = trim($_POST['grade'] ?? '');
  $status = trim($_POST['status'] ?? 'approved');

  if ($id <= 0 || $name === '' || !in_array($grade, $allowedGrades, true)) {
    $msg = "<div class='alert-danger'>Invalid update data.</div>";
  } else {
    $st = $conn->prepare("UPDATE subjects SET name=?, grade=?, status=? WHERE id=? AND teacher_id=?");
    $st->bind_param("sssii", $name, $grade, $status, $id, $tid);
    $st->execute();
    $msg = "<div class='alert-success'>✅ Updated.</div>";
  }
}

/* ---------------------------
   DELETE
----------------------------*/
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $st = $conn->prepare("DELETE FROM subjects WHERE id=? AND teacher_id=?");
  $st->bind_param("ii", $id, $tid);
  $st->execute();
  redirect("/classms/teacher/subjects.php");
}

/* ---------------------------
   EDIT LOAD
----------------------------*/
$edit = null;
if (isset($_GET['edit'])) {
  $id = (int)$_GET['edit'];
  $st = $conn->prepare("SELECT * FROM subjects WHERE id=? AND teacher_id=?");
  $st->bind_param("ii", $id, $tid);
  $st->execute();
  $edit = $st->get_result()->fetch_assoc();
}

/* ---------------------------
   SEARCH + FILTERS
----------------------------*/
$q = trim($_GET['q'] ?? '');
$filter_grade = trim($_GET['grade'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM subjects WHERE teacher_id=? ";
$params = [$tid];
$types = "i";

if ($q !== '') {
  $sql .= " AND name LIKE ? ";
  $params[] = "%$q%";
  $types .= "s";
}
if ($filter_grade !== '' && in_array($filter_grade, $allowedGrades, true)) {
  $sql .= " AND grade=? ";
  $params[] = $filter_grade;
  $types .= "s";
}
if ($filter_status !== '' && in_array($filter_status, ['approved','pending'], true)) {
  $sql .= " AND status=? ";
  $params[] = $filter_status;
  $types .= "s";
}

$sql .= " ORDER BY grade+0 ASC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

require_once __DIR__ . "/../includes/header.php";
?>

<style>
/* Dark Blue Theme with White Text */
:root {
    --dark-blue: #0a192f;
    --medium-blue: #112240;
    --light-blue: #233554;
    --accent-blue: #64ffda;
    --accent-pink: #ff6b9d;
    --accent-gold: #ffd166;
    --accent-purple: #a882ff;
    --accent-green: #4cd964;
    --accent-red: #ff4757;
    --text-white: #ffffff;
    --text-light: #e6f1ff;
    --text-muted: #a8b2d1;
    --card-shadow: 0 10px 30px -15px rgba(2, 12, 27, 0.7);
    --input-bg: rgba(26, 54, 93, 0.7);
}

body {
    background-color: var(--dark-blue) !important;
    color: var(--text-white) !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

/* Background Animation */
.bg-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    background: linear-gradient(-45deg, #0a192f, #112240, #1a365d, #233554);
    background-size: 400% 400%;
    animation: gradient-shift 15s ease infinite;
}

@keyframes gradient-shift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Floating particles */
.particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    pointer-events: none;
}

.particle {
    position: absolute;
    border-radius: 50%;
    animation: float 20s infinite linear;
}

/* Main Container */
.container-custom {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
    animation: slide-up 0.8s ease-out;
}

@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    animation: header-appear 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

@keyframes header-appear {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-pink), var(--accent-gold));
    animation: header-line 2s ease-out;
}

@keyframes header-line {
    from { transform: translateX(-100%); }
    to { transform: translateX(0); }
}

.page-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 10px;
    background: linear-gradient(135deg, var(--accent-blue), var(--text-white));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
}

/* Form Card */
.form-card {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: form-appear 0.6s ease-out;
}

@keyframes form-appear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-title {
    color: var(--text-white);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--accent-blue);
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Form Controls */
.form-label {
    color: var(--text-light);
    font-weight: 500;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
}

.form-label i {
    color: var(--accent-blue);
    width: 20px;
}

.form-control-custom, .form-select-custom {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
    padding: 12px 15px !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
}

.form-control-custom:focus, .form-select-custom:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    color: var(--text-white) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
    transform: translateY(-2px);
}

.form-control-custom::placeholder {
    color: var(--text-muted) !important;
}

/* Buttons */
.btn-primary-custom {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-primary-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.7s;
    z-index: -1;
}

.btn-primary-custom:hover::before {
    left: 100%;
}

.btn-primary-custom:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 25px rgba(100, 255, 218, 0.4);
}

.btn-primary-custom:active {
    transform: translateY(-1px) scale(1.02);
}

.btn-outline-custom {
    background: transparent;
    color: var(--accent-blue);
    border: 2px solid var(--accent-blue);
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.btn-outline-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(100, 255, 218, 0.2), transparent);
    transition: left 0.7s;
    z-index: -1;
}

.btn-outline-custom:hover::before {
    left: 100%;
}

.btn-outline-custom:hover {
    background: rgba(100, 255, 218, 0.1);
    color: var(--text-white);
    transform: translateX(-5px);
    box-shadow: 0 5px 20px rgba(100, 255, 218, 0.3);
}

.btn-warning-custom {
    background: linear-gradient(135deg, var(--accent-gold), #ffb347);
    color: var(--dark-blue);
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-warning-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 209, 102, 0.4);
}

/* Alert Messages */
.alert-success {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    backdrop-filter: blur(10px);
    animation: alert-slide 0.5s ease-out, alert-pulse 2s infinite;
}

@keyframes alert-slide {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes alert-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(100, 255, 218, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(100, 255, 218, 0); }
}

.alert-danger {
    background: rgba(255, 107, 157, 0.1);
    color: var(--accent-pink);
    border: 1px solid rgba(255, 107, 157, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    backdrop-filter: blur(10px);
    animation: alert-slide 0.5s ease-out;
}

/* Subjects List */
.subjects-grid {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 25px;
}

.subject-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(100, 255, 218, 0.05);
    color: var(--accent-blue);
    padding: 8px 16px;
    border-radius: 25px;
    margin: 5px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    transition: all 0.3s ease;
    animation: tag-appear 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes tag-appear {
    to { opacity: 1; }
}

.subject-tag:hover {
    background: rgba(100, 255, 218, 0.1);
    transform: translateY(-2px);
    border-color: var(--accent-blue);
}

/* Subjects Table */
.subjects-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: container-appear 0.8s ease-out forwards 0.2s;
    opacity: 0;
}

@keyframes container-appear {
    to { opacity: 1; }
}

.subjects-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}

.subjects-table thead th {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--accent-blue);
    font-weight: 600;
    padding: 18px 20px;
    border-bottom: 2px solid rgba(100, 255, 218, 0.2);
    position: relative;
    overflow: hidden;
}

.subjects-table thead th::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--accent-blue);
    animation: expand-width 1s ease-out forwards;
}

@keyframes expand-width {
    to { width: 100%; }
}

.subjects-table tbody tr {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
    transition: all 0.3s ease;
}

@keyframes row-appear {
    to { opacity: 1; }
}

.subjects-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
}

.subjects-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    vertical-align: middle;
}

.subjects-table tr:last-child td {
    border-bottom: none;
}

/* Grade Badge */
.grade-badge {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    animation: badge-pulse 2s infinite;
}

@keyframes badge-pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(168, 130, 255, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(168, 130, 255, 0); }
}

/* Status Badge */
.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-approved {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.3);
}

.badge-pending {
    background: rgba(255, 209, 102, 0.1);
    color: var(--accent-gold);
    border: 1px solid rgba(255, 209, 102, 0.3);
}

/* Action Buttons */
.btn-action {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-edit {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.2);
}

.btn-edit:hover {
    background: rgba(100, 255, 218, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(100, 255, 218, 0.2);
}

.btn-delete {
    background: rgba(255, 71, 87, 0.1);
    color: var(--accent-red);
    border: 1px solid rgba(255, 71, 87, 0.2);
}

.btn-delete:hover {
    background: rgba(255, 71, 87, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 71, 87, 0.2);
}

/* Filter Form */
.filter-form {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 25px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 30px;
    animation: empty-appear 0.8s ease-out;
}

@keyframes empty-appear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.empty-icon {
    font-size: 4rem;
    color: var(--accent-blue);
    margin-bottom: 20px;
    animation: icon-float 3s ease-in-out infinite;
}

@keyframes icon-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Info Tip */
.info-tip {
    background: rgba(100, 255, 218, 0.05);
    border: 1px solid rgba(100, 255, 218, 0.1);
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
}

.info-tip i {
    color: var(--accent-gold);
    margin-right: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .container-custom {
        padding: 20px 15px;
    }
    
    .page-header {
        padding: 20px;
    }
    
    .page-title {
        font-size: 1.8rem;
    }
    
    .form-card, .subjects-container {
        padding: 20px;
    }
    
    .subjects-container {
        overflow-x: auto;
    }
    
    .subjects-table {
        min-width: 600px;
    }
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--dark-blue);
}

::-webkit-scrollbar-thumb {
    background: var(--accent-blue);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #52d3b8;
}
</style>

<!-- Background Animation -->
<div class="bg-animation"></div>
<div class="particles" id="particles-container"></div>

<div class="container-custom">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-book"></i> Manage Subjects
                </h1>
                <div class="page-subtitle">
                    Create and manage subjects for different grade levels
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Forms -->
        <div class="col-lg-4">
            <!-- Add/Edit Form -->
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-<?= $edit ? 'edit' : 'plus' ?>"></i>
                    <?= $edit ? 'Edit Subject' : 'Add New Subject' ?>
                </div>
                
                <?= $msg ?>
                
                <form method="post">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-font"></i> Subject Name
                        </label>
                        <input class="form-control-custom" name="name" required 
                               value="<?= e($edit['name'] ?? '') ?>" 
                               placeholder="Enter subject name">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-graduation-cap"></i> Grade Level
                        </label>
                        <select class="form-select-custom" name="grade" required>
                            <option value="">Select Grade</option>
                            <?php foreach($allowedGrades as $gr): ?>
                                <option value="<?= $gr ?>" <?= ((string)($edit['grade'] ?? '')===$gr)?'selected':'' ?>>
                                    Grade <?= $gr ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i> Status
                        </label>
                        <select class="form-select-custom" name="status">
                            <option value="approved" <?= (($edit['status'] ?? '')==='approved')?'selected':'' ?>>Approved</option>
                            <option value="pending" <?= (($edit['status'] ?? '')==='pending')?'selected':'' ?>>Pending</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if($edit): ?>
                            <button class="btn-primary-custom" name="update">
                                <i class="fas fa-save"></i> Update Subject
                            </button>
                            <a class="btn-outline-custom text-center" href="/classms/teacher/subjects.php">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        <?php else: ?>
                            <button class="btn-primary-custom" name="create">
                                <i class="fas fa-plus"></i> Add Subject
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Bulk Add Section -->
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-bolt"></i> Bulk Add Subjects
                </div>
                
                <div class="subjects-grid mb-4">
                    <div class="mb-3" style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> Will add these 9 subjects:
                    </div>
                    <div>
                        <?php foreach($default9 as $index => $subject): ?>
                            <div class="subject-tag" style="animation-delay: <?= $index * 0.1 ?>s;">
                                <i class="fas fa-check-circle"></i>
                                <?= e($subject) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-layer-group"></i> Select Grade
                        </label>
                        <select class="form-select-custom" name="bulk_grade" required>
                            <option value="">Select Grade</option>
                            <?php foreach($allowedGrades as $gr): ?>
                                <option value="<?= $gr ?>">Grade <?= $gr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag"></i> Initial Status
                        </label>
                        <select class="form-select-custom" name="bulk_status">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    
                    <button class="btn-warning-custom w-100" name="bulk_add_auto">
                        <i class="fas fa-bolt"></i> Add 9 Subjects Automatically
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Right Column: Subjects List -->
        <div class="col-lg-8">
            <div class="subjects-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="form-title mb-0">
                            <i class="fas fa-list"></i> My Subjects
                        </h2>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                            Total: <?= $res->num_rows ?> subjects
                        </div>
                    </div>
                    <a href="/classms/teacher/student_subjects.php" class="btn-outline-custom">
                        <i class="fas fa-users"></i> View Student Subjects
                    </a>
                </div>
                
                <!-- Filter Form -->
                <form method="get" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">
                                <i class="fas fa-search"></i> Search
                            </label>
                            <input class="form-control-custom" name="q" value="<?= e($q) ?>" 
                                   placeholder="Search by subject name...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-filter"></i> Grade
                            </label>
                            <select class="form-select-custom" name="grade">
                                <option value="">All Grades</option>
                                <?php foreach($allowedGrades as $gr): ?>
                                    <option value="<?= $gr ?>" <?= ($filter_grade===$gr)?'selected':'' ?>>
                                        Grade <?= $gr ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> Status
                            </label>
                            <select class="form-select-custom" name="status">
                                <option value="">All Status</option>
                                <option value="approved" <?= ($filter_status==='approved')?'selected':'' ?>>Approved</option>
                                <option value="pending" <?= ($filter_status==='pending')?'selected':'' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="opacity: 0;">Search</label>
                            <button class="btn-primary-custom w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Subjects Table -->
                <div class="table-responsive">
                    <table class="subjects-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Grade</th>
                                <th style="width: 35%;">Subject</th>
                                <th style="width: 20%;">Status</th>
                                <th style="width: 25%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($res->num_rows > 0): ?>
                                <?php 
                                $rowDelay = 0;
                                while($r = $res->fetch_assoc()): 
                                    $rowDelay += 0.05;
                                ?>
                                    <tr style="animation-delay: <?= $rowDelay ?>s;">
                                        <td>
                                            <span class="grade-badge">
                                                <i class="fas fa-graduation-cap"></i>
                                                Grade <?= e($r['grade']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="background: rgba(100, 255, 218, 0.1); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent-blue);">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--text-white);"><?= e($r['name']) ?></div>
                                                    <div style="color: var(--text-muted); font-size: 0.85rem;">
                                                        ID: <?= (int)$r['id'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= ($r['status']==='approved')?'badge-approved':'badge-pending' ?>">
                                                <i class="fas fa-<?= ($r['status']==='approved')?'check-circle':'clock' ?>"></i>
                                                <?= ucfirst(e($r['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a class="btn-action btn-edit" href="?edit=<?= (int)$r['id'] ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a class="btn-action btn-delete" 
                                                   onclick="return confirm('Are you sure you want to delete this subject?')"
                                                   href="?delete=<?= (int)$r['id'] ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                                No Subjects Found
                                            </h3>
                                            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 20px;">
                                                <?php if($q !== ''): ?>
                                                    No subjects found matching your search.
                                                <?php else: ?>
                                                    You haven't created any subjects yet. Start by adding your first subject!
                                                <?php endif; ?>
                                            </p>
                                            <a href="?" class="btn-primary-custom">
                                                <i class="fas fa-redo"></i> Clear Filters
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Info Tip -->
                <div class="info-tip">
                    <i class="fas fa-lightbulb"></i>
                    <span style="color: var(--text-light);">
                        Set subjects to <b>approved</b> so students can see assignments and marks for the grade. 
                        <a href="/classms/teacher/student_subjects.php" style="color: var(--accent-blue); text-decoration: none;">
                            View which students are enrolled in your subjects
                        </a>.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
// Create floating particles
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('particles-container');
    const particleCount = 20;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Random properties
        const size = Math.random() * 5 + 2;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const delay = Math.random() * 15;
        const duration = Math.random() * 20 + 10;
        const colors = [
            'rgba(100, 255, 218, 0.1)',
            'rgba(255, 209, 102, 0.1)',
            'rgba(255, 107, 157, 0.1)',
            'rgba(168, 130, 255, 0.1)'
        ];
        const color = colors[Math.floor(Math.random() * colors.length)];
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${posX}%`;
        particle.style.top = `${posY}%`;
        particle.style.background = color;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;
        
        container.appendChild(particle);
    }
    
    // Row animations
    const rows = document.querySelectorAll('.subjects-table tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Tag animations
    const tags = document.querySelectorAll('.subject-tag');
    tags.forEach((tag, index) => {
        tag.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Form input focus effects
    const inputs = document.querySelectorAll('.form-control-custom, .form-select-custom');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-5px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
    
    // Delete confirmation with animation
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this subject?')) {
                e.preventDefault();
                // Shake animation
                this.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    this.style.animation = '';
                }, 500);
            }
        });
    });
    
    // Add shake animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
    
    // Subject tag hover effect
    tags.forEach(tag => {
        tag.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });
        
        tag.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>