<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");

$uid = (int) current_user()['id'];

// ✅ FIX: get teacher_id from GET OR POST
$teacher_id = (int)($_GET['teacher_id'] ?? ($_POST['teacher_id'] ?? 0));
$msg = "";

// ✅ Safety: check table exists
$chkT = $conn->query("SHOW TABLES LIKE 'chat_messages'");
if(!$chkT || $chkT->num_rows === 0){
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <div class='alert alert-danger mb-0 animated-alert'>
            Chat table not found. Create <b>chat_messages</b> table first.
          </div>
          <a class='btn btn-outline-dark mt-3' href='/classms/student/dashboard.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

/* ------------------------------------------------
   ✅ TEACHER LIST = Enrolled teachers OR chatted teachers
------------------------------------------------- */
$teachers = $conn->prepare("
  SELECT DISTINCT u.id, u.full_name
  FROM (
    SELECT s.teacher_id AS tid
    FROM enrollments e
    JOIN subjects s ON s.id=e.subject_id
    WHERE e.student_id=?

    UNION

    SELECT cm.teacher_id AS tid
    FROM chat_messages cm
    WHERE cm.student_id=?
  ) t
  JOIN users u ON u.id=t.tid
  WHERE u.role='teacher'
  ORDER BY u.full_name ASC
");
$teachers->bind_param("ii", $uid, $uid);
$teachers->execute();
$teacherRes = $teachers->get_result();

// Auto-select first teacher
if ($teacher_id === 0 && $teacherRes->num_rows > 0) {
  $teacherRes->data_seek(0);
  $first = $teacherRes->fetch_assoc();
  $teacher_id = (int)$first['id'];
  
  // Re-execute to reset result pointer
  $teachers->execute();
  $teacherRes = $teachers->get_result();
}

// Validate selected teacher
$selectedTeacher = null;
if ($teacher_id > 0) {
  $tq = $conn->prepare("SELECT id, full_name FROM users WHERE id=? AND role='teacher' LIMIT 1");
  $tq->bind_param("i", $teacher_id);
  $tq->execute();
  $selectedTeacher = $tq->get_result()->fetch_assoc();
  if(!$selectedTeacher){
    $teacher_id = 0;
  }
}

/* ------------------------------------------------
   ✅ Send message (Student -> Teacher)
------------------------------------------------- */
if ($teacher_id > 0 && isset($_POST['send'])) {
  $text = trim($_POST['message'] ?? '');
  if ($text === '') {
    $msg = "<div class='alert alert-danger animated-alert'>Type a message.</div>";
  } else {
    $role = "student";
    $ins = $conn->prepare("
      INSERT INTO chat_messages(teacher_id, student_id, sender_role, message, seen_by_student, seen_by_teacher)
      VALUES (?,?,?,?,1,0)
    ");
    $ins->bind_param("iiss", $teacher_id, $uid, $role, $text);
    $ins->execute();

    // ✅ redirect keep teacher_id
    header("Location: /classms/student/chat.php?teacher_id=".$teacher_id);
    exit();
  }
}

/* ------------------------------------------------
   ✅ Mark teacher messages as seen by student
------------------------------------------------- */
if ($teacher_id > 0) {
  $seen = $conn->prepare("
    UPDATE chat_messages
    SET seen_by_student=1
    WHERE teacher_id=? AND student_id=? AND sender_role='teacher' AND seen_by_student=0
  ");
  $seen->bind_param("ii", $teacher_id, $uid);
  $seen->execute();
}

/* ------------------------------------------------
   ✅ Load messages
------------------------------------------------- */
$messagesRes = null;
if ($teacher_id > 0) {
  $list = $conn->prepare("
    SELECT id, sender_role, message, created_at, seen_by_teacher
    FROM chat_messages
    WHERE teacher_id=? AND student_id=?
    ORDER BY created_at ASC, id ASC
  ");
  $list->bind_param("ii", $teacher_id, $uid);
  $list->execute();
  $messagesRes = $list->get_result();
}

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
  --gradient-mine: linear-gradient(135deg, #415a77 0%, #1b263b 100%);
  --gradient-theirs: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
  --gradient-reply: linear-gradient(135deg, #2d3748 0%, #374151 100%);
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

/* Teacher List */
.list-group-item {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-light);
  margin-bottom: 8px;
  border-radius: 12px !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

.list-group-item.active {
  background: var(--gradient-secondary) !important;
  border-color: var(--accent-blue) !important;
  transform: translateX(5px) scale(1.02);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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

/* Badge Animations */
.badge {
  animation: badgePulse 2s infinite;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 20px;
}

.text-bg-danger {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
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

.text-bg-success {
  background: linear-gradient(135deg, #28a745 0%, #218838 100%) !important;
}

.text-bg-secondary {
  background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

/* Chat Container */
.chat-box {
  height: 520px;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 20px;
  scroll-behavior: smooth;
}

/* Custom scrollbar */
.chat-box::-webkit-scrollbar {
  width: 8px;
}

.chat-box::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}

.chat-box::-webkit-scrollbar-thumb {
  background: var(--accent-blue);
  border-radius: 10px;
}

.chat-box::-webkit-scrollbar-thumb:hover {
  background: var(--light-blue);
}

/* Message Bubbles */
.message-container {
  display: flex;
  margin-bottom: 20px;
  animation: messageEntrance 0.4s ease-out;
  animation-fill-mode: both;
}

.message-container.mine {
  justify-content: flex-end;
  animation-delay: 0.1s;
}

.message-container.theirs {
  justify-content: flex-start;
  animation-delay: 0.2s;
}

@keyframes messageEntrance {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-bubble {
  max-width: 70%;
  border-radius: 18px;
  position: relative;
  overflow: hidden;
  animation: bubbleFloat 3s ease-in-out infinite;
}

.message-container.mine .message-bubble {
  background: var(--gradient-mine);
  border-bottom-right-radius: 4px;
  box-shadow: 0 5px 15px rgba(65, 90, 119, 0.3);
}

.message-container.theirs .message-bubble {
  background: var(--gradient-theirs);
  border-bottom-left-radius: 4px;
  box-shadow: 0 5px 15px rgba(45, 55, 72, 0.3);
}

@keyframes bubbleFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}

.message-content {
  padding: 14px 18px;
  color: white;
}

.message-text {
  font-size: 0.95rem;
  line-height: 1.5;
  word-break: break-word;
  white-space: pre-wrap;
}

.message-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
  font-size: 0.75rem;
  opacity: 0.85;
}

/* Reply Preview */
.reply-preview {
  background: var(--gradient-reply);
  border-left: 4px solid var(--accent-blue);
  border-radius: 8px;
  padding: 10px 14px;
  margin-bottom: 12px;
  font-size: 0.85rem;
  animation: replySlideIn 0.3s ease-out;
}

@keyframes replySlideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Form Styles */
.form-control {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: var(--text-light);
  padding: 12px 16px;
  transition: all 0.3s ease;
  min-height: 60px;
  resize: none;
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

/* Reply Info */
.reply-info {
  background: var(--gradient-reply);
  border-left: 4px solid var(--accent-blue);
  border-radius: 8px;
  padding: 10px 14px;
  margin-top: 8px;
  font-size: 0.85rem;
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Button Styles */
.btn-dark {
  background: var(--gradient-secondary);
  border: none;
  border-radius: 12px;
  padding: 14px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  min-width: 100px;
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

.btn-outline-light {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: var(--text-light);
  border-radius: 20px;
  padding: 4px 12px;
  font-size: 0.75rem;
  transition: all 0.3s ease;
}

.btn-outline-light:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--accent-blue);
  transform: translateY(-1px);
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
h5.fw-bold {
  background: linear-gradient(45deg, #e0e1dd, #778da9);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
  display: inline-block;
}

.text-muted {
  color: rgba(224, 225, 221, 0.6) !important;
}

/* Status Indicator */
.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  display: inline-block;
}

.status-sent { background: #6c757d; }
.status-delivered { background: #17a2b8; animation: pulse 1s infinite; }
.status-seen { background: #28a745; animation: pulse 0.5s infinite; }

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Empty State */
.empty-chat {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
  animation: gentlePulse 3s infinite;
}

@keyframes gentlePulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 0.8; }
}

/* Teacher Avatar */
.teacher-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--gradient-secondary);
  color: white;
  font-weight: bold;
  margin-right: 12px;
  font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .chat-box {
    height: 400px;
  }
  
  .message-bubble {
    max-width: 85%;
  }
  
  .card-soft {
    margin: 10px;
    padding: 15px !important;
  }
  
  .btn-dark {
    padding: 12px 16px;
    min-width: 80px;
  }
}
</style>

<!-- Animated Background -->
<div class="animated-bg">
  <div class="bg-particle"></div>
  <div class="bg-particle"></div>
</div>

<div class="container py-4">
  <div class="row g-4">

    <!-- Left: Teacher List -->
    <div class="col-lg-4">
      <div class="card card-soft p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="fw-bold mb-0 d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people me-2" viewBox="0 0 16 16">
              <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
            </svg>
            Teachers
          </h5>
          <a class="btn btn-outline-dark btn-sm" href="/classms/student/dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-1" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Back
          </a>
        </div>

        <?php if($teacherRes->num_rows === 0): ?>
          <div class="alert alert-info animated-alert">
            <div class="d-flex align-items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle me-2" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 8 7 7 7 0 0 1-8 7zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
              </svg>
              <div>
                <strong>No teachers found.</strong>
                <div class="small mt-1">Enroll subjects or teacher must message you first.</div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="list-group">
            <?php 
            $teacherRes->data_seek(0);
            while($t = $teacherRes->fetch_assoc()):
              $active = ((int)$teacher_id === (int)$t['id']);

              $uc = $conn->prepare("
                SELECT COUNT(*) c
                FROM chat_messages
                WHERE student_id=? AND teacher_id=? AND sender_role='teacher' AND seen_by_student=0
              ");
              $uc->bind_param("ii", $uid, $t['id']);
              $uc->execute();
              $unread = (int)($uc->get_result()->fetch_assoc()['c'] ?? 0);
            ?>
              <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $active?'active':'' ?>"
                 href="/classms/student/chat.php?teacher_id=<?= (int)$t['id'] ?>">
                <div class="d-flex align-items-center">
                  <div class="teacher-avatar">
                    <?= htmlspecialchars(substr($t['full_name'], 0, 1)) ?>
                  </div>
                  <span class="fw-medium"><?= htmlspecialchars($t['full_name']) ?></span>
                </div>
                <?php if($unread > 0): ?>
                  <span class="badge text-bg-danger animated-badge"><?= $unread ?></span>
                <?php endif; ?>
              </a>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: Chat Area -->
    <div class="col-lg-8">
      <div class="card card-soft p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1 d-flex align-items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-chat-left-text me-2" viewBox="0 0 16 16">
                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
              </svg>
              Direct Chat
            </h5>
            <div class="text-muted small d-flex align-items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill me-1" viewBox="0 0 16 16">
                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
              </svg>
              <?php if($selectedTeacher): ?>
                Teacher: <b class="ms-1"><?= htmlspecialchars($selectedTeacher['full_name']) ?></b>
              <?php else: ?>
                Select a teacher to start chatting
              <?php endif; ?>
            </div>
          </div>

          <?php if($selectedTeacher): ?>
            <a class="btn btn-outline-dark btn-sm" href="/classms/student/chat.php?teacher_id=<?= (int)$teacher_id ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
              </svg>
              Refresh
            </a>
          <?php endif; ?>
        </div>

        <?= $msg ?>

        <div class="chat-box mb-4" id="chatBox">
          <?php if(!$messagesRes): ?>
            <div class="empty-chat">
              <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
                  <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                  <path d="M2.165 15.803l.02-.004c1.83-.363 2.948-.842 3.468-1.105A9.06 9.06 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.437 10.437 0 0 1-.524 2.318l-.003.011a10.722 10.722 0 0 1-.244.637c-.079.186.074.394.273.362a21.673 21.673 0 0 0 .693-.125zm.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6c0 3.193-3.004 6-7 6a8.06 8.06 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a10.97 10.97 0 0 0 .398-2z"/>
                </svg>
              </div>
              <h6 class="text-muted">Select a teacher to view messages</h6>
              <p class="small text-muted mt-2">Choose a teacher from the list to start chatting</p>
            </div>
          <?php elseif($messagesRes->num_rows === 0): ?>
            <div class="empty-chat">
              <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-chat-text" viewBox="0 0 16 16">
                  <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10.97 10.97 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.520.263-1.639.742-3.468 1.105z"/>
                  <path d="M4 5.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zM4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8zm0 2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5z"/>
                </svg>
              </div>
              <h6 class="text-muted">No messages yet</h6>
              <p class="small text-muted mt-2">Say Hi 👋 to start the conversation</p>
            </div>
          <?php else: ?>
            <?php while($m = $messagesRes->fetch_assoc()):
              $mine = ($m['sender_role'] === 'student');
              $time = date('Y-m-d g:i A', strtotime($m['created_at']));
              $safeMsg = htmlspecialchars($m['message']);
              
              // Determine status
              $statusClass = '';
              if($mine) {
                $statusClass = ((int)$m['seen_by_teacher']===1) ? 'text-bg-success' : 'text-bg-secondary';
              }
            ?>
              <div class="message-container <?= $mine ? 'mine' : 'theirs' ?>">
                <div class="message-bubble">
                  <div class="message-content">
                    <div class="message-text"><?= nl2br($safeMsg) ?></div>
                    
                    <div class="message-meta">
                      <span><?= htmlspecialchars($time) ?></span>
                      
                      <div class="d-flex gap-2 align-items-center">
                        <!-- Reply Button -->
                        <button type="button"
                          class="btn btn-outline-light btn-sm"
                          onclick="replyTo(<?= (int)$m['id'] ?>, '<?= $mine ? 'Me' : htmlspecialchars($selectedTeacher['full_name'] ?? 'Teacher') ?>', '<?= htmlspecialchars($time) ?>', <?= json_encode($m['message']) ?>)">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-reply me-1" viewBox="0 0 16 16">
                            <path d="M6.598 5.013a.144.144 0 0 1 .202.134V6.3a.5.5 0 0 0 .5.5c.667 0 2.013.005 3.3.822.984.624 1.99 1.76 2.595 3.876-1.02-.983-2.185-1.516-3.205-1.799a8.74 8.74 0 0 0-1.921-.306 7.404 7.404 0 0 0-.798.008h-.013l-.005.001h-.001L7.3 9.9l-.05-.498a.5.5 0 0 0-.45.498v1.153c0 .108-.11.176-.202.134L2.614 8.254a.503.503 0 0 0-.042-.028.147.147 0 0 1 0-.252.499.499 0 0 0 .042-.028l3.984-2.933zM7.8 10.386c.068 0 .143.003.223.006.434.02 1.034.086 1.7.271 1.326.368 2.896 1.202 3.94 3.08a.5.5 0 0 0 .933-.305c-.464-3.71-1.886-5.662-3.46-6.66-1.245-.79-2.527-.942-3.336-.971v-.66a1.144 1.144 0 0 0-1.767-.96l-3.994 2.94a1.147 1.147 0 0 0 0 1.946l3.994 2.94a1.144 1.144 0 0 0 1.767-.96v-.667z"/>
                          </svg>
                          Reply
                        </button>
                        
                        <?php if($mine): ?>
                          <span class="badge <?= $statusClass ?>">
                            <?= ((int)$m['seen_by_teacher']===1) ? 'Seen' : 'Sent' ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>

        <?php if($selectedTeacher): ?>
          <form method="post" class="d-flex gap-3 align-items-end" id="messageForm"
                action="/classms/student/chat.php?teacher_id=<?= (int)$teacher_id ?>">
            <!-- ✅ IMPORTANT: hidden teacher_id so POST never loses it -->
            <input type="hidden" name="teacher_id" value="<?= (int)$teacher_id ?>">

            <div class="flex-grow-1">
              <textarea class="form-control" id="messageInput" name="message" rows="2"
                        placeholder="Type your message..." required></textarea>
              <div class="reply-info" id="replyInfo" style="display:none;"></div>
            </div>

            <button class="btn btn-dark px-4" name="send" id="sendButton">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
              </svg>
              <span class="ms-1 d-none d-md-inline">Send</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
// Always scroll bottom
const chatBox = document.getElementById('chatBox');
if(chatBox) {
  chatBox.scrollTop = chatBox.scrollHeight;
  chatBox.style.scrollBehavior = 'smooth';
}

// ✅ Reply helper (quote message into textarea)
function replyTo(id, who, when, message){
  const input = document.getElementById('messageInput');
  const info  = document.getElementById('replyInfo');
  if(!input) return;

  const clean = (message || '').toString().replace(/\r/g,'').trim();
  const firstLine = clean.split("\n")[0].slice(0, 120);

  info.style.display = 'block';
  info.innerHTML = `
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong>Replying to ${escapeHtml(who)}</strong>
        <div class="small">${escapeHtml(when)}: ${escapeHtml(firstLine)}${clean.length > 120 ? '...' : ''}</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-light" onclick="cancelReply()">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
          <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
        </svg>
      </button>
    </div>
  `;

  // put quote at top (user can edit)
  input.value = `> ${escapeHtml(who)} (${escapeHtml(when)}): ${clean}\n\n` + input.value;
  input.focus();
  input.style.height = 'auto';
  input.style.height = (input.scrollHeight) + 'px';
}

function cancelReply() {
  const info = document.getElementById('replyInfo');
  if(info) {
    info.style.display = 'none';
  }
}

function escapeHtml(str){
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Form submission animation
const messageForm = document.getElementById('messageForm');
if(messageForm) {
  messageForm.addEventListener('submit', function(e) {
    const button = document.getElementById('sendButton');
    if (button) {
      const originalContent = button.innerHTML;
      
      // Show sending animation
      button.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span class="ms-1 d-none d-md-inline">Sending...</span>
      `;
      button.disabled = true;
      
      // Re-enable after 3 seconds (in case of error)
      setTimeout(() => {
        button.innerHTML = originalContent;
        button.disabled = false;
      }, 3000);
    }
  });
}

// Auto-resize textarea
const messageInput = document.getElementById('messageInput');
if(messageInput) {
  messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
  });
  
  // Focus on input
  messageInput.focus();
}

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
      pointer-events: none;
    `;
    
    button.style.position = 'relative';
    button.appendChild(ripple);
    
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

// ✅ Safer refresh (only chat area) every 5 seconds without breaking typing
let refreshTimer;
function startRefreshTimer() {
  if (refreshTimer) clearInterval(refreshTimer);
  
  refreshTimer = setInterval(() => {
    const tid = <?= (int)$teacher_id ?>;
    if(!tid) return;

    fetch("/classms/student/chat.php?teacher_id="+tid)
      .then(r => r.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newBox = doc.querySelector('#chatBox');
        const curBox = document.querySelector('#chatBox');

        // only update if changed
        if(newBox && curBox && newBox.innerHTML !== curBox.innerHTML){
          curBox.innerHTML = newBox.innerHTML;
          curBox.scrollTop = curBox.scrollHeight;
        }
      })
      .catch(()=>{});
  }, 5000);
}

// Start the refresh timer when page loads
document.addEventListener('DOMContentLoaded', function() {
  startRefreshTimer();
});
</script>

<?php
// Helper function for escaping
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . "/../includes/footer.php"; 
?>