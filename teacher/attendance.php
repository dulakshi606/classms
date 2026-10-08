<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

date_default_timezone_set("Asia/Colombo");

$tid = (int) current_user()['id'];
$student_id = (int)($_GET['student_id'] ?? 0);
$action = trim($_GET['action'] ?? 'menu');
$msg = "";

// If no student_id, show message
if ($student_id <= 0) {
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <h4 class='fw-bold mb-2'>Attendance</h4>
          <div class='alert alert-danger mb-0'>Student ID missing. Please open from the Students page.</div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

// Load student info
$st = $conn->prepare("
  SELECT u.id, u.full_name, u.email, u.phone, sp.grade
  FROM users u
  LEFT JOIN student_profiles sp ON sp.user_id = u.id
  WHERE u.id=? AND u.role='student'
  LIMIT 1
");
$st->bind_param("i", $student_id);
$st->execute();
$student = $st->get_result()->fetch_assoc();

if (!$student) {
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <h4 class='fw-bold mb-2'>Attendance</h4>
          <div class='alert alert-danger mb-0'>Student not found.</div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

// MARK attendance
if (isset($_POST['mark_attendance'])) {
  $date = $_POST['attendance_date'] ?? date("Y-m-d");
  $status = $_POST['status'] ?? 'present';
  $note = trim($_POST['note'] ?? '');

  if (!in_array($status, ['present','absent'], true)) $status = 'present';

  $ins = $conn->prepare("
    INSERT INTO attendance(student_id, attendance_date, status, marked_at, note)
    VALUES (?, ?, ?, NOW(), ?)
    ON DUPLICATE KEY UPDATE status=VALUES(status), marked_at=NOW(), note=VALUES(note)
  ");
  $ins->bind_param("isss", $student_id, $date, $status, $note);
  $ins->execute();

  $msg = "<div class='alert-success'>✅ Attendance saved for <b>{$date}</b> with time.</div>";
  $action = "mark";
}

// Latest record (for showing time)
$latestQ = $conn->prepare("
  SELECT attendance_date, status, marked_at, note
  FROM attendance
  WHERE student_id=?
  ORDER BY marked_at DESC
  LIMIT 1
");
$latestQ->bind_param("i", $student_id);
$latestQ->execute();
$latest = $latestQ->get_result()->fetch_assoc();

// History (last 20)
$hisQ = $conn->prepare("
  SELECT attendance_date, status, marked_at, note
  FROM attendance
  WHERE student_id=?
  ORDER BY attendance_date DESC, marked_at DESC
  LIMIT 20
");
$hisQ->bind_param("i", $student_id);
$hisQ->execute();
$history = $hisQ->get_result();

// Statistics
$statsQ = $conn->prepare("
  SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent_days
  FROM attendance
  WHERE student_id=?
");
$statsQ->bind_param("i", $student_id);
$statsQ->execute();
$stats = $statsQ->get_result()->fetch_assoc();
$presentPercent = $stats['total_days'] > 0 ? round(($stats['present_days'] / $stats['total_days']) * 100, 1) : 0;

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

.btn-success-custom {
    background: linear-gradient(135deg, var(--accent-green), #38c172);
    color: white;
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

.btn-success-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(76, 217, 100, 0.4);
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

.alert-info {
    background: rgba(54, 185, 204, 0.1);
    color: #36b9cc;
    border: 1px solid rgba(54, 185, 204, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    backdrop-filter: blur(10px);
    animation: alert-slide 0.5s ease-out;
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

/* Options Cards */
.option-card {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 15px;
    padding: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: var(--card-shadow);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: block;
    animation: option-appear 0.6s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes option-appear {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.option-card:hover {
    transform: translateY(-10px) scale(1.02);
    border-color: var(--accent-blue);
    box-shadow: 0 20px 40px rgba(2, 12, 27, 0.8);
}

.option-icon {
    width: 70px;
    height: 70px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 2rem;
    animation: icon-float 3s ease-in-out infinite;
}

@keyframes icon-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.option-title {
    color: var(--text-white);
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.option-description {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Attendance Container */
.attendance-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: container-appear 0.8s ease-out forwards;
    opacity: 0;
}

@keyframes container-appear {
    to { opacity: 1; }
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--accent-blue);
    background: rgba(100, 255, 218, 0.05);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Progress Bar */
.progress-container {
    margin: 20px 0;
}

.progress-bar-custom {
    height: 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 1s ease-out;
    position: relative;
    overflow: hidden;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: progress-shine 2s infinite;
}

@keyframes progress-shine {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Attendance Table */
.attendance-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}

.attendance-table thead th {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--accent-blue);
    font-weight: 600;
    padding: 18px 20px;
    border-bottom: 2px solid rgba(100, 255, 218, 0.2);
    position: relative;
    overflow: hidden;
}

.attendance-table thead th::after {
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

.attendance-table tbody tr {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
    transition: all 0.3s ease;
}

@keyframes row-appear {
    to { opacity: 1; }
}

.attendance-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
}

.attendance-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    vertical-align: middle;
}

.attendance-table tr:last-child td {
    border-bottom: none;
}

/* Status Badges */
.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-present {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.3);
}

.badge-absent {
    background: rgba(255, 107, 157, 0.1);
    color: var(--accent-pink);
    border: 1px solid rgba(255, 107, 157, 0.3);
}

/* Time Badge */
.time-badge {
    color: var(--text-muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Student Info Card */
.student-info-card {
    background: linear-gradient(135deg, rgba(100, 255, 218, 0.1), rgba(168, 130, 255, 0.1));
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(100, 255, 218, 0.2);
    margin-bottom: 25px;
}

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
    
    .form-card, .attendance-container {
        padding: 20px;
    }
    
    .attendance-container {
        overflow-x: auto;
    }
    
    .attendance-table {
        min-width: 600px;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
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
                    <i class="fas fa-calendar-check"></i> Student Attendance
                </h1>
                <div class="page-subtitle">
                    Manage attendance for individual students with detailed history
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/students.php">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <!-- Student Info -->
        <div class="student-info-card mt-3">
            <div class="d-flex align-items-center">
                <div class="student-avatar">
                    <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size: 1.3rem; font-weight: 600; color: var(--text-white);">
                        <?= e($student['full_name']) ?>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 8px;">
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-id-card"></i> ID: <?= (int)$student['id'] ?>
                        </span>
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-graduation-cap"></i> Grade: <?= e($student['grade'] ?? '-') ?>
                        </span>
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-envelope"></i> <?= e($student['email']) ?>
                        </span>
                        <?php if($student['phone']): ?>
                            <span style="color: var(--text-muted);">
                                <i class="fas fa-phone"></i> <?= e($student['phone']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= $msg ?>

    <?php if($action === 'menu'): ?>
        <!-- Attendance Statistics -->
        <div class="stats-container">
            <div class="stat-card" style="animation-delay: 0.1s;">
                <div class="stat-value" style="color: var(--accent-blue);">
                    <?= $stats['total_days'] ?? 0 ?>
                </div>
                <div class="stat-label">Total Days</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.2s;">
                <div class="stat-value" style="color: var(--accent-green);">
                    <?= $stats['present_days'] ?? 0 ?>
                </div>
                <div class="stat-label">Present Days</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.3s;">
                <div class="stat-value" style="color: var(--accent-pink);">
                    <?= $stats['absent_days'] ?? 0 ?>
                </div>
                <div class="stat-label">Absent Days</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.4s;">
                <div class="stat-value" style="color: var(--accent-gold);">
                    <?= $presentPercent ?>%
                </div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>

        <!-- Attendance Rate Progress -->
        <div class="form-card">
            <div class="form-title">
                <i class="fas fa-chart-line"></i> Attendance Rate
            </div>
            <div class="progress-container">
                <div class="d-flex justify-content-between mb-2">
                    <span style="color: var(--text-light);">Current Attendance Rate</span>
                    <span style="color: var(--accent-gold); font-weight: 600;"><?= $presentPercent ?>%</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?= $presentPercent ?>%; background: linear-gradient(90deg, var(--accent-green), #38c172);"></div>
                </div>
            </div>
        </div>

        <!-- Options Menu -->
        <div class="row g-4">
            <div class="col-lg-6">
                <a href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>&action=mark" 
                   class="option-card" style="animation-delay: 0.1s;">
                    <div class="option-icon" style="background: rgba(100, 255, 218, 0.1); color: var(--accent-blue);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="option-title">Mark Attendance</div>
                    <div class="option-description">
                        Record daily attendance with status, date, and optional notes. Track present/absent status with timestamps.
                    </div>
                </a>
            </div>

            <div class="col-lg-6">
                <a href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>&action=history" 
                   class="option-card" style="animation-delay: 0.2s;">
                    <div class="option-icon" style="background: rgba(54, 185, 204, 0.1); color: #36b9cc;">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="option-title">View Attendance History</div>
                    <div class="option-description">
                        View complete attendance records with dates, statuses, timestamps, and notes for the last 20 entries.
                    </div>
                </a>
            </div>
        </div>

        <!-- Latest Record -->
        <?php if($latest): ?>
        <div class="attendance-container mt-4" style="animation-delay: 0.3s;">
            <div class="form-title">
                <i class="fas fa-clock"></i> Latest Attendance Record
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: rgba(255, 255, 255, 0.03); border-radius: 10px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 5px;">Date</div>
                    <div style="color: var(--text-white); font-size: 1.1rem; font-weight: 600;">
                        <i class="fas fa-calendar-alt me-2"></i><?= e($latest['attendance_date']) ?>
                    </div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.03); border-radius: 10px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 5px;">Status</div>
                    <span class="status-badge <?= $latest['status'] === 'present' ? 'badge-present' : 'badge-absent' ?>">
                        <i class="fas fa-<?= $latest['status'] === 'present' ? 'check' : 'times' ?>"></i>
                        <?= ucfirst(e($latest['status'])) ?>
                    </span>
                </div>
                <div style="background: rgba(255, 255, 255, 0.03); border-radius: 10px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 5px;">Time</div>
                    <div style="color: var(--text-white); font-size: 1.1rem; font-weight: 600;">
                        <i class="fas fa-clock me-2"></i><?= date('g:i A', strtotime($latest['marked_at'])) ?>
                    </div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.03); border-radius: 10px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 5px;">Note</div>
                    <div style="color: var(--text-white); font-size: 1.1rem; font-weight: 600;">
                        <i class="fas fa-sticky-note me-2"></i><?= e($latest['note'] ?? 'No notes') ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    <?php elseif($action === 'mark'): ?>
        <!-- Mark Attendance -->
        <div class="row">
            <!-- Attendance Form -->
            <div class="col-lg-6">
                <div class="form-card">
                    <div class="form-title">
                        <i class="fas fa-edit"></i> Mark Attendance
                    </div>
                    
                    <form method="post">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-calendar-day"></i> Attendance Date
                            </label>
                            <input type="date" class="form-control-custom" name="attendance_date" 
                                   value="<?= e($_POST['attendance_date'] ?? date('Y-m-d')) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-user-check"></i> Attendance Status
                            </label>
                            <select class="form-select-custom" name="status" required>
                                <option value="present">📗 Present - Student attended</option>
                                <option value="absent">📕 Absent - Student not present</option>
                            </select>
                            <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                                <i class="fas fa-info-circle"></i> Select student's attendance status
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-sticky-note"></i> Notes (Optional)
                            </label>
                            <input class="form-control-custom" name="note" 
                                   placeholder="E.g., Late by 15 minutes, Sick leave, Early dismissal"
                                   value="<?= e($_POST['note'] ?? '') ?>">
                            <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                                <i class="fas fa-edit"></i> Add any additional notes or remarks
                            </div>
                        </div>

                        <button class="btn-primary-custom w-100" name="mark_attendance">
                            <i class="fas fa-save"></i> Save Attendance Record
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent History -->
            <div class="col-lg-6">
                <div class="attendance-container">
                    <div class="form-title">
                        <i class="fas fa-history"></i> Recent Attendance
                    </div>
                    
                    <?php if($history->num_rows > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $history->data_seek(0);
                                    $rowDelay = 0;
                                    while($h = $history->fetch_assoc()): 
                                        $rowDelay += 0.05;
                                    ?>
                                        <tr style="animation-delay: <?= $rowDelay ?>s;">
                                            <td>
                                                <div style="font-weight: 500; color: var(--text-white);">
                                                    <?= e($h['attendance_date']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= $h['status'] === 'present' ? 'badge-present' : 'badge-absent' ?>">
                                                    <i class="fas fa-<?= $h['status'] === 'present' ? 'check' : 'times' ?>"></i>
                                                    <?= ucfirst(e($h['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="time-badge">
                                                    <i class="fas fa-clock"></i>
                                                    <?= date('g:i A', strtotime($h['marked_at'])) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                No Attendance Records
                            </h3>
                            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                                No attendance has been marked for this student yet. Mark your first attendance record!
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a class="btn-outline-custom w-100 text-center" 
                           href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>">
                            <i class="fas fa-arrow-left"></i> Back to Options
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif($action === 'history'): ?>
        <!-- History View -->
        <div class="attendance-container">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="form-title mb-0">
                        <i class="fas fa-list-check"></i> Attendance History
                    </h2>
                    <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                        Last 20 attendance records for <?= e($student['full_name']) ?>
                    </div>
                </div>
                <div>
                    <a class="btn-outline-custom me-2" href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a class="btn-success-custom" href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>&action=mark">
                        <i class="fas fa-plus"></i> Mark New
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Date</th>
                            <th style="width: 15%;">Day</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 20%;">Time Recorded</th>
                            <th style="width: 30%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history->num_rows > 0): 
                            $history->data_seek(0);
                            $rowDelay = 0;
                            while($h = $history->fetch_assoc()): 
                                $rowDelay += 0.05;
                                $dayOfWeek = date('l', strtotime($h['attendance_date']));
                        ?>
                            <tr style="animation-delay: <?= $rowDelay ?>s;">
                                <td>
                                    <div style="font-weight: 600; color: var(--text-white);">
                                        <?= e($h['attendance_date']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="color: var(--text-muted);">
                                        <?= $dayOfWeek ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $h['status'] === 'present' ? 'badge-present' : 'badge-absent' ?>">
                                        <i class="fas fa-<?= $h['status'] === 'present' ? 'check' : 'times' ?>"></i>
                                        <?= ucfirst(e($h['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="time-badge">
                                        <i class="fas fa-clock"></i>
                                        <?= date('M j, g:i A', strtotime($h['marked_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="color: var(--text-light); font-size: 0.9rem;">
                                        <?= e($h['note'] ?? 'No notes') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="fas fa-calendar-times"></i>
                                        </div>
                                        <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                            No Attendance History
                                        </h3>
                                        <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                                            No attendance records found for this student. Start by marking attendance.
                                        </p>
                                        <a class="btn-primary-custom mt-3" 
                                           href="/classms/teacher/attendance.php?student_id=<?= $student_id ?>&action=mark">
                                            <i class="fas fa-plus"></i> Mark First Attendance
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <?php if($history->num_rows > 0): ?>
            <div style="background: rgba(255, 255, 255, 0.03); border-radius: 10px; padding: 20px; margin-top: 30px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <i class="fas fa-chart-pie" style="color: var(--accent-blue); font-size: 1.2rem;"></i>
                    <div style="font-weight: 600; color: var(--text-white);">Attendance Summary</div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--accent-blue);"><?= $stats['total_days'] ?? 0 ?></div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Total Records</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--accent-green);"><?= $presentPercent ?>%</div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Attendance Rate</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--accent-green);"><?= $stats['present_days'] ?? 0 ?></div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Present Days</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--accent-pink);"><?= $stats['absent_days'] ?? 0 ?></div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Absent Days</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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
    
    // Card animations
    const cards = document.querySelectorAll('.option-card, .stat-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        
        card.addEventListener('mouseenter', function() {
            if (this.classList.contains('option-card')) {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            } else {
                this.style.transform = 'translateY(-5px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Row animations
    const rows = document.querySelectorAll('.attendance-table tbody tr');
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
    
    // Status badge hover effects
    const statusBadges = document.querySelectorAll('.status-badge');
    statusBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            if (this.classList.contains('badge-present')) {
                this.style.transform = 'scale(1.1) rotate(2deg)';
                this.style.boxShadow = '0 5px 15px rgba(76, 217, 100, 0.3)';
            } else {
                this.style.transform = 'scale(1.1) rotate(-2deg)';
                this.style.boxShadow = '0 5px 15px rgba(255, 107, 157, 0.3)';
            }
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Auto-set date to today
    const dateInput = document.querySelector('input[name="attendance_date"]');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Progress bar animation
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        // Store original width
        const originalWidth = progressFill.style.width;
        progressFill.style.width = '0%';
        
        // Animate to original width
        setTimeout(() => {
            progressFill.style.width = originalWidth;
        }, 300);
    }
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>