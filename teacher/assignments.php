<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$tid = (int) current_user()['id'];
$msg = "";

// Teacher grade (auto from login) optional
$class_grade = (string)($_SESSION['user']['class_grade'] ?? '');

// Load teacher subjects
$subs = $conn->prepare("SELECT id, name, grade FROM subjects WHERE teacher_id=? AND status='approved' ORDER BY grade+0 ASC, name ASC");
$subs->bind_param("i",$tid);
$subs->execute();
$subRes = $subs->get_result();

// CREATE assignment
if(isset($_POST['create'])){
  $subject_id = (int)($_POST['subject_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $due_date = trim($_POST['due_date'] ?? '');
  $description = trim($_POST['description'] ?? '');

  if($subject_id<=0 || $title==='' || $due_date===''){
    $msg = "<div class='alert-danger'>Please fill Subject, Title and Due date.</div>";
  } else {

    // ensure subject belongs to teacher
    $chk = $conn->prepare("SELECT id FROM subjects WHERE id=? AND teacher_id=? LIMIT 1");
    $chk->bind_param("ii",$subject_id,$tid);
    $chk->execute();
    if(!$chk->get_result()->fetch_assoc()){
      $msg = "<div class='alert-danger'>Invalid subject.</div>";
    } else {
      $ins = $conn->prepare("INSERT INTO assignments(subject_id, teacher_id, title, description, due_date) VALUES (?,?,?,?,?)");
      $ins->bind_param("iisss",$subject_id,$tid,$title,$description,$due_date);
      $ins->execute();
      $msg = "<div class='alert-success'>✅ Assignment created. It is now visible to all students for that grade subject.</div>";
    }
  }
}

// DELETE
if(isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  $del = $conn->prepare("DELETE FROM assignments WHERE id=? AND teacher_id=?");
  $del->bind_param("ii",$id,$tid);
  $del->execute();
  redirect("/classms/teacher/assignments.php");
}

// List assignments
$res = $conn->prepare("
  SELECT a.*, s.name subject_name, s.grade
  FROM assignments a
  JOIN subjects s ON s.id=a.subject_id
  WHERE a.teacher_id=?
  ORDER BY a.due_date DESC, a.id DESC
");
$res->bind_param("i",$tid);
$res->execute();
$list = $res->get_result();

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

.btn-danger-custom {
    background: rgba(255, 71, 87, 0.1);
    color: var(--accent-red);
    border: 1px solid rgba(255, 71, 87, 0.2);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.btn-danger-custom:hover {
    background: rgba(255, 71, 87, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 71, 87, 0.2);
}

.btn-view-custom {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.2);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.btn-view-custom:hover {
    background: rgba(100, 255, 218, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(100, 255, 218, 0.2);
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

/* Assignments Container */
.assignments-container {
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

/* Assignments Table */
.assignments-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}

.assignments-table thead th {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--accent-blue);
    font-weight: 600;
    padding: 18px 20px;
    border-bottom: 2px solid rgba(100, 255, 218, 0.2);
    position: relative;
    overflow: hidden;
}

.assignments-table thead th::after {
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

.assignments-table tbody tr {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
    transition: all 0.3s ease;
}

@keyframes row-appear {
    to { opacity: 1; }
}

.assignments-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
}

.assignments-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    vertical-align: middle;
}

.assignments-table tr:last-child td {
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

/* Due Date Badge */
.due-date-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.due-future {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.3);
}

.due-today {
    background: rgba(255, 209, 102, 0.1);
    color: var(--accent-gold);
    border: 1px solid rgba(255, 209, 102, 0.3);
}

.due-past {
    background: rgba(255, 107, 157, 0.1);
    color: var(--accent-pink);
    border: 1px solid rgba(255, 107, 157, 0.3);
}

/* Subject Badge */
.subject-badge {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    
    .form-card, .assignments-container {
        padding: 20px;
    }
    
    .assignments-container {
        overflow-x: auto;
    }
    
    .assignments-table {
        min-width: 600px;
    }
    
    .btn-view-custom, .btn-danger-custom {
        padding: 6px 12px;
        font-size: 0.8rem;
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
                    <i class="fas fa-tasks"></i> Manage Assignments
                </h1>
                <div class="page-subtitle">
                    Create and manage assignments for your subjects
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Create Assignment Form -->
        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-plus-circle"></i> Create New Assignment
                </div>
                
                <?= $msg ?>
                
                <?php 
                // Reset subject pointer for form
                $subRes->data_seek(0);
                ?>
                
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-book"></i> Select Subject
                        </label>
                        <select class="form-select-custom" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php while($s = $subRes->fetch_assoc()): ?>
                                <option value="<?= (int)$s['id'] ?>">
                                    Grade <?= e($s['grade']) ?> - <?= e($s['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Students of that grade will see this assignment
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-heading"></i> Assignment Title
                        </label>
                        <input class="form-control-custom" name="title" required 
                               placeholder="Eg: Homework 01, Project Submission, Quiz 1">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i> Due Date
                        </label>
                        <input type="date" class="form-control-custom" name="due_date" required>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                            <i class="fas fa-clock"></i> Set deadline for submission
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i> Description & Instructions
                        </label>
                        <textarea class="form-control-custom" name="description" rows="4" 
                                  placeholder="Provide detailed instructions, requirements, and submission guidelines..."></textarea>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                            <i class="fas fa-edit"></i> Be clear about expectations and submission format
                        </div>
                    </div>
                    
                    <button class="btn-primary-custom w-100" name="create">
                        <i class="fas fa-plus"></i> Create Assignment
                    </button>
                </form>
            </div>
            
            <!-- Quick Stats -->
            <?php 
            $totalAssignments = $list->num_rows;
            $list->data_seek(0);
            $today = date('Y-m-d');
            $upcomingCount = 0;
            $overdueCount = 0;
            
            while($a = $list->fetch_assoc()) {
                if ($a['due_date'] > $today) $upcomingCount++;
                if ($a['due_date'] < $today) $overdueCount++;
            }
            $list->data_seek(0);
            ?>
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-chart-bar"></i> Assignment Stats
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div style="background: rgba(100, 255, 218, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(100, 255, 218, 0.2);">
                        <div style="color: var(--accent-blue); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $totalAssignments ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Total</div>
                    </div>
                    <div style="background: rgba(76, 217, 100, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(76, 217, 100, 0.2);">
                        <div style="color: var(--accent-green); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $upcomingCount ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Upcoming</div>
                    </div>
                    <div style="background: rgba(255, 107, 157, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(255, 107, 157, 0.2);">
                        <div style="color: var(--accent-pink); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $overdueCount ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Overdue</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Assignments List -->
        <div class="col-lg-8">
            <div class="assignments-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="form-title mb-0">
                            <i class="fas fa-list-check"></i> My Assignments
                        </h2>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                            Total: <?= $totalAssignments ?> assignments across all subjects
                        </div>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-calendar-alt"></i> <?= date('F j, Y') ?>
                    </div>
                </div>
                
                <!-- Assignments Table -->
                <div class="table-responsive">
                    <table class="assignments-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Grade</th>
                                <th style="width: 20%;">Subject</th>
                                <th style="width: 25%;">Assignment</th>
                                <th style="width: 15%;">Due Date</th>
                                <th style="width: 25%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($list->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-tasks"></i>
                                            </div>
                                            <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                                No Assignments Created
                                            </h3>
                                            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                                                You haven't created any assignments yet. Start by creating your first assignment!
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: 
                                $rowDelay = 0;
                                $today = date('Y-m-d');
                                while($a = $list->fetch_assoc()): 
                                    $rowDelay += 0.05;
                                    $dueDate = $a['due_date'];
                                    $dueStatus = '';
                                    
                                    if ($dueDate > $today) {
                                        $dueStatus = 'due-future';
                                        $dueIcon = 'fas fa-calendar-plus';
                                    } elseif ($dueDate == $today) {
                                        $dueStatus = 'due-today';
                                        $dueIcon = 'fas fa-exclamation-circle';
                                    } else {
                                        $dueStatus = 'due-past';
                                        $dueIcon = 'fas fa-calendar-times';
                                    }
                            ?>
                                <tr style="animation-delay: <?= $rowDelay ?>s;">
                                    <td>
                                        <span class="grade-badge">
                                            <i class="fas fa-graduation-cap"></i>
                                            Grade <?= e($a['grade']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="subject-badge">
                                            <i class="fas fa-book"></i>
                                            <?= e($a['subject_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-white); margin-bottom: 5px;">
                                            <?= e($a['title']) ?>
                                        </div>
                                        <?php if($a['description']): ?>
                                            <div style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.3;">
                                                <?= substr(e($a['description']), 0, 50) ?>...
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="due-date-badge <?= $dueStatus ?>">
                                            <i class="<?= $dueIcon ?>"></i>
                                            <?= e($a['due_date']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn-view-custom" 
                                               href="/classms/teacher/submissions.php?assignment_id=<?= (int)$a['id'] ?>">
                                                <i class="fas fa-users"></i> View Submissions
                                            </a>
                                            <a class="btn-danger-custom"
                                               onclick="return confirm('Are you sure you want to delete this assignment? This will also remove all submissions.')"
                                               href="?delete=<?= (int)$a['id'] ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Info Tip -->
                <div class="info-tip">
                    <i class="fas fa-lightbulb"></i>
                    <span style="color: var(--text-light);">
                        <b>Tip:</b> Click "View Submissions" to see which students have submitted work and to grade their submissions.
                        Assignments become visible to students immediately after creation.
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
    const rows = document.querySelectorAll('.assignments-table tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
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
    
    // Due date color coding
    const dueDates = document.querySelectorAll('.due-date-badge');
    dueDates.forEach(badge => {
        const today = new Date().toISOString().split('T')[0];
        const dueDate = badge.textContent.trim();
        
        // Set appropriate icon based on due date
        const icon = badge.querySelector('i');
        if (dueDate > today) {
            icon.className = 'fas fa-calendar-plus';
        } else if (dueDate === today) {
            icon.className = 'fas fa-exclamation-circle';
            // Add pulse animation for today's due dates
            badge.style.animation = 'pulse-today 2s infinite';
        } else {
            icon.className = 'fas fa-calendar-times';
        }
    });
    
    // Add pulse animation for today's due dates
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse-today {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(255, 209, 102, 0.4);
            }
            50% { 
                box-shadow: 0 0 0 6px rgba(255, 209, 102, 0);
            }
        }
    `;
    document.head.appendChild(style);
    
    // Delete confirmation with animation
    document.querySelectorAll('.btn-danger-custom').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this assignment? This will also remove all submissions.')) {
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
    const shakeStyle = document.createElement('style');
    shakeStyle.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(shakeStyle);
    
    // Grade badge hover effect
    const gradeBadges = document.querySelectorAll('.grade-badge');
    gradeBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(2deg)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>