<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");

$uid = (int) current_user()['id'];
$msg = "";

/* ---------------------------
   ✅ Get student grade (and CLEAN it)
----------------------------*/
$g = $conn->prepare("SELECT grade FROM student_profiles WHERE user_id=? LIMIT 1");
$g->bind_param("i",$uid);
$g->execute();
$rawGrade = (string)($g->get_result()->fetch_assoc()['grade'] ?? '');

/*
  ✅ FIX: if grade saved like "Grade 6" or "6 " -> extract number 6-12
*/
$grade = "";
if (preg_match('/(6|7|8|9|10|11|12)/', $rawGrade, $m)) {
  $grade = $m[1];
}

if($grade === ''){
  die("Student grade not found or invalid. Please set grade as 6-12 in student_profiles.");
}

/* ---------------------------
   SUBMIT / UPDATE SUBMISSION
----------------------------*/
if(isset($_POST['submit_assignment'])){
  $assignment_id = (int)($_POST['assignment_id'] ?? 0);

  if($assignment_id<=0){
    $msg="<div class='alert alert-danger animated-alert'>Invalid assignment.</div>";
  } elseif(!isset($_FILES['file']) || $_FILES['file']['error']!==0){
    $msg="<div class='alert alert-danger animated-alert'>Please choose a file.</div>";
  } else {

    // ✅ Confirm assignment belongs to this student's grade
    $chk = $conn->prepare("
      SELECT a.id
      FROM assignments a
      JOIN subjects s ON s.id=a.subject_id
      WHERE a.id=? AND s.grade=? AND s.status='approved'
      LIMIT 1
    ");
    // ✅ grade is numeric -> use "ii"
    $chk->bind_param("ii",$assignment_id,$grade);
    $chk->execute();

    if(!$chk->get_result()->fetch_assoc()){
      $msg="<div class='alert alert-danger animated-alert'>You cannot submit for this assignment (wrong grade or not approved).</div>";
    } else {

      $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
      $allowed = ['pdf','doc','docx','png','jpg','jpeg','zip'];

      if(!in_array($ext,$allowed,true)){
        $msg="<div class='alert alert-danger animated-alert'>Invalid file type (pdf/doc/docx/png/jpg/jpeg/zip).</div>";
      } else {

        $dir = __DIR__ . "/../uploads/submissions/";
        if(!is_dir($dir)) mkdir($dir,0777,true);

        $newName = "sub_{$uid}_{$assignment_id}_" . time() . "." . $ext;
        $path = "/classms/uploads/submissions/" . $newName;

        if(move_uploaded_file($_FILES['file']['tmp_name'], $dir.$newName)){

          // ✅ 1 submission per student per assignment
          $st=$conn->prepare("
            INSERT INTO submissions(assignment_id, student_id, file_path, submitted_at)
            VALUES (?,?,?,NOW())
            ON DUPLICATE KEY UPDATE
              file_path=VALUES(file_path),
              submitted_at=NOW()
          ");
          $st->bind_param("iis",$assignment_id,$uid,$path);
          $st->execute();

          $msg="<div class='alert alert-success animated-alert'>✅ Submitted successfully!</div>";
        } else {
          $msg="<div class='alert alert-danger animated-alert'>Upload failed. Check folder permissions.</div>";
        }
      }
    }
  }
}

/* ---------------------------
   ✅ LIST ASSIGNMENTS (GRADE BASED)
   Shows ALL assignments for student's grade
----------------------------*/
$q = trim($_GET['q'] ?? '');

$sql = "
  SELECT 
    a.id, a.title, a.description, a.due_date,
    s.name subject_name, s.grade,
    sub.file_path submitted_file, sub.marks, sub.feedback, sub.submitted_at
  FROM assignments a
  JOIN subjects s ON s.id=a.subject_id
  LEFT JOIN submissions sub 
    ON sub.assignment_id=a.id AND sub.student_id=?
  WHERE s.grade=? AND s.status='approved'
";

$params = [$uid, $grade];
$types  = "ii"; // ✅ uid int, grade int

if($q !== ''){
  $sql .= " AND (a.title LIKE ? OR s.name LIKE ?) ";
  $like = "%$q%";
  $params[] = $like; $params[] = $like;
  $types .= "ss";
}

$sql .= " ORDER BY a.due_date DESC, a.id DESC";

$list = $conn->prepare($sql);
$list->bind_param($types, ...$params);
$list->execute();
$res = $list->get_result();

require_once __DIR__ . "/../includes/header.php";
?>

<style>
:root {
  --primary-blue: #0d1b2a;
  --secondary-blue: #1b263b;
  --accent-blue: #415a77;
  --light-blue: #778da9;
  --text-light: #e0e1dd;
  --gradient-primary: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
  --gradient-secondary: linear-gradient(135deg, #1b263b 0%, #415a77 100%);
  --gradient-success: linear-gradient(135deg, #28a745 0%, #218838 100%);
  --gradient-warning: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
  --gradient-danger: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

body {
  background: var(--gradient-primary);
  color: var(--text-light);
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Animated Background */
.animated-bg {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
  overflow: hidden;
}

.bg-particle {
  position: absolute;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 50%;
  animation: float 20s infinite linear;
}

.bg-particle:nth-child(1) {
  width: 250px;
  height: 250px;
  top: 15%;
  left: 10%;
  animation-delay: 0s;
}

.bg-particle:nth-child(2) {
  width: 180px;
  height: 180px;
  top: 70%;
  right: 15%;
  animation-delay: -7s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  25% {
    transform: translateY(-20px) rotate(90deg);
  }
  50% {
    transform: translateY(0) rotate(180deg);
  }
  75% {
    transform: translateY(20px) rotate(270deg);
  }
}

/* Card Styles */
.card-soft {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  animation: cardEntrance 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes cardEntrance {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Assignment Card */
.assignment-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  animation: assignmentEntrance 0.6s ease-out;
  animation-fill-mode: both;
  overflow: hidden;
  position: relative;
}

.assignment-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--gradient-secondary);
  opacity: 0.7;
}

.assignment-card:nth-child(1) { animation-delay: 0.1s; }
.assignment-card:nth-child(2) { animation-delay: 0.2s; }
.assignment-card:nth-child(3) { animation-delay: 0.3s; }
.assignment-card:nth-child(4) { animation-delay: 0.4s; }
.assignment-card:nth-child(5) { animation-delay: 0.5s; }

.assignment-card:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-5px) scale(1.02);
  border-color: var(--accent-blue);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

@keyframes assignmentEntrance {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Due Date Badge */
.due-date {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  animation: badgePulse 2s infinite;
}

.due-date.urgent {
  background: var(--gradient-danger);
  animation: dangerPulse 2s infinite;
}

.due-date.warning {
  background: var(--gradient-warning);
  color: #000;
  animation: warningPulse 2s infinite;
}

.due-date.normal {
  background: var(--gradient-secondary);
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

@keyframes dangerPulse {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
  }
  50% { 
    box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
  }
}

@keyframes warningPulse {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
  }
  50% { 
    box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
  }
}

/* Subject Badge */
.subject-badge {
  background: rgba(65, 90, 119, 0.2);
  border: 1px solid rgba(65, 90, 119, 0.4);
  border-radius: 12px;
  padding: 4px 12px;
  font-size: 0.85rem;
  font-weight: 500;
  display: inline-block;
  transition: all 0.3s ease;
}

.subject-badge:hover {
  background: rgba(65, 90, 119, 0.4);
  transform: translateY(-2px);
}

/* Form Styles */
.form-control {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: var(--text-light);
  padding: 12px 16px;
  transition: all 0.3s ease;
}

.form-control:focus {
  background: rgba(255, 255, 255, 0.12);
  border-color: var(--accent-blue);
  box-shadow: 0 0 0 3px rgba(65, 90, 119, 0.2);
  color: white;
  transform: translateY(-2px);
}

.form-control::placeholder {
  color: rgba(224, 225, 221, 0.5);
}

/* File Input Customization */
.custom-file-input {
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.custom-file-input input[type="file"] {
  position: absolute;
  top: 0;
  right: 0;
  min-width: 100%;
  min-height: 100%;
  font-size: 100px;
  text-align: right;
  filter: alpha(opacity=0);
  opacity: 0;
  outline: none;
  cursor: pointer;
  display: block;
}

.file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 2px dashed rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: var(--text-light);
  transition: all 0.3s ease;
  cursor: pointer;
}

.file-label:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--accent-blue);
  transform: translateY(-2px);
}

/* Button Styles */
.btn-dark {
  background: var(--gradient-secondary);
  border: none;
  border-radius: 12px;
  padding: 12px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-dark:hover {
  background: linear-gradient(135deg, #415a77 0%, #1b263b 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.btn-dark::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  transition: left 0.7s;
}

.btn-dark:hover::after {
  left: 100%;
}

.btn-outline-dark {
  background: transparent;
  border: 2px solid var(--accent-blue);
  color: var(--text-light);
  border-radius: 12px;
  padding: 8px 16px;
  transition: all 0.3s ease;
}

.btn-outline-dark:hover {
  background: var(--accent-blue);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.btn-success {
  background: var(--gradient-success);
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
}

/* Alert Animations */
.animated-alert {
  animation: slideIn 0.5s ease-out;
  border: none;
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.alert-success {
  background: rgba(40, 167, 69, 0.15);
  border: 1px solid rgba(40, 167, 69, 0.3);
  color: #a3e9b4;
}

.alert-danger {
  background: rgba(220, 53, 69, 0.15);
  border: 1px solid rgba(220, 53, 69, 0.3);
  color: #f5a9b5;
}

.alert-info {
  background: rgba(23, 162, 184, 0.15);
  border: 1px solid rgba(23, 162, 184, 0.3);
  color: #a3e9f7;
}

/* Header Styles */
h4.fw-bold {
  background: linear-gradient(45deg, #e0e1dd, #778da9);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
  padding-bottom: 10px;
}

h4.fw-bold::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 60px;
  height: 3px;
  background: var(--accent-blue);
  border-radius: 2px;
  animation: lineExpand 2s ease-in-out infinite alternate;
}

@keyframes lineExpand {
  0% {
    width: 60px;
  }
  100% {
    width: 150px;
  }
}

.text-muted {
  color: rgba(224, 225, 221, 0.6) !important;
}

/* Status Indicators */
.submission-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-submitted {
  background: rgba(40, 167, 69, 0.15);
  border: 1px solid rgba(40, 167, 69, 0.3);
  color: #a3e9b4;
}

.status-pending {
  background: rgba(255, 193, 7, 0.15);
  border: 1px solid rgba(255, 193, 7, 0.3);
  color: #ffeaa7;
}

.status-icon {
  animation: gentlePulse 2s infinite;
}

@keyframes gentlePulse {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

/* Marks Display */
.marks-display {
  font-size: 1.25rem;
  font-weight: bold;
  padding: 8px 16px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  display: inline-block;
  animation: marksFloat 3s ease-in-out infinite;
}

@keyframes marksFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  animation: fadeIn 1s ease-out;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
  animation: gentlePulse 3s infinite;
}

/* File Type Icons */
.file-type-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  font-size: 1.5rem;
  margin-right: 12px;
  transition: all 0.3s ease;
}

.file-type-icon:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: scale(1.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .card-soft {
    margin: 10px;
    padding: 15px !important;
  }
  
  .assignment-card {
    margin: 10px 0;
  }
  
  .btn-dark, .btn-outline-dark {
    padding: 10px 16px;
    font-size: 0.9rem;
  }
}
</style>

<!-- Animated Background -->
<div class="animated-bg">
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
</div>

<div class="container py-4">
  <div class="card card-soft p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1 d-flex align-items-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-journal-text me-2" viewBox="0 0 16 16">
            <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
          </svg>
          Assignments
        </h4>
        <div class="text-muted d-flex align-items-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-vcard me-1" viewBox="0 0 16 16">
            <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm4-2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5ZM9 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 9 8Zm1 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5Z"/>
            <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2ZM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8.96c.026-.163.04-.33.04-.5C9 10.567 7.21 9 5 9c-2.086 0-3.8 1.398-3.984 3.181A1.006 1.006 0 0 1 1 12V4Z"/>
          </svg>
          Grade <?= e($grade) ?> • All assignments for your grade
        </div>
      </div>
      <a class="btn btn-outline-dark" href="/classms/student/dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-1" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Back to Dashboard
      </a>
    </div>

    <?= $msg ?>

    <!-- Search Form -->
    <form class="row g-3 mb-4" method="get">
      <div class="col-lg-9 col-md-8">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-end-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
              <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
          </span>
          <input class="form-control border-start-0" name="q" value="<?= e($q) ?>" placeholder="Search assignment title or subject name...">
        </div>
      </div>
      <div class="col-lg-3 col-md-4 d-grid">
        <button class="btn btn-dark">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel me-1" viewBox="0 0 16 16">
            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2h-11z"/>
          </svg>
          Filter Assignments
        </button>
      </div>
    </form>

    <?php if($res->num_rows===0): ?>
      <div class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-journal-x" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M6.146 6.146a.5.5 0 0 1 .708 0L8 7.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 8l1.147 1.146a.5.5 0 0 1-.708.708L8 8.707 6.854 9.854a.5.5 0 0 1-.708-.708L7.293 8 6.146 6.854a.5.5 0 0 1 0-.708z"/>
            <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
            <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
          </svg>
        </div>
        <h5 class="text-muted mb-3">No Assignments Found</h5>
        <p class="text-muted small mb-4">
          No assignments found for Grade <?= e($grade) ?>.<br>
          <small>Check: subjects.grade must be <?= e($grade) ?> and subjects.status must be 'approved'.</small>
        </p>
        <a href="/classms/student/assignments.php" class="btn btn-outline-dark">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
          </svg>
          Refresh Page
        </a>
      </div>
    <?php endif; ?>

    <?php while($a=$res->fetch_assoc()): 
      // Determine due date urgency
      $dueDate = new DateTime($a['due_date']);
      $today = new DateTime();
      $interval = $today->diff($dueDate);
      $daysUntilDue = $interval->days;
      $isOverdue = $today > $dueDate;
      
      $dueDateClass = 'normal';
      if($isOverdue) {
        $dueDateClass = 'urgent';
      } elseif($daysUntilDue <= 3) {
        $dueDateClass = 'warning';
      }
      
      // Submission status
      $hasSubmission = !empty($a['submitted_file']);
      $isGraded = !empty($a['marks']);
    ?>
      <div class="assignment-card p-4 mb-4">
        <!-- Assignment Header -->
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="fw-bold mb-1"><?= e($a['title']) ?></h5>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="subject-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-book me-1" viewBox="0 0 16 16">
                  <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
                <?= e($a['subject_name']) ?>
              </span>
              <span class="due-date <?= $dueDateClass ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar-check me-1" viewBox="0 0 16 16">
                  <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                </svg>
                Due: <?= e($a['due_date']) ?>
                <?= $isOverdue ? '(Overdue!)' : '' ?>
              </span>
            </div>
          </div>
          
          <div class="submission-status <?= $hasSubmission ? 'status-submitted' : 'status-pending' ?>">
            <span class="status-icon">
              <?php if($hasSubmission): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 8 7 7 7 0 0 1-8 7zm3.707-9.293a1 1 0 0 0-1.414-1.414L7 8.586 5.707 7.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4z"/>
                </svg>
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                  <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                  <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 7 7z"/>
                </svg>
              <?php endif; ?>
            </span>
            <span><?= $hasSubmission ? 'Submitted' : 'Pending' ?></span>
          </div>
        </div>

        <!-- Assignment Description -->
        <div class="mb-3">
          <p class="mb-0"><?= nl2br(e($a['description'] ?? 'No description provided')) ?></p>
        </div>

        <!-- Submission Details -->
        <div class="border-top border-bottom py-3 mb-3 border-secondary">
          <div class="small fw-bold mb-2">My Submission</div>
          
          <?php if($a['submitted_file']): ?>
            <div class="row align-items-center">
              <div class="col-md-8">
                <div class="d-flex align-items-center">
                  <div class="file-type-icon">
                    <?php
                    $ext = pathinfo($a['submitted_file'], PATHINFO_EXTENSION);
                    $icon = '📄';
                    if(in_array($ext, ['pdf'])) $icon = '📕';
                    elseif(in_array($ext, ['doc', 'docx'])) $icon = '📘';
                    elseif(in_array($ext, ['png', 'jpg', 'jpeg'])) $icon = '🖼️';
                    elseif(in_array($ext, ['zip'])) $icon = '🗜️';
                    echo $icon;
                    ?>
                  </div>
                  <div>
                    <div class="small text-muted">Submitted at: <?= e($a['submitted_at']) ?></div>
                    <?php if($isGraded): ?>
                      <div class="marks-display text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill me-1" viewBox="0 0 16 16">
                          <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                        </svg>
                        Marks: <?= e($a['marks']) ?>
                      </div>
                      <?php if(!empty($a['feedback'])): ?>
                        <div class="small mt-1">
                          <span class="fw-semibold">Feedback:</span> <?= e($a['feedback']) ?>
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="small text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-hourglass-split me-1" viewBox="0 0 16 16">
                          <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1h-11zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2h-7zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48V8.35zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                        </svg>
                        Awaiting grading
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-end">
                <a class="btn btn-outline-dark" target="_blank" href="<?= e($a['submitted_file']) ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download me-1" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                  </svg>
                  View/Download
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="text-center py-3">
              <div class="text-muted mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-upload" viewBox="0 0 16 16">
                  <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                  <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                </svg>
              </div>
              <p class="small text-muted mb-0">No submission yet. Upload your work below.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Submission Form -->
        <form method="post" enctype="multipart/form-data" class="mt-3">
          <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
          
          <div class="mb-3">
            <div class="custom-file-input">
              <input type="file" name="file" id="file_<?= $a['id'] ?>" class="form-control" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.zip">
              <label for="file_<?= $a['id'] ?>" class="file-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cloud-arrow-up me-2" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2z"/>
                  <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                </svg>
                Choose File (PDF, DOC, Images, ZIP)
              </label>
            </div>
          </div>
          
          <button name="submit_assignment" class="btn <?= $hasSubmission ? 'btn-success' : 'btn-dark' ?> w-100">
            <?php if($hasSubmission): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat me-1" viewBox="0 0 16 16">
                <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
              </svg>
              Update Submission
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-upload me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383z"/>
                <path fill-rule="evenodd" d="M7.646 4.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 5.707V14.5a.5.5 0 0 1-1 0V5.707L5.354 7.854a.5.5 0 1 1-.708-.708l3-3z"/>
              </svg>
              Upload Submission
            <?php endif; ?>
          </button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<script>
// Add ripple effect to buttons
document.querySelectorAll('.btn').forEach(button => {
  button.addEventListener('click', function(e) {
    const rect = this.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const ripple = document.createElement('span');
    ripple.style.cssText = `
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.4);
      transform: scale(0);
      animation: ripple 0.6s linear;
      width: 100px;
      height: 100px;
      top: ${y - 50}px;
      left: ${x - 50}px;
    `;
    
    this.appendChild(ripple);
    
    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
});

// Add CSS for ripple animation
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
  
  .animated-badge {
    animation: badgePulse 1s infinite;
  }
`;
document.head.appendChild(style);

// File input preview
document.querySelectorAll('input[type="file"]').forEach(input => {
  input.addEventListener('change', function(e) {
    const label = this.nextElementSibling;
    if(this.files && this.files[0]) {
      const fileName = this.files[0].name;
      label.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-earmark-check me-2" viewBox="0 0 16 16">
          <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/>
          <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
        </svg>
        Selected: ${fileName}
      `;
      label.style.borderColor = '#415a77';
      label.style.background = 'rgba(65, 90, 119, 0.1)';
    }
  });
});

// Auto-refresh page every 60 seconds for new assignments
setTimeout(() => {
  window.location.reload();
}, 60000);
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>