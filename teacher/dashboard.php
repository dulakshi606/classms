<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

// ✅ Extra safety: if db didn't load, show clear message
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection not found. Check: classms/config/db.php and includes/auth.php");
}

$tid = (int) current_user()['id'];

// Subjects
$q1 = $conn->prepare("SELECT COUNT(*) c FROM subjects WHERE teacher_id=?");
$q1->bind_param("i", $tid);
$q1->execute();
$subjects = $q1->get_result()->fetch_assoc()['c'] ?? 0;

// Assignments
$q2 = $conn->prepare("SELECT COUNT(*) c FROM assignments WHERE teacher_id=?");
$q2->bind_param("i", $tid);
$q2->execute();
$assignments = $q2->get_result()->fetch_assoc()['c'] ?? 0;

// Announcements
$q3 = $conn->prepare("SELECT COUNT(*) c FROM announcements WHERE teacher_id=?");
$q3->bind_param("i", $tid);
$q3->execute();
$announcements = $q3->get_result()->fetch_assoc()['c'] ?? 0;

// Submissions
$q4 = $conn->prepare("
  SELECT COUNT(*) c
  FROM submissions s
  JOIN assignments a ON a.id = s.assignment_id
  WHERE a.teacher_id=?
");
$q4->bind_param("i", $tid);
$q4->execute();
$submissions = $q4->get_result()->fetch_assoc()['c'] ?? 0;

// Get total students for stats
$studentCount = 0;
$studentsQuery = $conn->prepare("
  SELECT COUNT(DISTINCT e.student_id) as total 
  FROM enrollments e 
  JOIN subjects s ON s.id = e.subject_id 
  WHERE s.teacher_id = ?
");
$studentsQuery->bind_param("i", $tid);
$studentsQuery->execute();
$studentResult = $studentsQuery->get_result()->fetch_assoc();
$studentCount = $studentResult['total'] ?? 0;

require_once __DIR__ . "/../includes/header.php";
?>

<style>
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

.page-title {
  color: var(--text-white);
  font-size: 2.2rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.page-subtitle {
  color: var(--text-muted);
  font-size: 1.1rem;
}

/* Stats Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
  border-radius: 15px;
  padding: 25px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: var(--card-shadow);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
  box-shadow: 0 20px 40px rgba(2, 12, 27, 0.8);
  border-color: var(--accent-blue);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  font-size: 1.8rem;
}

.stat-value {
  color: var(--text-white);
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 5px;
}

.stat-label {
  color: var(--text-muted);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Quick Actions */
.actions-container {
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

.section-title {
  color: var(--text-white);
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 2px solid var(--accent-blue);
  display: inline-block;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.action-card {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 15px;
  padding: 25px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  animation: action-card-appear 0.5s ease-out forwards;
  opacity: 0;
}

@keyframes action-card-appear {
  to { opacity: 1; }
}

.action-card:hover {
  transform: translateY(-5px) scale(1.05);
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--accent-blue);
  box-shadow: 0 15px 30px rgba(2, 12, 27, 0.5);
}

.action-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 15px;
  font-size: 2rem;
  animation: icon-float 3s ease-in-out infinite;
}

@keyframes icon-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.action-title {
  color: var(--text-white);
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 8px;
}

.action-desc {
  color: var(--text-muted);
  font-size: 0.85rem;
  line-height: 1.4;
}

/* Stats Sidebar */
.stats-sidebar {
  background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
  border-radius: 20px;
  padding: 30px;
  border: 1px solid rgba(100, 255, 218, 0.1);
  box-shadow: var(--card-shadow);
  animation: sidebar-appear 0.8s ease-out forwards 0.4s;
  opacity: 0;
}

@keyframes sidebar-appear {
  to { opacity: 1; }
}

.stats-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  animation: stat-item-appear 0.5s ease-out forwards;
  opacity: 0;
}

@keyframes stat-item-appear {
  to { opacity: 1; }
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-item-label {
  color: var(--text-white);
  font-weight: 500;
}

.stat-badge {
  background: rgba(100, 255, 218, 0.1);
  color: var(--accent-blue);
  padding: 6px 15px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.9rem;
  animation: badge-pulse 2s infinite;
}

@keyframes badge-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

/* View Students Button */
.btn-view-students {
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
  width: 100%;
  margin-top: 20px;
}

.btn-view-students::before {
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

.btn-view-students:hover::before {
  left: 100%;
}

.btn-view-students:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 10px 25px rgba(100, 255, 218, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .container-custom {
    padding: 20px 15px;
  }
  
  .stats-container {
    grid-template-columns: 1fr;
  }
  
  .action-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
  
  .page-header {
    padding: 20px;
  }
  
  .page-title {
    font-size: 1.8rem;
  }
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
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

<div class="container-custom">
  <!-- Page Header -->
  <div class="page-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start">
      <div>
        <h1 class="page-title">
          <i class="fas fa-chalkboard-teacher me-3"></i>Teacher Dashboard
        </h1>
        <div class="page-subtitle">
          Welcome back, <?= htmlspecialchars(current_user()['full_name'] ?? 'Teacher') ?>!
        </div>
      </div>
      <div style="color: var(--accent-blue); font-weight: 600; font-size: 1.1rem;">
        <i class="fas fa-id-card me-2"></i> Teacher ID: <?= $tid ?>
      </div>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="stats-container">
    <div class="stat-card" style="animation-delay: 0.1s;">
      <div class="stat-icon" style="background: rgba(100, 255, 218, 0.1); color: var(--accent-blue);">
        <i class="fas fa-book"></i>
      </div>
      <div class="stat-value"><?= (int)$subjects ?></div>
      <div class="stat-label">Subjects Assigned</div>
    </div>
    
    <div class="stat-card" style="animation-delay: 0.2s;">
      <div class="stat-icon" style="background: rgba(28, 200, 138, 0.1); color: #1cc88a;">
        <i class="fas fa-tasks"></i>
      </div>
      <div class="stat-value"><?= (int)$assignments ?></div>
      <div class="stat-label">Assignments Created</div>
    </div>
    
    <div class="stat-card" style="animation-delay: 0.3s;">
      <div class="stat-icon" style="background: rgba(246, 194, 62, 0.1); color: #f6c23e;">
        <i class="fas fa-file-upload"></i>
      </div>
      <div class="stat-value"><?= (int)$submissions ?></div>
      <div class="stat-label">Student Submissions</div>
    </div>
    
    <div class="stat-card" style="animation-delay: 0.4s;">
      <div class="stat-icon" style="background: rgba(54, 185, 204, 0.1); color: #36b9cc;">
        <i class="fas fa-bullhorn"></i>
      </div>
      <div class="stat-value"><?= (int)$announcements ?></div>
      <div class="stat-label">Announcements Posted</div>
    </div>
  </div>

  <div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-8 mb-4 mb-lg-0">
      <div class="actions-container">
        <h2 class="section-title">
          <i class="fas fa-bolt me-2"></i> Quick Actions
        </h2>
        <div class="action-grid">
          <a href="/classms/teacher/subjects.php" class="action-card" style="animation-delay: 0.1s;">
            <div class="action-icon" style="background: rgba(100, 255, 218, 0.1); color: var(--accent-blue);">
              <i class="fas fa-book"></i>
            </div>
            <div class="action-title">Manage Subjects</div>
            <div class="action-desc">View and manage your subjects</div>
          </a>
          
          <a href="/classms/teacher/assignments.php" class="action-card" style="animation-delay: 0.15s;">
            <div class="action-icon" style="background: rgba(28, 200, 138, 0.1); color: #1cc88a;">
              <i class="fas fa-tasks"></i>
            </div>
            <div class="action-title">Assignments</div>
            <div class="action-desc">Create and grade assignments</div>
          </a>
          
          <a href="/classms/teacher/submissions.php" class="action-card" style="animation-delay: 0.2s;">
            <div class="action-icon" style="background: rgba(246, 194, 62, 0.1); color: #f6c23e;">
              <i class="fas fa-file-upload"></i>
            </div>
            <div class="action-title">Submissions</div>
            <div class="action-desc">Review student submissions</div>
          </a>
          
          <a href="/classms/teacher/term_marks.php" class="action-card" style="animation-delay: 0.25s;">
            <div class="action-icon" style="background: rgba(54, 185, 204, 0.1); color: #36b9cc;">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="action-title">Term Marks</div>
            <div class="action-desc">Manage student grades</div>
          </a>
          
          <a href="/classms/teacher/attendance.php" class="action-card" style="animation-delay: 0.3s;">
            <div class="action-icon" style="background: rgba(231, 74, 59, 0.1); color: #e74a3b;">
              <i class="fas fa-calendar-check"></i>
            </div>
            <div class="action-title">Attendance</div>
            <div class="action-desc">Track student attendance</div>
          </a>
          
          <a href="/classms/teacher/announcements.php" class="action-card" style="animation-delay: 0.35s;">
            <div class="action-icon" style="background: rgba(133, 135, 150, 0.1); color: #858796;">
              <i class="fas fa-bullhorn"></i>
            </div>
            <div class="action-title">Announcements</div>
            <div class="action-desc">Post announcements</div>
          </a>
          
          <a href="/classms/teacher/students.php" class="action-card" style="animation-delay: 0.4s;">
            <div class="action-icon" style="background: rgba(224, 225, 221, 0.1); color: #e0e1dd;">
              <i class="fas fa-users"></i>
            </div>
            <div class="action-title">View Students</div>
            <div class="action-desc">See all student details</div>
          </a>
          
          <a href="/classms/teacher/schedule.php" class="action-card" style="animation-delay: 0.45s;">
            <div class="action-icon" style="background: rgba(168, 130, 255, 0.1); color: var(--accent-purple);">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="action-title">Schedule</div>
            <div class="action-desc">View teaching schedule</div>
          </a>
        </div>
      </div>
    </div>
    
    <!-- Stats Sidebar -->
    <div class="col-lg-4">
      <div class="stats-sidebar">
        <h2 class="section-title" style="border-color: var(--accent-pink);">
          <i class="fas fa-chart-pie me-2"></i> Quick Stats
        </h2>
        <ul class="stats-list">
          <li class="stat-item" style="animation-delay: 0.1s;">
            <span class="stat-item-label">Total Students</span>
            <span class="stat-badge"><?= $studentCount ?></span>
          </li>
          <li class="stat-item" style="animation-delay: 0.2s;">
            <span class="stat-item-label">Active Assignments</span>
            <span class="stat-badge" style="background: rgba(246, 194, 62, 0.1); color: #f6c23e;">
              <?= (int)$assignments ?>
            </span>
          </li>
          <li class="stat-item" style="animation-delay: 0.3s;">
            <span class="stat-item-label">Submitted Work</span>
            <span class="stat-badge" style="background: rgba(54, 185, 204, 0.1); color: #36b9cc;">
              <?= (int)$submissions ?>
            </span>
          </li>
          <li class="stat-item" style="animation-delay: 0.4s;">
            <span class="stat-item-label">Announcements</span>
            <span class="stat-badge" style="background: rgba(231, 74, 59, 0.1); color: #e74a3b;">
              <?= (int)$announcements ?>
            </span>
          </li>
          <li class="stat-item" style="animation-delay: 0.5s;">
            <span class="stat-item-label">Subjects Assigned</span>
            <span class="stat-badge" style="background: rgba(100, 255, 218, 0.1); color: var(--accent-blue);">
              <?= (int)$subjects ?>
            </span>
          </li>
          <li class="stat-item" style="animation-delay: 0.6s;">
            <span class="stat-item-label">Profile Grade</span>
            <span class="stat-badge" style="background: rgba(168, 130, 255, 0.1); color: var(--accent-purple);">
              Teacher
            </span>
          </li>
        </ul>
        <a href="/classms/teacher/students.php" class="btn-view-students">
          <i class="fas fa-users"></i> View All Students
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Stagger animations for action cards
  const actionCards = document.querySelectorAll('.action-card');
  actionCards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.05}s`;
  });
  
  // Stagger animations for stat items
  const statItems = document.querySelectorAll('.stat-item');
  statItems.forEach((item, index) => {
    item.style.animationDelay = `${index * 0.05}s`;
  });
  
  // Card hover effects
  const cards = document.querySelectorAll('.stat-card, .action-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      if (this.classList.contains('stat-card')) {
        this.style.transform = 'translateY(-5px)';
      } else {
        this.style.transform = 'translateY(-5px) scale(1.05)';
      }
    });
    
    card.addEventListener('mouseleave', function() {
      if (this.classList.contains('stat-card')) {
        this.style.transform = 'translateY(0)';
      } else {
        this.style.transform = 'translateY(0) scale(1)';
      }
    });
  });
  
  // Icon hover effects
  const actionIcons = document.querySelectorAll('.action-icon');
  actionIcons.forEach(icon => {
    icon.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-10px) scale(1.1)';
    });
    
    icon.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });
  
  // Auto-refresh every 60 seconds
  setTimeout(() => {
    window.location.reload();
  }, 60000);
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>