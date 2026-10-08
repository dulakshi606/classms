<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$tid = (int) current_user()['id'];
$student_id = (int)($_GET['student_id'] ?? 0);
$msg = "";

// Safety: check table exists
$chkT = $conn->query("SHOW TABLES LIKE 'chat_messages'");
if(!$chkT || $chkT->num_rows === 0){
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <div class='alert alert-danger mb-0'>
            Chat table not found. Create <b>chat_messages</b> table in database first.
          </div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

// Load student
$stu = null;
if ($student_id > 0) {
  $st = $conn->prepare("
    SELECT u.id, u.full_name, u.email, sp.grade
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    WHERE u.id=? AND u.role='student'
    LIMIT 1
  ");
  $st->bind_param("i", $student_id);
  $st->execute();
  $stu = $st->get_result()->fetch_assoc();
}

if (!$stu) {
  require_once __DIR__ . "/../includes/header.php";
  echo "<div class='card card-soft p-4'>
          <div class='alert alert-danger mb-0'>Student not found. Open chat from Students page.</div>
          <a class='btn btn-outline-dark mt-3' href='/classms/teacher/students.php'>Back</a>
        </div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit();
}

// Send message
if (isset($_POST['send'])) {
  $text = trim($_POST['message'] ?? '');
  if ($text === '') {
    $msg = "<div class='alert-danger'>Type a message.</div>";
  } else {
    $role = "teacher";
    $ins = $conn->prepare("
      INSERT INTO chat_messages(teacher_id, student_id, sender_role, message, seen_by_student, seen_by_teacher)
      VALUES (?,?,?,?,0,1)
    ");
    $ins->bind_param("iiss", $tid, $student_id, $role, $text);
    $ins->execute();
    redirect("/classms/teacher/chat.php?student_id=".$student_id);
  }
}

// Mark student messages as seen by teacher
$seen = $conn->prepare("
  UPDATE chat_messages
  SET seen_by_teacher=1
  WHERE teacher_id=? AND student_id=? AND sender_role='student' AND seen_by_teacher=0
");
$seen->bind_param("ii", $tid, $student_id);
$seen->execute();

// Load messages
$list = $conn->prepare("
  SELECT id, sender_role, message, created_at, seen_by_student, seen_by_teacher
  FROM chat_messages
  WHERE teacher_id=? AND student_id=?
  ORDER BY created_at ASC, id ASC
");
$list->bind_param("ii", $tid, $student_id);
$list->execute();
$res = $list->get_result();

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
    max-width: 1200px;
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
    animation: alert-slide 0.5s ease-out;
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

/* Chat Container */
.chat-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: container-appear 0.6s ease-out;
}

@keyframes container-appear {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Chat Box */
.chat-box {
    background: var(--input-bg);
    border: 1px solid rgba(100, 255, 218, 0.1);
    border-radius: 15px;
    padding: 20px;
    height: 500px;
    overflow-y: auto;
    margin-bottom: 20px;
    position: relative;
}

.chat-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent-blue), transparent);
}

/* Chat Messages */
.chat-row {
    display: flex;
    margin-bottom: 15px;
    animation: message-appear 0.4s ease-out;
}

@keyframes message-appear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-row.mine {
    justify-content: flex-end;
}

.chat-row.theirs {
    justify-content: flex-start;
}

.chat-bubble {
    max-width: 70%;
    padding: 15px 20px;
    border-radius: 18px;
    position: relative;
    animation: bubble-appear 0.3s ease-out;
}

@keyframes bubble-appear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.chat-row.mine .chat-bubble {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border-bottom-right-radius: 5px;
}

.chat-row.theirs .chat-bubble {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--text-light);
    border: 1px solid rgba(100, 255, 218, 0.2);
    border-bottom-left-radius: 5px;
}

.chat-text {
    word-wrap: break-word;
    line-height: 1.5;
}

.chat-meta {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Message Status Badges */
.status-badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-sent {
    background: rgba(255, 255, 255, 0.2);
    color: var(--text-light);
}

.status-seen {
    background: rgba(100, 255, 218, 0.3);
    color: var(--accent-blue);
}

/* Message Input */
.message-input-container {
    background: var(--input-bg);
    border: 1px solid rgba(100, 255, 218, 0.1);
    border-radius: 15px;
    padding: 15px;
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.message-input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--text-white);
    resize: none;
    font-size: 1rem;
    line-height: 1.5;
    min-height: 80px;
    max-height: 150px;
    padding: 10px;
}

.message-input::placeholder {
    color: var(--text-muted);
}

.message-input:focus {
    outline: none;
}

/* Form Controls */
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
    
    .chat-container {
        padding: 20px;
    }
    
    .chat-box {
        height: 400px;
    }
    
    .chat-bubble {
        max-width: 85%;
    }
    
    .message-input-container {
        flex-direction: column;
    }
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--dark-blue);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--accent-blue);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #52d3b8;
}

/* Typing indicator */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 10px 15px;
    background: var(--light-blue);
    border-radius: 15px;
    width: fit-content;
    margin-bottom: 10px;
}

.typing-dot {
    width: 8px;
    height: 8px;
    background: var(--accent-blue);
    border-radius: 50%;
    animation: typing-dot 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing-dot {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
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

.form-control, textarea {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
}

.form-control:focus, textarea:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    color: var(--text-white) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
}

.btn-dark {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8) !important;
    color: var(--dark-blue) !important;
    border: none !important;
    font-weight: 600 !important;
    padding: 12px 25px !important;
    border-radius: 10px !important;
    transition: all 0.3s ease !important;
}

.btn-dark:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(100, 255, 218, 0.3) !important;
}

.btn-outline-dark {
    background: transparent !important;
    color: var(--accent-blue) !important;
    border: 2px solid var(--accent-blue) !important;
    font-weight: 600 !important;
    padding: 10px 20px !important;
    border-radius: 10px !important;
    transition: all 0.3s ease !important;
}

.btn-outline-dark:hover {
    background: rgba(100, 255, 218, 0.1) !important;
    color: var(--text-white) !important;
    transform: translateX(-5px);
    box-shadow: 0 5px 20px rgba(100, 255, 218, 0.3) !important;
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
                    <i class="fas fa-comments"></i> Direct Chat
                </h1>
                <div class="page-subtitle">
                    Private conversation with student
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
                    <?= strtoupper(substr($stu['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size: 1.3rem; font-weight: 600; color: var(--text-white);">
                        <?= e($stu['full_name']) ?>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 8px;">
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-id-card"></i> ID: <?= (int)$student_id ?>
                        </span>
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-graduation-cap"></i> Grade: <?= e($stu['grade'] ?? '-') ?>
                        </span>
                        <span style="color: var(--text-muted);">
                            <i class="fas fa-envelope"></i> <?= e($stu['email']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= $msg ?>

    <div class="chat-container">
        <!-- Chat Messages -->
        <div class="chat-box" id="chatBox">
            <?php if($res->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-comment-slash"></i>
                    </div>
                    <h4 style="color: var(--text-white); margin-bottom: 10px;">No Messages Yet</h4>
                    <p style="color: var(--text-muted);">Start the conversation by sending your first message.</p>
                </div>
            <?php else: ?>
                <?php while($m = $res->fetch_assoc()): 
                    $mine = ($m['sender_role'] === 'teacher');
                    $statusClass = ((int)$m['seen_by_student']===1) ? 'status-seen' : 'status-sent';
                    $statusText = ((int)$m['seen_by_student']===1) ? 'Seen' : 'Sent';
                ?>
                    <div class="chat-row <?= $mine ? 'mine' : 'theirs' ?>">
                        <div class="chat-bubble">
                            <div class="chat-text"><?= nl2br(e($m['message'])) ?></div>
                            <div class="chat-meta">
                                <i class="far fa-clock"></i>
                                <?= e(date('M d, Y - h:i A', strtotime($m['created_at']))) ?>
                                <?php if($mine): ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <i class="fas <?= $statusText === 'Seen' ? 'fa-check-double' : 'fa-check' ?>"></i>
                                        <?= $statusText ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- Message Input Form -->
        <form method="post">
            <div class="message-input-container">
                <textarea class="message-input" 
                          name="message" 
                          rows="3" 
                          placeholder="Type your message here..."
                          required
                          oninput="autoResize(this)"></textarea>
                <button class="btn-primary-custom" name="send">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = (textarea.scrollHeight) + 'px';
}

// Scroll to bottom of chat
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
    
    // Create floating particles
    const particlesContainer = document.getElementById('particles-container');
    const colors = ['#64ffda', '#ff6b9d', '#ffd166', '#a882ff'];
    
    for (let i = 0; i < 15; i++) {
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

// Auto-refresh chat every 10 seconds
setInterval(function() {
    const currentScroll = chatBox.scrollTop;
    const isAtBottom = (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) < 50;
    
    // Store current scroll position
    if (isAtBottom) {
        // If user is at bottom, reload to see new messages
        window.location.reload();
    }
}, 10000); // 10 seconds
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>