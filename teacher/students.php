<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$tid = (int) current_user()['id'];
$msg = "";

/* -----------------------------
   DELETE student (optional)
----------------------------- */
if (isset($_GET['delete'])) {
  $del_id = (int)$_GET['delete'];

  $chk = $conn->prepare("SELECT id FROM users WHERE id=? AND role='student' LIMIT 1");
  $chk->bind_param("i", $del_id);
  $chk->execute();
  $ok = $chk->get_result()->fetch_assoc();

  if ($ok) {
    $conn->begin_transaction();
    try {
      $conn->prepare("DELETE FROM enrollments WHERE student_id={$del_id}")->execute();
      $conn->prepare("DELETE FROM attendance WHERE student_id={$del_id}")->execute();
      $conn->prepare("DELETE FROM term_marks WHERE student_id={$del_id}")->execute();
      $conn->prepare("DELETE FROM submissions WHERE student_id={$del_id}")->execute();
      $conn->prepare("DELETE FROM student_profiles WHERE user_id={$del_id}")->execute();
      $conn->prepare("DELETE FROM users WHERE id={$del_id} AND role='student'")->execute();
      $conn->commit();
      redirect("/classms/teacher/students.php");
    } catch(Exception $e) {
      $conn->rollback();
      $msg = "<div class='alert-danger'>❌ Delete failed: ".htmlspecialchars($e->getMessage())."</div>";
    }
  } else {
    $msg = "<div class='alert-danger'>Student not found.</div>";
  }
}

/* -----------------------------
   UPDATE student (CRUD)
----------------------------- */
if (isset($_POST['update_student'])) {
  $sid = (int)($_POST['student_id'] ?? 0);

  $grade = trim($_POST['grade'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $guardian_name = trim($_POST['guardian_name'] ?? '');
  $guardian_phone = trim($_POST['guardian_phone'] ?? '');
  $dob = trim($_POST['dob'] ?? '');

  $allowedGrades = ['6','7','8','9','10','11','12'];
  if (!in_array($grade, $allowedGrades, true)) {
    $msg = "<div class='alert-danger'>Invalid grade. Use 6-12.</div>";
  } else {

    $chk = $conn->prepare("SELECT id FROM users WHERE id=? AND role='student' LIMIT 1");
    $chk->bind_param("i", $sid);
    $chk->execute();
    $ok = $chk->get_result()->fetch_assoc();

    if (!$ok) {
      $msg = "<div class='alert-danger'>Student not found.</div>";
    } else {

      $u = $conn->prepare("UPDATE users SET phone=? WHERE id=? AND role='student'");
      $u->bind_param("si", $phone, $sid);
      $u->execute();

      $sp = $conn->prepare("
        INSERT INTO student_profiles(user_id, grade, address, guardian_name, guardian_phone, dob)
        VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          grade=VALUES(grade),
          address=VALUES(address),
          guardian_name=VALUES(guardian_name),
          guardian_phone=VALUES(guardian_phone),
          dob=VALUES(dob)
      ");
      $sp->bind_param("isssss", $sid, $grade, $address, $guardian_name, $guardian_phone, $dob);
      $sp->execute();

      $msg = "<div class='alert-success'>✅ Student updated successfully.</div>";
    }
  }
}

/* -----------------------------
   LOAD edit student (if edit)
----------------------------- */
$editStudent = null;
if (isset($_GET['edit'])) {
  $edit_id = (int)$_GET['edit'];

  $es = $conn->prepare("
    SELECT 
      u.id, u.full_name, u.email, u.phone,
      sp.grade, sp.address, sp.guardian_name, sp.guardian_phone, sp.dob
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    WHERE u.id=? AND u.role='student'
    LIMIT 1
  ");
  $es->bind_param("i", $edit_id);
  $es->execute();
  $editStudent = $es->get_result()->fetch_assoc();
}

/* -----------------------------
   SEARCH
----------------------------- */
$q = trim($_GET['q'] ?? '');
$grade = trim($_GET['grade'] ?? '');

$sql = "
  SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    sp.grade,
    sp.address,
    sp.guardian_name,
    sp.guardian_phone,
    sp.dob,
    u.created_at
  FROM users u
  LEFT JOIN student_profiles sp ON sp.user_id = u.id
  WHERE u.role='student'
";

$params = [];
$types = "";

if ($q !== "") {
  $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?) ";
  $like = "%$q%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= "sss";
}
if ($grade !== "") {
  $sql .= " AND sp.grade = ? ";
  $params[] = $grade;
  $types .= "s";
}

$sql .= " ORDER BY sp.grade+0 ASC, u.full_name ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

/* -----------------------------
   ✅ PREPARE notification query (fast)
----------------------------- */
$hasChatTable = false;
$chkT = $conn->query("SHOW TABLES LIKE 'chat_messages'");
if ($chkT && $chkT->num_rows > 0) $hasChatTable = true;

$unreadStmt = null;
if ($hasChatTable) {
  $unreadStmt = $conn->prepare("
    SELECT COUNT(*) c
    FROM chat_messages
    WHERE teacher_id=? AND student_id=? AND sender_role='student' AND seen_by_teacher=0
  ");
}

// Attendance + marks count prepared
$attStmt = $conn->prepare("SELECT COUNT(*) c FROM attendance WHERE student_id=?");
$markStmt = $conn->prepare("SELECT COUNT(*) c FROM term_marks WHERE student_id=?");

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
    max-width: 1600px;
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

.btn-danger-custom {
    background: transparent;
    color: var(--accent-red);
    border: 2px solid var(--accent-red);
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-danger-custom:hover {
    background: rgba(255, 71, 87, 0.1);
    color: var(--text-white);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 71, 87, 0.2);
}

.btn-edit-custom {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.2);
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-edit-custom:hover {
    background: rgba(100, 255, 218, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(100, 255, 218, 0.2);
}

.btn-chat-custom {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.btn-chat-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(168, 130, 255, 0.3);
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

/* Edit Form Card */
.edit-form-card {
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

/* Search Form */
.search-form {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 30px;
    animation: search-appear 0.6s ease-out;
}

@keyframes search-appear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Student Cards */
.student-card {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: card-appear 0.6s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
    overflow: hidden;
    position: relative;
}

@keyframes card-appear {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.student-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 20px 40px rgba(2, 12, 27, 0.8);
    border-color: var(--accent-blue);
}

.student-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.student-card:hover::before {
    opacity: 1;
}

/* Student Info */
.student-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--dark-blue);
    margin-right: 15px;
    animation: avatar-pulse 3s infinite;
}

@keyframes avatar-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.student-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-white);
    margin-bottom: 5px;
}

.student-grade {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    animation: badge-pulse 2s infinite;
}

@keyframes badge-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.student-id {
    color: var(--text-muted);
    font-size: 0.85rem;
}

/* Info Grid */
.info-grid {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 15px;
    margin: 15px 0;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.info-item {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-item i {
    color: var(--accent-blue);
    width: 18px;
}

.info-label {
    color: var(--text-muted);
    font-weight: 500;
    min-width: 120px;
}

/* Stats Badges */
.stats-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-attendance {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.2);
}

.badge-marks {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.2);
}

.badge-subjects {
    background: rgba(168, 130, 255, 0.1);
    color: var(--accent-purple);
    border: 1px solid rgba(168, 130, 255, 0.2);
}

/* Chat Notification Dot */
.chat-dot {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-red), #ff6b6b);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    color: white;
    animation: pulse-dot 1.5s infinite;
    box-shadow: 0 0 10px rgba(255, 107, 157, 0.5);
}

@keyframes pulse-dot {
    0%, 100% { 
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 107, 157, 0.7);
    }
    50% { 
        transform: scale(1.1);
        box-shadow: 0 0 0 10px rgba(255, 107, 157, 0);
    }
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
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
    
    .edit-form-card, .search-form {
        padding: 20px;
    }
    
    .student-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
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
                    <i class="fas fa-users"></i> Manage Students
                </h1>
                <div class="page-subtitle">
                    View and manage all student information with real-time chat notifications
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Edit Student Form -->
    <?php if($editStudent): ?>
    <div class="edit-form-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="form-title">
                    <i class="fas fa-user-edit"></i> Edit Student
                </h2>
                <div style="color: var(--text-muted); font-size: 0.95rem;">
                    Editing: <?= e($editStudent['full_name']) ?> (ID: <?= (int)$editStudent['id'] ?>)
                </div>
            </div>
            <a class="btn-outline-custom btn-sm" href="/classms/teacher/students.php">
                <i class="fas fa-times"></i> Close Edit
            </a>
        </div>
        
        <?= $msg ?>
        
        <form method="post">
            <input type="hidden" name="student_id" value="<?= (int)$editStudent['id'] ?>">
            
            <div class="row g-3">
                <!-- Basic Information -->
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input class="form-control-custom" value="<?= e($editStudent['full_name']) ?>" disabled>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input class="form-control-custom" value="<?= e($editStudent['email']) ?>" disabled>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input class="form-control-custom" name="phone" value="<?= e($editStudent['phone'] ?? '') ?>" 
                           placeholder="07XXXXXXXX">
                </div>
                
                <!-- Grade Selection -->
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-graduation-cap"></i> Grade
                    </label>
                    <select class="form-select-custom" name="grade" required>
                        <?php foreach(['6','7','8','9','10','11','12'] as $g): ?>
                            <option value="<?= $g ?>" <?= ((string)($editStudent['grade'] ?? '')===$g)?'selected':'' ?>>
                                Grade <?= $g ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Guardian Information -->
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-user-friends"></i> Guardian Name
                    </label>
                    <input class="form-control-custom" name="guardian_name" value="<?= e($editStudent['guardian_name'] ?? '') ?>"
                           placeholder="Parent/Guardian full name">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-phone-alt"></i> Guardian Phone
                    </label>
                    <input class="form-control-custom" name="guardian_phone" value="<?= e($editStudent['guardian_phone'] ?? '') ?>"
                           placeholder="Guardian contact number">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">
                        <i class="fas fa-birthday-cake"></i> Date of Birth
                    </label>
                    <input type="date" class="form-control-custom" name="dob" value="<?= e($editStudent['dob'] ?? '') ?>">
                </div>
                
                <!-- Address -->
                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-home"></i> Address
                    </label>
                    <input class="form-control-custom" name="address" value="<?= e($editStudent['address'] ?? '') ?>"
                           placeholder="Complete residential address">
                </div>
                
                <!-- Form Actions -->
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <button class="btn-primary-custom" name="update_student">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a class="btn-danger-custom"
                           onclick="return confirm('Are you sure you want to permanently delete this student? This action cannot be undone.')"
                           href="/classms/teacher/students.php?delete=<?= (int)$editStudent['id'] ?>">
                            <i class="fas fa-trash"></i> Delete Student
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php else: ?>
        <?= $msg ?>
    <?php endif; ?>
    
    <!-- Search Form -->
    <div class="search-form">
        <form method="get" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">
                    <i class="fas fa-search"></i> Search Students
                </label>
                <input class="form-control-custom" name="q" value="<?= e($q) ?>" 
                       placeholder="Search by name, email, or phone...">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-filter"></i> Filter by Grade
                </label>
                <input class="form-control-custom" name="grade" value="<?= e($grade) ?>" 
                       placeholder="Enter grade (6-12)">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="opacity: 0;">Search</label>
                <button class="btn-primary-custom w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="opacity: 0;">Reset</label>
                <a class="btn-outline-custom w-100 text-center" href="/classms/teacher/students.php">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Students Grid -->
    <?php if($res->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                No Students Found
            </h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                <?php if($q !== '' || $grade !== ''): ?>
                    No students match your search criteria. Try a different search.
                <?php else: ?>
                    No students are currently registered in the system.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php 
            $cardCount = 0;
            while($r = $res->fetch_assoc()):
                $sid = (int)$r['id'];
                $cardCount++;
                
                // Attendance count
                $attStmt->bind_param("i", $sid);
                $attStmt->execute();
                $attCount = (int)($attStmt->get_result()->fetch_assoc()['c'] ?? 0);
                
                // Marks count
                $markStmt->bind_param("i", $sid);
                $markStmt->execute();
                $markCount = (int)($markStmt->get_result()->fetch_assoc()['c'] ?? 0);
                
                // Unread messages count (student -> teacher)
                $unread = 0;
                if ($hasChatTable && $unreadStmt) {
                    $unreadStmt->bind_param("ii", $tid, $sid);
                    $unreadStmt->execute();
                    $unread = (int)($unreadStmt->get_result()->fetch_assoc()['c'] ?? 0);
                }
            ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="student-card" style="animation-delay: <?= $cardCount * 0.1 ?>s;">
                        <div class="p-4">
                            <!-- Student Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="student-avatar">
                                        <?= strtoupper(substr($r['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="student-name">
                                            <?= e($r['full_name']) ?>
                                            <?php if($unread > 0): ?>
                                                <span class="chat-dot"><?= $unread ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="student-grade">
                                                <i class="fas fa-graduation-cap"></i> Grade <?= e($r['grade'] ?? '-') ?>
                                            </span>
                                            <span class="student-id">
                                                <i class="fas fa-id-card"></i> ID: <?= $sid ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Student Information Grid -->
                            <div class="info-grid">
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span class="info-label">Email:</span>
                                    <span><?= e($r['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <span class="info-label">Phone:</span>
                                    <span><?= e($r['phone'] ?? 'Not provided') ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-user-friends"></i>
                                    <span class="info-label">Guardian:</span>
                                    <span><?= e($r['guardian_name'] ?? 'Not provided') ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-phone-alt"></i>
                                    <span class="info-label">Guardian Phone:</span>
                                    <span><?= e($r['guardian_phone'] ?? 'Not provided') ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <span class="info-label">Date of Birth:</span>
                                    <span><?= e($r['dob'] ?? 'Not provided') ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-home"></i>
                                    <span class="info-label">Address:</span>
                                    <span><?= e($r['address'] ?? 'Not provided') ?></span>
                                </div>
                            </div>
                            
                            <!-- Student Stats -->
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="stats-badge badge-attendance">
                                    <i class="fas fa-calendar-check"></i> Attendance: <?= $attCount ?>
                                </span>
                                <span class="stats-badge badge-marks">
                                    <i class="fas fa-chart-line"></i> Marks: <?= $markCount ?>
                                </span>
                                <span class="stats-badge badge-subjects">
                                    <i class="fas fa-book"></i> Enrolled
                                </span>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <a class="btn-edit-custom" href="/classms/teacher/students.php?edit=<?= $sid ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a class="btn-chat-custom position-relative" href="/classms/teacher/chat.php?student_id=<?= $sid ?>">
                                    <i class="fas fa-comments"></i> Chat
                                    <?php if($unread > 0): ?>
                                        <span class="chat-dot"><?= $unread ?></span>
                                    <?php endif; ?>
                                </a>
                                <a class="btn-edit-custom" href="/classms/teacher/attendance.php?student_id=<?= $sid ?>">
                                    <i class="fas fa-calendar-alt"></i> Attendance
                                </a>
                                <a class="btn-edit-custom" href="/classms/teacher/term_marks.php?student_id=<?= $sid ?>">
                                    <i class="fas fa-star"></i> Marks
                                </a>
                                <a class="btn-edit-custom" href="/classms/teacher/student_subjects.php?student_id=<?= $sid ?>">
                                    <i class="fas fa-book-open"></i> Subjects
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
// Create floating particles
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('particles-container');
    const particleCount = 25;
    
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
    
    // Card hover animations
    const cards = document.querySelectorAll('.student-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
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
    document.querySelectorAll('.btn-danger-custom').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to permanently delete this student? This action cannot be undone.')) {
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
    
    // Chat button glow for unread messages
    const chatButtons = document.querySelectorAll('.btn-chat-custom');
    chatButtons.forEach(btn => {
        const hasUnread = btn.querySelector('.chat-dot');
        if (hasUnread) {
            setInterval(() => {
                btn.style.boxShadow = btn.style.boxShadow ? 
                    '' : '0 0 15px rgba(168, 130, 255, 0.5)';
            }, 1000);
        }
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>