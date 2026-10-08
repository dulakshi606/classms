<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");

$uid = (int) current_user()['id'];

/* ---------------------------
   SAFE TABLE CHECK FUNCTION
----------------------------*/
function table_exists(mysqli $conn, string $table): bool {
  $t = $conn->real_escape_string($table);
  $r = $conn->query("SHOW TABLES LIKE '{$t}'");
  return ($r && $r->num_rows > 0);
}

/* ---------------------------
   COUNTS + NOTIFICATIONS
----------------------------*/

// My subjects count (enrolled)
$enrolled = 0;
if (table_exists($conn, "enrollments")) {
  $en = $conn->prepare("SELECT COUNT(*) c FROM enrollments WHERE student_id=?");
  $en->bind_param("i", $uid);
  $en->execute();
  $enrolled = (int)($en->get_result()->fetch_assoc()['c'] ?? 0);
}

// Marks count
$marks = 0;
if (table_exists($conn, "term_marks")) {
  $mk = $conn->prepare("SELECT COUNT(*) c FROM term_marks WHERE student_id=?");
  $mk->bind_param("i", $uid);
  $mk->execute();
  $marks = (int)($mk->get_result()->fetch_assoc()['c'] ?? 0);
}

// Announcements count (all)
$announcements = 0;
if (table_exists($conn, "announcements")) {
  $anRow = $conn->query("SELECT COUNT(*) c FROM announcements");
  $announcements = (int)(($anRow && $anRow->fetch_assoc()['c']) ?? 0);
}

// ✅ Unread teacher messages
$unread_chat = 0;
if (table_exists($conn, "chat_messages")) {
  $cm = $conn->prepare("
    SELECT COUNT(*) c
    FROM chat_messages
    WHERE student_id=? AND sender_role='teacher' AND seen_by_student=0
  ");
  $cm->bind_param("i", $uid);
  $cm->execute();
  $unread_chat = (int)($cm->get_result()->fetch_assoc()['c'] ?? 0);
}

// ✅ Pending assignments (FIXED: no s.id)
$pending_assignments = 0;
if (table_exists($conn, "assignments") && table_exists($conn, "enrollments") && table_exists($conn, "submissions")) {
  $pa = $conn->prepare("
    SELECT COUNT(DISTINCT a.id) c
    FROM assignments a
    JOIN enrollments e ON e.subject_id = a.subject_id
    LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = e.student_id
    WHERE e.student_id = ?
      AND (s.assignment_id IS NULL OR s.file_path IS NULL OR s.file_path = '')
  ");
  $pa->bind_param("i", $uid);
  $pa->execute();
  $pending_assignments = (int)($pa->get_result()->fetch_assoc()['c'] ?? 0);
}

// ✅ Attendance this month (SAFE: supports att_date OR attendance_date)
$attendance_month = 0;
$monthStart = date("Y-m-01");
$monthEnd   = date("Y-m-t");

if (table_exists($conn, "attendance")) {
  $dateCol = null;

  $c1 = $conn->query("SHOW COLUMNS FROM attendance LIKE 'attendance_date'");
  if ($c1 && $c1->num_rows > 0) $dateCol = "attendance_date";

  if (!$dateCol) {
    $c2 = $conn->query("SHOW COLUMNS FROM attendance LIKE 'att_date'");
    if ($c2 && $c2->num_rows > 0) $dateCol = "att_date";
  }

  if ($dateCol) {
    $att = $conn->prepare("
      SELECT COUNT(*) c
      FROM attendance
      WHERE student_id=? AND {$dateCol} BETWEEN ? AND ?
    ");
    $att->bind_param("iss", $uid, $monthStart, $monthEnd);
    $att->execute();
    $attendance_month = (int)($att->get_result()->fetch_assoc()['c'] ?? 0);
  }
}

/* ---------------------------
   LATEST LISTS
----------------------------*/

// Latest 5 announcements
$latestAnn = null;
if (table_exists($conn, "announcements")) {
  $latestAnn = $conn->query("
    SELECT id, title, created_at
    FROM announcements
    ORDER BY created_at DESC
    LIMIT 5
  ");
}

// Latest 5 marks (FIXED: no tm.id)
$latestMarksRes = null;
if (table_exists($conn, "term_marks") && table_exists($conn, "subjects")) {

  $hasCreated = $conn->query("SHOW COLUMNS FROM term_marks LIKE 'created_at'");
  $orderBy = ($hasCreated && $hasCreated->num_rows > 0)
    ? "tm.created_at DESC"
    : "tm.term DESC"; // fallback if no created_at

  $latestMarks = $conn->prepare("
    SELECT tm.term, tm.marks, tm.grade, s.name AS subject_name
    FROM term_marks tm
    JOIN subjects s ON s.id = tm.subject_id
    WHERE tm.student_id = ?
    ORDER BY {$orderBy}
    LIMIT 5
  ");
  $latestMarks->bind_param("i", $uid);
  $latestMarks->execute();
  $latestMarksRes = $latestMarks->get_result();
}

require_once __DIR__ . "/../includes/header.php";
?>

<!-- ✅ YOUR EXISTING HTML + CSS CAN STAY SAME BELOW -->
<!-- NOTE: Only change required in HTML: remove any usage of $m['id'] if you had it -->

<!-- Animated Background -->
<div class="animated-bg">
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
</div>

<div class="container py-4">

  <!-- Welcome Card -->
  <div class="welcome-card mb-4">
    <div class="card-body p-4">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="h3 mb-2 fw-bold" style="color: #e0e1dd;">Student Dashboard</h1>
          <p class="text-muted mb-0">
            Welcome back, <span class="fw-bold text-white"><?= e(current_user()['full_name'] ?? 'Student') ?>!</span>
            <span class="d-block small mt-1">Here's your academic overview for today.</span>
          </p>
        </div>
        <div class="col-md-4 text-end">
          <span class="badge bg-primary px-3 py-2">
            Student ID: <?= $uid ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
      <div class="stat-card border-start border-danger border-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold fs-5">Teacher Messages</div>
              <div class="text-muted small">Unread chats</div>
            </div>
            <span class="badge <?= ($unread_chat>0)?'bg-danger':'bg-secondary' ?> fs-6"><?= $unread_chat ?></span>
          </div>
          <a href="/classms/student/chat.php" class="btn btn-dark btn-sm w-100 mt-3">Open Chat</a>
          <?php if(!table_exists($conn,"chat_messages")): ?>
            <div class="text-muted small mt-2">Chat table not created yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="stat-card border-start border-warning border-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold fs-5">Assignments</div>
              <div class="text-muted small">Pending uploads</div>
            </div>
            <span class="badge <?= ($pending_assignments>0)?'bg-warning text-dark':'bg-secondary' ?> fs-6"><?= $pending_assignments ?></span>
          </div>
          <a href="/classms/student/assignments.php" class="btn btn-outline-dark btn-sm w-100 mt-3">View Assignments</a>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="stat-card border-start border-success border-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold fs-5">Term Test Marks</div>
              <div class="text-muted small">Total records</div>
            </div>
            <span class="badge <?= ($marks>0)?'bg-success':'bg-secondary' ?> fs-6"><?= $marks ?></span>
          </div>
          <a href="/classms/student/marks.php" class="btn btn-outline-success btn-sm w-100 mt-3">View Marks</a>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="stat-card border-start border-info border-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold fs-5">Announcements</div>
              <div class="text-muted small">Total notices</div>
            </div>
            <span class="badge bg-info fs-6"><?= $announcements ?></span>
          </div>
          <a href="/classms/student/announcements.php" class="btn btn-outline-info btn-sm w-100 mt-3">View Notices</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Latest Updates -->
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="updates-card p-3">
        <h5 class="fw-bold">Latest Marks</h5>

        <?php if(!$latestMarksRes || $latestMarksRes->num_rows === 0): ?>
          <div class="alert alert-secondary">No marks recorded yet.</div>
        <?php else: ?>
          <div class="list-group mb-3">
            <?php while($m = $latestMarksRes->fetch_assoc()): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?= e($m['subject_name']) ?></div>
                    <div class="small text-muted"><?= e($m['term']) ?></div>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-success"><?= e($m['marks']) ?></span>
                    <div class="small text-muted mt-1">Grade: <?= e($m['grade']) ?></div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="updates-card p-3">
        <h5 class="fw-bold">Latest Announcements</h5>

        <?php if(!$latestAnn || $latestAnn->num_rows === 0): ?>
          <div class="alert alert-secondary">No announcements yet.</div>
        <?php else: ?>
          <div class="list-group">
            <?php while($a = $latestAnn->fetch_assoc()): ?>
              <a class="list-group-item list-group-item-action" href="/classms/student/announcements.php">
                <div class="d-flex justify-content-between">
                  <div class="fw-semibold"><?= e($a['title'] ?? 'Announcement') ?></div>
                  <small class="text-muted"><?= date('M d', strtotime($a['created_at'])) ?></small>
                </div>
              </a>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>


<style>
:root {
  --primary-blue: #0d1b2a;
  --secondary-blue: #1b263b;
  --accent-blue: #415a77;
  --light-blue: #778da9;
  --text-light: #e0e1dd;
  --gradient-primary: linear-gradient(135deg, #0d1b2a 0%, #1b263b 100%);
  --gradient-secondary: linear-gradient(135deg, #1b263b 0%, #415a77 100%);
  --gradient-warning: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
  --gradient-danger: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  --gradient-success: linear-gradient(135deg, #28a745 0%, #218838 100%);
  --gradient-info: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
  background: rgba(255, 255, 255, 0.03);
  border-radius: 50%;
  animation: float 25s infinite linear;
}

.bg-particle:nth-child(1) {
  width: 300px;
  height: 300px;
  top: 10%;
  left: 5%;
  animation-delay: 0s;
}

.bg-particle:nth-child(2) {
  width: 200px;
  height: 200px;
  top: 60%;
  right: 10%;
  animation-delay: -8s;
}

.bg-particle:nth-child(3) {
  width: 150px;
  height: 150px;
  bottom: 20%;
  left: 15%;
  animation-delay: -15s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  25% {
    transform: translateY(-30px) rotate(90deg);
  }
  50% {
    transform: translateY(0) rotate(180deg);
  }
  75% {
    transform: translateY(30px) rotate(270deg);
  }
}

/* Welcome Card */
.welcome-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
  animation: cardEntrance 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.welcome-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--gradient-secondary);
  animation: progressBar 3s ease-in-out infinite;
}

@keyframes progressBar {
  0%, 100% { width: 0%; }
  50% { width: 100%; }
}

@keyframes cardEntrance {
  from {
    opacity: 0;
    transform: translateY(40px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Stat Cards */
.stat-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  animation: statCardEntrance 0.6s ease-out;
  animation-fill-mode: both;
  position: relative;
  overflow: hidden;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

.stat-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  border-color: rgba(120, 141, 169, 0.3);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.03) 50%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.stat-card:hover::before {
  opacity: 1;
}

@keyframes statCardEntrance {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Border Colors */
.border-danger { border-left-color: #dc3545 !important; }
.border-warning { border-left-color: #ffc107 !important; }
.border-success { border-left-color: #28a745 !important; }
.border-info { border-left-color: #17a2b8 !important; }

/* Badge Animations */
.badge {
  animation: badgePulse 2s infinite;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 20px;
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.bg-danger { 
  background: var(--gradient-danger) !important;
  animation: dangerPulse 2s infinite;
}

@keyframes dangerPulse {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
  }
  50% { 
    box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
  }
}

.bg-warning { 
  background: var(--gradient-warning) !important;
  animation: warningPulse 2s infinite;
}

@keyframes warningPulse {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
  }
  50% { 
    box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
  }
}

.bg-success { 
  background: var(--gradient-success) !important;
}

.bg-info { 
  background: var(--gradient-info) !important;
}

.bg-primary { 
  background: var(--gradient-secondary) !important;
}

/* Quick Access Cards */
.quick-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  animation: quickCardEntrance 0.5s ease-out;
  animation-fill-mode: both;
}

.quick-card:nth-child(1) { animation-delay: 0.1s; }
.quick-card:nth-child(2) { animation-delay: 0.2s; }
.quick-card:nth-child(3) { animation-delay: 0.3s; }
.quick-card:nth-child(4) { animation-delay: 0.4s; }
.quick-card:nth-child(5) { animation-delay: 0.5s; }
.quick-card:nth-child(6) { animation-delay: 0.6s; }

.quick-card:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-5px) scale(1.03);
  border-color: var(--accent-blue);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

.quick-card .emoji {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  display: inline-block;
  animation: emojiFloat 3s ease-in-out infinite;
}

@keyframes emojiFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

@keyframes quickCardEntrance {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Button Styles */
.btn-dark, .btn-outline-dark, .btn-outline-success, .btn-outline-info {
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-dark {
  background: var(--gradient-secondary);
  border: none;
}

.btn-dark:hover {
  background: linear-gradient(135deg, #415a77 0%, #1b263b 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.btn-outline-dark, .btn-outline-success, .btn-outline-info {
  background: transparent;
  border-width: 2px;
}

.btn-outline-dark:hover, .btn-outline-success:hover, .btn-outline-info:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

/* Latest Updates Card */
.updates-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  animation: slideInRight 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* List Group Items */
.list-group-item {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: var(--text-light);
  margin-bottom: 8px;
  border-radius: 12px !important;
  transition: all 0.3s ease;
  animation: listItemEntrance 0.5s ease-out;
  animation-fill-mode: both;
}

.list-group-item:nth-child(1) { animation-delay: 0.1s; }
.list-group-item:nth-child(2) { animation-delay: 0.2s; }
.list-group-item:nth-child(3) { animation-delay: 0.3s; }
.list-group-item:nth-child(4) { animation-delay: 0.4s; }
.list-group-item:nth-child(5) { animation-delay: 0.5s; }

.list-group-item:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateX(5px);
  border-color: var(--accent-blue);
}

.list-group-item-action:hover {
  background: rgba(65, 90, 119, 0.2);
}

@keyframes listItemEntrance {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Text Styles */
.text-muted {
  color: rgba(224, 225, 221, 0.6) !important;
}

.fw-bold {
  color: #e0e1dd;
}

/* Responsive Design */
@media (max-width: 768px) {
  .stat-card, .quick-card {
    margin-bottom: 1rem;
  }
  
  .welcome-card, .updates-card {
    margin: 1rem;
    padding: 1rem !important;
  }
}

/* Loading Animation */
.loading-shimmer {
  background: linear-gradient(90deg, 
    rgba(255,255,255,0) 0%, 
    rgba(255,255,255,0.1) 50%, 
    rgba(255,255,255,0) 100%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
</style>

<!-- Animated Background -->
<div class="animated-bg">
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
</div>

<div class="container py-4">
  <!-- Welcome Card -->
 

  
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card updates-card mb-4">
        <div class="card-header" style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
          <h5 class="mb-0 fw-bold d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-lightning-charge me-2" viewBox="0 0 16 16">
              <path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09zM4.157 8.5H7a.5.5 0 0 1 .478.647L6.11 13.59l5.732-6.09H9a.5.5 0 0 1-.478-.647L9.89 2.41 4.157 8.5z"/>
            </svg>
            Quick Access
          </h5>
        </div>

        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <a href="/classms/student/profile.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">👤</div>
                  <h6 class="fw-bold mb-1">My Profile</h6>
                  <p class="text-muted small mb-0">Update details</p>
                </div>
              </a>
            </div>

            <div class="col-md-4">
              <a href="/classms/student/subjects.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">📚</div>
                  <h6 class="fw-bold mb-1">My Subjects</h6>
                  <p class="text-muted small mb-0"><?= $enrolled ?> enrolled</p>
                </div>
              </a>
            </div>

            <div class="col-md-4">
              <a href="/classms/student/attendance.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">📅</div>
                  <h6 class="fw-bold mb-1">Attendance</h6>
                  <p class="text-muted small mb-0">This month: <b><?= $attendance_month ?></b></p>
                </div>
              </a>
            </div>

            <div class="col-md-4">
              <a href="/classms/student/assignments.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">📝</div>
                  <h6 class="fw-bold mb-1">Assignments</h6>
                  <p class="text-muted small mb-0">Pending: <b class="<?= $pending_assignments>0?'text-warning':'text-muted' ?>"><?= $pending_assignments ?></b></p>
                </div>
              </a>
            </div>

            <div class="col-md-4">
              <a href="/classms/student/chat.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">💬</div>
                  <h6 class="fw-bold mb-1">Teacher Chat</h6>
                  <p class="text-muted small mb-0">
                    Unread: 
                    <span class="badge <?= ($unread_chat>0)?'bg-danger':'bg-secondary' ?>"><?= $unread_chat ?></span>
                  </p>
                </div>
              </a>
            </div>

            <div class="col-md-4">
              <a href="/classms/student/announcements.php" class="card quick-card text-decoration-none">
                <div class="card-body text-center p-4">
                  <div class="emoji">📢</div>
                  <h6 class="fw-bold mb-1">Announcements</h6>
                  <p class="text-muted small mb-0"><?= $announcements ?> notices</p>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="updates-card h-100">
        <div class="card-header" style="background: rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
          <h5 class="mb-0 fw-bold d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bell me-2" viewBox="0 0 16 16">
              <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
            </svg>
            Latest Updates
          </h5>
        </div>

        <div class="card-body">
          <div class="fw-bold small mb-2 d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up-arrow me-2" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z"/>
            </svg>
            Latest Marks
          </div>
          
          <?php if(!$latestMarksRes || $latestMarksRes->num_rows === 0): ?>
            <div class="alert alert-secondary animated-alert">
              No marks recorded yet.
            </div>
          <?php else: ?>
            <div class="list-group mb-3">
              <?php while($m = $latestMarksRes->fetch_assoc()): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-semibold"><?= e($m['subject_name']) ?></div>
                      <div class="small text-muted"><?= e($m['term']) ?></div>
                    </div>
                    <div class="text-end">
                      <span class="badge <?= ($m['grade'] == 'A' || $m['grade'] == 'A+') ? 'bg-success' : 'bg-secondary' ?>">
                        <?= e($m['marks']) ?>
                      </span>
                      <div class="small text-muted mt-1">Grade: <?= e($m['grade']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>

          <div class="fw-bold small mb-2 d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-megaphone me-2" viewBox="0 0 16 16">
              <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-2.083l.405 2.712A1 1 0 0 1 5.51 15.1h-.548a1 1 0 0 1-.916-.599l-1.85-3.49a68.14 68.14 0 0 0-.202-.003A2.014 2.014 0 0 1 0 9V7a2.02 2.02 0 0 1 1.992-2.013 74.663 74.663 0 0 0 2.483-.075c3.043-.154 6.148-.849 8.525-2.199V2.5zm1 0v11a.5.5 0 0 0 1 0v-11a.5.5 0 0 0-1 0zm-1 1.35c-2.344 1.205-5.209 1.842-8 2.033v4.233c.18.01.359.022.537.036 2.568.189 5.093.744 7.463 1.993V3.85zm-9 6.215v-4.13a95.09 95.09 0 0 1-1.992.052A1.02 1.02 0 0 0 1 7v2c0 .55.448 1.002 1.006 1.009A60.49 60.49 0 0 1 4 10.065zm-.657.975 1.609 3.037.01.024h.548l-.002-.014-.443-2.966a68.019 68.019 0 0 0-1.722-.082z"/>
            </svg>
            Latest Announcements
          </div>
          
          <?php if(!$latestAnn || $latestAnn->num_rows === 0): ?>
            <div class="alert alert-secondary animated-alert">
              No announcements yet.
            </div>
          <?php else: ?>
            <div class="list-group">
              <?php while($a = $latestAnn->fetch_assoc()): ?>
                <a class="list-group-item list-group-item-action" href="/classms/student/announcements.php">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold"><?= e($a['title'] ?? 'Announcement') ?></div>
                    <small class="text-muted"><?= date('M d', strtotime($a['created_at'])) ?></small>
                  </div>
                </a>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Add hover effects and animations
document.addEventListener('DOMContentLoaded', function() {
  // Animate elements on scroll
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  // Observe all stat cards and quick cards
  document.querySelectorAll('.stat-card, .quick-card, .list-group-item').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
  });

  // Add click ripple effect to buttons
  document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
      const rect = this.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      
      const ripple = document.createElement('span');
      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
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
  `;
  document.head.appendChild(style);

  // Auto-refresh unread count every 30 seconds
  setInterval(() => {
    fetch('/classms/student/api/unread-count.php')
      .then(response => response.json())
      .then(data => {
        if(data.unread_chat !== undefined) {
          const badge = document.querySelector('.badge.bg-danger, .badge.bg-secondary');
          if(badge) {
            const newCount = data.unread_chat;
            const oldCount = parseInt(badge.textContent);
            if(newCount !== oldCount) {
              badge.textContent = newCount;
              badge.className = newCount > 0 ? 'badge bg-danger' : 'badge bg-secondary';
              
              // Add notification animation
              if(newCount > oldCount) {
                badge.style.animation = 'none';
                setTimeout(() => {
                  badge.style.animation = 'badgePulse 2s infinite';
                }, 10);
              }
            }
          }
        }
      })
      .catch(() => {}); // Silent fail
  }, 30000);
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>