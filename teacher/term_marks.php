<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

date_default_timezone_set("Asia/Colombo");

$student_id = (int)($_GET['student_id'] ?? 0);
$term = trim($_GET['term'] ?? 'Term 1');
$allowedTerms = ['Term 1','Term 2','Term 3'];

if (!in_array($term, $allowedTerms, true)) $term = 'Term 1';

if ($student_id <= 0) {
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <div class='alert alert-danger mb-0'>Student ID missing.</div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

/* -----------------------------
   Load student info (grade)
------------------------------*/
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
          <div class='alert alert-danger mb-0'>Student not found.</div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

$grade = (string)($student['grade'] ?? '');
$msg = "";

/* -----------------------------
   Load 9 subjects for the grade
   (approved only)
------------------------------*/
$subQ = $conn->prepare("
  SELECT id, name
  FROM subjects
  WHERE grade=? AND status='approved'
  ORDER BY name ASC
  LIMIT 9
");
$subQ->bind_param("s", $grade);
$subQ->execute();
$subjects = $subQ->get_result();

/* If less than 9 subjects exist, still continue */
$subjectRows = [];
while($s = $subjects->fetch_assoc()) $subjectRows[] = $s;

/* -----------------------------
   Existing marks for this term
------------------------------*/
$existing = [];
if (count($subjectRows) > 0) {
  $ex = $conn->prepare("
    SELECT subject_id, marks, grade
    FROM term_marks
    WHERE student_id=? AND term=?
  ");
  $ex->bind_param("is", $student_id, $term);
  $ex->execute();
  $exRes = $ex->get_result();
  while($r = $exRes->fetch_assoc()){
    $existing[(int)$r['subject_id']] = $r;
  }
}

/* -----------------------------
   Calculate statistics
------------------------------*/
$totalMarks = 0;
$validCount = 0;
$average = 0;
$maxMark = 0;
$minMark = 100;

foreach ($existing as $subjectId => $data) {
    if (!empty($data['marks']) && is_numeric($data['marks'])) {
        $mark = (float)$data['marks'];
        $totalMarks += $mark;
        $validCount++;
        $maxMark = max($maxMark, $mark);
        $minMark = min($minMark, $mark);
    }
}

if ($validCount > 0) {
    $average = round($totalMarks / $validCount, 2);
}

/* -----------------------------
   Save marks (bulk)
------------------------------*/
if (isset($_POST['save_marks'])) {
  $termPost = trim($_POST['term'] ?? $term);
  if (!in_array($termPost, $allowedTerms, true)) $termPost = 'Term 1';

  $marksArr = $_POST['marks'] ?? [];
  $gradeArr = $_POST['grade'] ?? [];

  $conn->begin_transaction();

  try {
    $up = $conn->prepare("
      INSERT INTO term_marks (student_id, subject_id, term, marks, grade)
      VALUES (?,?,?,?,?)
      ON DUPLICATE KEY UPDATE marks=VALUES(marks), grade=VALUES(grade), updated_at=NOW()
    ");

    foreach($subjectRows as $s){
      $sid = (int)$s['id'];

      $m = trim($marksArr[$sid] ?? '');
      $gval = trim($gradeArr[$sid] ?? '');

      // marks validation (allow empty)
      $marksVal = null;
      if ($m !== '') {
        $marksVal = (float)$m;
        if ($marksVal < 0) $marksVal = 0;
        if ($marksVal > 100) $marksVal = 100;
      }

      // grade validation (allow empty)
      $allowedGrades = ['A','B','C','S','F',''];
      if (!in_array($gval, $allowedGrades, true)) $gval = '';

      // bind (marks can be null => use variable + "d" will cast 0; so handle with string)
      // easiest: store marks as NULL by using set to null and bind as string
      $marksBind = ($marksVal === null) ? null : $marksVal;

      // mysqli doesn't bind null nicely with "d" sometimes, so bind as string for marks
      $marksStr = ($marksBind === null) ? null : (string)$marksBind;

      $up->bind_param("iisss", $student_id, $sid, $termPost, $marksStr, $gval);
      $up->execute();
    }

    $conn->commit();
    $term = $termPost;
    $msg = "<div class='alert-success'>✅ Marks saved successfully for <b>".e($term)."</b>.</div>";

    // Reload existing after save
    $existing = [];
    $ex = $conn->prepare("
      SELECT subject_id, marks, grade
      FROM term_marks
      WHERE student_id=? AND term=?
    ");
    $ex->bind_param("is", $student_id, $term);
    $ex->execute();
    $exRes = $ex->get_result();
    while($r = $exRes->fetch_assoc()){
      $existing[(int)$r['subject_id']] = $r;
    }

  } catch(Exception $e) {
    $conn->rollback();
    $msg = "<div class='alert-danger'>❌ Save failed: ".htmlspecialchars($e->getMessage())."</div>";
  }
}

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

@keyframes float {
    0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.3; }
    90% { opacity: 0.3; }
    100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
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

.alert-warning {
    background: rgba(255, 209, 102, 0.1);
    color: var(--accent-gold);
    border: 1px solid rgba(255, 209, 102, 0.3);
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

/* Term Selector */
.term-selector {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 30px;
    animation: selector-appear 0.6s ease-out;
}

@keyframes selector-appear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

/* Marks Table */
.marks-container {
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

.marks-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}

.marks-table thead th {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--accent-blue);
    font-weight: 600;
    padding: 18px 20px;
    border-bottom: 2px solid rgba(100, 255, 218, 0.2);
    position: relative;
    overflow: hidden;
}

.marks-table thead th::after {
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

.marks-table tbody tr {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
    transition: all 0.3s ease;
}

@keyframes row-appear {
    to { opacity: 1; }
}

.marks-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
}

.marks-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    vertical-align: middle;
}

.marks-table tr:last-child td {
    border-bottom: none;
}

/* Subject Row */
.subject-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.subject-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(100, 255, 218, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-blue);
    font-size: 1.2rem;
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
    animation: stat-card-appear 0.6s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes stat-card-appear {
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

/* Grade Badge */
.grade-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
}

.grade-A {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.3);
}

.grade-B {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.3);
}

.grade-C {
    background: rgba(255, 209, 102, 0.1);
    color: var(--accent-gold);
    border: 1px solid rgba(255, 209, 102, 0.3);
}

.grade-S {
    background: rgba(168, 130, 255, 0.1);
    color: var(--accent-purple);
    border: 1px solid rgba(168, 130, 255, 0.3);
}

.grade-F {
    background: rgba(255, 107, 157, 0.1);
    color: var(--accent-pink);
    border: 1px solid rgba(255, 107, 157, 0.3);
}

.grade-empty {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    border: 1px solid rgba(255, 255, 255, 0.1);
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
    animation: avatar-pulse 3s infinite;
}

@keyframes avatar-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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
    
    .form-card, .marks-container {
        padding: 20px;
    }
    
    .marks-container {
        overflow-x: auto;
    }
    
    .marks-table {
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

/* Bootstrap overrides */
.card {
    background-color: var(--medium-blue) !important;
    border-color: rgba(100, 255, 218, 0.1) !important;
    color: var(--text-white) !important;
}

.text-muted {
    color: var(--text-muted) !important;
}

.fw-bold, .fw-semibold {
    color: var(--text-white) !important;
}

.table-dark {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue)) !important;
    color: var(--accent-blue) !important;
}

.table-bordered {
    border-color: rgba(100, 255, 218, 0.1) !important;
}

.table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: rgba(100, 255, 218, 0.05) !important;
    color: var(--text-light) !important;
}

.table-striped > tbody > tr:nth-of-type(even) > * {
    background-color: var(--medium-blue) !important;
    color: var(--text-light) !important;
}

.form-select, .form-control {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
}

.form-select:focus, .form-control:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    color: var(--text-white) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
}

.btn-dark {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8) !important;
    color: var(--dark-blue) !important;
    border: none !important;
}

.btn-outline-dark {
    background: transparent !important;
    color: var(--accent-blue) !important;
    border: 2px solid var(--accent-blue) !important;
}

.btn-outline-dark:hover {
    background: rgba(100, 255, 218, 0.1) !important;
    color: var(--text-white) !important;
}

.alert-success {
    background: rgba(100, 255, 218, 0.1) !important;
    color: var(--accent-blue) !important;
    border-color: rgba(100, 255, 218, 0.3) !important;
}

.alert-danger {
    background: rgba(255, 107, 157, 0.1) !important;
    color: var(--accent-pink) !important;
    border-color: rgba(255, 107, 157, 0.3) !important;
}

.alert-warning {
    background: rgba(255, 209, 102, 0.1) !important;
    color: var(--accent-gold) !important;
    border-color: rgba(255, 209, 102, 0.3) !important;
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
                    <i class="fas fa-chart-line"></i> Term Marks
                </h1>
                <div class="page-subtitle">
                    Enter and manage term marks for individual students
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/students.php">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <!-- Student Info -->
        <div class="student-info-card">
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
                            <i class="fas fa-graduation-cap"></i> Grade: <?= e($grade) ?>
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

    <?php if(count($subjectRows) === 0): ?>
        <div class="alert-warning">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem; color: var(--accent-gold);"></i>
                <div>
                    <div style="font-weight: 600; color: var(--text-white); margin-bottom: 5px;">No Approved Subjects Found</div>
                    <div style="color: var(--text-light);">
                        No approved subjects found for Grade <?= e($grade) ?>.
                        Please add subjects in <a href="/classms/teacher/subjects.php" style="color: var(--accent-blue);">teacher/subjects.php</a> and approve them.
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Term Statistics -->
        <div class="stats-container">
            <div class="stat-card" style="animation-delay: 0.1s;">
                <div class="stat-value" style="color: var(--accent-blue);">
                    <?= count($subjectRows) ?>
                </div>
                <div class="stat-label">Subjects</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.2s;">
                <div class="stat-value" style="color: var(--accent-green);">
                    <?= $validCount ?>
                </div>
                <div class="stat-label">Marks Entered</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.3s;">
                <div class="stat-value" style="color: var(--accent-gold);">
                    <?= $average ?>
                </div>
                <div class="stat-label">Average</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.4s;">
                <div class="stat-value" style="color: var(--accent-purple);">
                    <?= date('M Y') ?>
                </div>
                <div class="stat-label">Current Term</div>
            </div>
        </div>

        <!-- Term Selector -->
        <div class="term-selector">
            <div class="form-label">
                <i class="fas fa-calendar-alt"></i> Select Term
            </div>
            <form method="get">
                <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <select class="form-select-custom" name="term" onchange="this.form.submit()">
                            <?php foreach($allowedTerms as $t): ?>
                                <option value="<?= e($t) ?>" <?= ($term===$t)?'selected':'' ?>>
                                    📅 <?= e($t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-center">
                        <div style="color: var(--text-muted); font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> Select term to enter or view marks. Currently viewing: <b><?= e($term) ?></b>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Marks Entry Form -->
        <form method="post">
            <input type="hidden" name="term" value="<?= e($term) ?>">
            
            <div class="marks-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="form-title mb-0">
                            <i class="fas fa-edit"></i> Enter Marks for <?= e($term) ?>
                        </h2>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                            Enter marks (0-100) and select grades for each subject
                        </div>
                    </div>
                    <div>
                        <button class="btn-primary-custom" name="save_marks">
                            <i class="fas fa-save"></i> Save All Marks
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Subject</th>
                                <th style="width: 180px;">Marks (0-100)</th>
                                <th style="width: 150px;">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            $rowDelay = 0;
                            foreach($subjectRows as $s): 
                                $sid = (int)$s['id'];
                                $oldMarks = $existing[$sid]['marks'] ?? '';
                                $oldGrade = $existing[$sid]['grade'] ?? '';
                                $rowDelay += 0.05;
                                
                                // Determine grade class
                                $gradeClass = 'grade-empty';
                                if ($oldGrade === 'A') $gradeClass = 'grade-A';
                                elseif ($oldGrade === 'B') $gradeClass = 'grade-B';
                                elseif ($oldGrade === 'C') $gradeClass = 'grade-C';
                                elseif ($oldGrade === 'S') $gradeClass = 'grade-S';
                                elseif ($oldGrade === 'F') $gradeClass = 'grade-F';
                            ?>
                                <tr style="animation-delay: <?= $rowDelay ?>s;">
                                    <td>
                                        <div style="color: var(--text-muted); font-weight: 600; text-align: center;">
                                            <?= $i++ ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="subject-row">
                                            <div class="subject-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: var(--text-white);">
                                                    <?= e($s['name']) ?>
                                                </div>
                                                <div style="color: var(--text-muted); font-size: 0.85rem;">
                                                    Grade <?= e($grade) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number"
                                               class="form-control-custom"
                                               name="marks[<?= $sid ?>]"
                                               value="<?= e($oldMarks) ?>"
                                               min="0" max="100" step="0.01"
                                               placeholder="Enter marks (0-100)">
                                        <?php if($oldMarks !== ''): ?>
                                            <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">
                                                <i class="fas fa-clock"></i> Last updated
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="form-select-custom" name="grade[<?= $sid ?>]">
                                            <?php
                                                $opts = [
                                                    '' => 'Select Grade',
                                                    'A' => 'A - Excellent (75-100)',
                                                    'B' => 'B - Good (65-74)',
                                                    'C' => 'C - Average (50-64)',
                                                    'S' => 'S - Satisfactory (40-49)',
                                                    'F' => 'F - Fail (0-39)'
                                                ];
                                                foreach($opts as $val=>$label):
                                            ?>
                                                <option value="<?= e($val) ?>" <?= ($oldGrade===$val)?'selected':'' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if($oldGrade !== ''): ?>
                                            <div class="mt-2">
                                                <span class="grade-badge <?= $gradeClass ?>">
                                                    <?= e($oldGrade) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <!-- Key Information -->
        <div class="alert-warning">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-info-circle" style="font-size: 1.2rem; color: var(--accent-gold);"></i>
                <div>
                    <div style="font-weight: 600; color: var(--text-white); margin-bottom: 10px;">Grading Information</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <span style="color: var(--accent-blue); font-weight: 600;">A:</span> 
                            <span style="color: var(--text-muted);">Excellent (75-100 marks)</span>
                        </div>
                        <div>
                            <span style="color: var(--accent-green); font-weight: 600;">B:</span> 
                            <span style="color: var(--text-muted);">Good (65-74 marks)</span>
                        </div>
                        <div>
                            <span style="color: var(--accent-gold); font-weight: 600;">C:</span> 
                            <span style="color: var(--text-muted);">Average (50-64 marks)</span>
                        </div>
                        <div>
                            <span style="color: var(--accent-purple); font-weight: 600;">S:</span> 
                            <span style="color: var(--text-muted);">Satisfactory (40-49 marks)</span>
                        </div>
                        <div>
                            <span style="color: var(--accent-pink); font-weight: 600;">F:</span> 
                            <span style="color: var(--text-muted);">Fail (0-39 marks)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Create floating particles
document.addEventListener('DOMContentLoaded', function() {
    const particlesContainer = document.getElementById('particles-container');
    const colors = ['#64ffda', '#ff6b9d', '#ffd166', '#a882ff'];
    
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random properties
        const size = Math.random() * 4 + 2;
        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100;
        const delay = Math.random() * 20;
        
        // Apply styles
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.background = color;
        particle.style.left = `${left}%`;
        particle.style.animationDelay = `${delay}s`;
        
        particlesContainer.appendChild(particle);
    }
});

// Auto-save reminder
setTimeout(() => {
    const saveButton = document.querySelector('[name="save_marks"]');
    if (saveButton) {
        const reminder = document.createElement('div');
        reminder.className = 'alert-success';
        reminder.style.marginTop = '20px';
        reminder.innerHTML = '<i class="fas fa-lightbulb"></i> <b>Tip:</b> Remember to save your changes before leaving the page.';
        saveButton.parentNode.appendChild(reminder);
        
        setTimeout(() => {
            reminder.style.opacity = '0';
            reminder.style.transition = 'opacity 1s';
            setTimeout(() => reminder.remove(), 1000);
        }, 5000);
    }
}, 10000);
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>