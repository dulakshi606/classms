<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");

$uid = (int) current_user()['id'];

/* -------------------------
   Get student grade (default)
--------------------------*/
$g = $conn->prepare("SELECT grade FROM student_profiles WHERE user_id=?");
$g->bind_param("i", $uid);
$g->execute();
$profileGrade = ($g->get_result()->fetch_assoc()['grade'] ?? '10');

/* -------------------------
   Grade filter (6 - 12)
   - Student can change grade using dropdown
   - Default uses profile grade
--------------------------*/
$grade = trim($_GET['grade'] ?? $profileGrade);
$allowedGrades = ['6','7','8','9','10','11','12'];
if (!in_array($grade, $allowedGrades, true)) $grade = $profileGrade;

/* -------------------------
   Enroll / Drop
--------------------------*/
if (isset($_GET['enroll'])) {
  $sid = (int)$_GET['enroll'];
  $stmt = $conn->prepare("INSERT IGNORE INTO enrollments(student_id, subject_id) VALUES (?,?)");
  $stmt->bind_param("ii", $uid, $sid);
  $stmt->execute();
  redirect("/classms/student/subjects.php?grade=" . urlencode($grade));
}

if (isset($_GET['drop'])) {
  $sid = (int)$_GET['drop'];
  $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id=? AND subject_id=?");
  $stmt->bind_param("ii", $uid, $sid);
  $stmt->execute();
  redirect("/classms/student/subjects.php?grade=" . urlencode($grade));
}

/* -------------------------
   Search
--------------------------*/
$q = trim($_GET['q'] ?? '');
$like = "%" . $q . "%";

/* -------------------------
   List subjects (approved only)
   + teacher name
   + enrolled flag
   + grade filter 6-12
   + search by subject/teacher
--------------------------*/
if ($q !== '') {
  $subs = $conn->prepare("
    SELECT s.*, u.full_name teacher_name,
      EXISTS(SELECT 1 FROM enrollments e WHERE e.student_id=? AND e.subject_id=s.id) AS enrolled
    FROM subjects s
    JOIN users u ON u.id=s.teacher_id
    WHERE s.grade=? AND s.status='approved'
      AND (s.name LIKE ? OR u.full_name LIKE ?)
    ORDER BY s.name
  ");
  $subs->bind_param("isss", $uid, $grade, $like, $like);
} else {
  $subs = $conn->prepare("
    SELECT s.*, u.full_name teacher_name,
      EXISTS(SELECT 1 FROM enrollments e WHERE e.student_id=? AND e.subject_id=s.id) AS enrolled
    FROM subjects s
    JOIN users u ON u.id=s.teacher_id
    WHERE s.grade=? AND s.status='approved'
    ORDER BY s.name
  ");
  $subs->bind_param("is", $uid, $grade);
}

$subs->execute();
$list = $subs->get_result();

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
    margin-bottom: 5px;
}

.grade-badge {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
    color: white;
    padding: 6px 20px;
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

/* Back Button */
.btn-back {
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
    animation: button-appear 0.8s ease-out forwards;
    opacity: 0;
}

@keyframes button-appear {
    to { opacity: 1; }
}

.btn-back::before {
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

.btn-back:hover::before {
    left: 100%;
}

.btn-back:hover {
    background: rgba(100, 255, 218, 0.1);
    color: var(--text-white);
    transform: translateX(-5px);
    box-shadow: 0 5px 20px rgba(100, 255, 218, 0.3);
}

/* Filter Card */
.filter-card {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: filter-appear 0.6s ease-out forwards 0.2s;
    opacity: 0;
}

@keyframes filter-appear {
    to { opacity: 1; }
}

.filter-label {
    color: var(--text-light);
    font-weight: 500;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
}

.filter-label i {
    color: var(--accent-blue);
    width: 20px;
}

/* Custom Form Controls */
.form-select-custom {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
    padding: 12px 15px !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364ffda' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 15px center !important;
    background-size: 16px !important;
}

.form-select-custom:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
    transform: translateY(-2px);
}

.form-input-custom {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
    padding: 12px 15px !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
}

.form-input-custom:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
    transform: translateY(-2px);
}

.form-input-custom::placeholder {
    color: var(--text-muted) !important;
}

/* Search Button */
.btn-search {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    height: 46px;
}

.btn-search::before {
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

.btn-search:hover::before {
    left: 100%;
}

.btn-search:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 25px rgba(100, 255, 218, 0.4);
}

/* Subjects Table */
.subjects-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 15px;
    padding: 30px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    animation: container-appear 0.8s ease-out forwards 0.4s;
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
    transform: translateX(10px);
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

/* Subject Name Cell */
.subject-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-white);
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

/* Teacher Name Cell */
.teacher-name {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-light);
}

.teacher-icon {
    color: var(--accent-pink);
}

/* Action Buttons */
.btn-action {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 120px;
}

.btn-enroll {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-enroll::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.7s;
}

.btn-enroll:hover::before {
    left: 100%;
}

.btn-enroll:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px rgba(100, 255, 218, 0.4);
}

.btn-drop {
    background: transparent;
    color: var(--accent-red);
    border: 2px solid var(--accent-red);
    position: relative;
    overflow: hidden;
}

.btn-drop::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 71, 87, 0.2), transparent);
    transition: left 0.7s;
}

.btn-drop:hover::before {
    left: 100%;
}

.btn-drop:hover {
    background: rgba(255, 71, 87, 0.1);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 71, 87, 0.2);
}

/* Enrollment Status */
.enrollment-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-enrolled {
    background: rgba(76, 217, 100, 0.1);
    color: var(--accent-green);
    border: 1px solid rgba(76, 217, 100, 0.3);
}

.status-not-enrolled {
    background: rgba(168, 130, 255, 0.1);
    color: var(--accent-purple);
    border: 1px solid rgba(168, 130, 255, 0.3);
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
    animation: tip-appear 0.8s ease-out forwards 0.6s;
    opacity: 0;
}

@keyframes tip-appear {
    to { opacity: 1; }
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
    
    .subjects-container {
        padding: 20px;
        overflow-x: auto;
    }
    
    .subjects-table {
        min-width: 600px;
    }
    
    .btn-action {
        min-width: 100px;
        padding: 8px 16px;
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
                    <i class="fas fa-book-open"></i> Select Subjects
                </h1>
                <div class="page-subtitle mb-2">
                    Browse and enroll in approved subjects for your grade level
                </div>
                <div class="grade-badge">
                    <i class="fas fa-graduation-cap"></i> Your Profile Grade: <?= e($profileGrade) ?>
                </div>
            </div>
            <a class="btn-back" href="/classms/student/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <form method="get" class="filter-card">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="filter-label">
                    <i class="fas fa-filter"></i> Grade Filter
                </label>
                <select class="form-select-custom" name="grade" onchange="this.form.submit()">
                    <?php foreach($allowedGrades as $gr): ?>
                        <option value="<?= $gr ?>" <?= ($grade === $gr) ? 'selected' : '' ?>>
                            Grade <?= $gr ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="text-muted small mt-2">
                    <i class="fas fa-info-circle"></i> Currently viewing: Grade <?= e($grade) ?>
                </div>
            </div>

            <div class="col-md-7">
                <label class="filter-label">
                    <i class="fas fa-search"></i> Search Subjects
                </label>
                <input class="form-input-custom" name="q" value="<?= e($q) ?>"
                       placeholder="Search by subject name or teacher...">
            </div>

            <div class="col-md-2">
                <label class="filter-label">&nbsp;</label>
                <button class="btn-search" type="submit">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </div>
    </form>

    <!-- Subjects Table -->
    <div class="subjects-container">
        <div class="table-responsive">
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">
                            <i class="fas fa-book me-2"></i> Subject
                        </th>
                        <th style="width: 30%;">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Teacher
                        </th>
                        <th style="width: 30%;">
                            <i class="fas fa-tasks me-2"></i> Actions & Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($list->num_rows > 0): ?>
                        <?php 
                        $rowDelay = 0;
                        $subjectIcons = [
                            'math' => 'fas fa-calculator',
                            'science' => 'fas fa-flask',
                            'english' => 'fas fa-language',
                            'history' => 'fas fa-landmark',
                            'computer' => 'fas fa-laptop-code',
                            'physics' => 'fas fa-atom',
                            'chemistry' => 'fas fa-vial',
                            'biology' => 'fas fa-dna',
                            'art' => 'fas fa-palette',
                            'music' => 'fas fa-music',
                            'pe' => 'fas fa-running',
                            'geography' => 'fas fa-globe-americas'
                        ];
                        
                        while($r = $list->fetch_assoc()): 
                            $rowDelay += 0.05;
                            $subjectLower = strtolower($r['name']);
                            $icon = 'fas fa-book';
                            
                            foreach($subjectIcons as $key => $iconClass) {
                                if (strpos($subjectLower, $key) !== false) {
                                    $icon = $iconClass;
                                    break;
                                }
                            }
                        ?>
                            <tr style="animation-delay: <?= $rowDelay ?>s;">
                                <td>
                                    <div class="subject-name">
                                        <div class="subject-icon">
                                            <i class="<?= $icon ?>"></i>
                                        </div>
                                        <div>
                                            <?= e($r['name']) ?>
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-layer-group"></i> Grade <?= e($r['grade']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="teacher-name">
                                        <i class="fas fa-user-tie teacher-icon"></i>
                                        <span><?= e($r['teacher_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="enrollment-status <?= ((int)$r['enrolled'] === 1) ? 'status-enrolled' : 'status-not-enrolled' ?>">
                                            <?php if((int)$r['enrolled'] === 1): ?>
                                                <i class="fas fa-check-circle"></i> Currently Enrolled
                                            <?php else: ?>
                                                <i class="fas fa-clock"></i> Available for Enrollment
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if((int)$r['enrolled'] === 1): ?>
                                                <a class="btn-action btn-drop"
                                                   data-confirm="Are you sure you want to drop this subject? This action cannot be undone."
                                                   href="?grade=<?= urlencode($grade) ?>&q=<?= urlencode($q) ?>&drop=<?= (int)$r['id'] ?>">
                                                    <i class="fas fa-trash-alt"></i> Drop Subject
                                                </a>
                                            <?php else: ?>
                                                <a class="btn-action btn-enroll"
                                                   href="?grade=<?= urlencode($grade) ?>&q=<?= urlencode($q) ?>&enroll=<?= (int)$r['id'] ?>">
                                                    <i class="fas fa-user-plus"></i> Enroll Now
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                        No Subjects Found
                                    </h3>
                                    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 20px;">
                                        <?php if($q !== ''): ?>
                                            No subjects found matching "<b><?= e($q) ?></b>" for Grade <?= e($grade) ?>.
                                        <?php else: ?>
                                            No approved subjects available for Grade <?= e($grade) ?> at the moment.
                                        <?php endif; ?>
                                    </p>
                                    <a href="?grade=<?= urlencode($grade) ?>" class="btn-enroll" style="display: inline-flex;">
                                        <i class="fas fa-redo"></i> Clear Search
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
                If you cannot see subjects for your grade, please ask your teacher to set the subject status to <b>approved</b>.
            </span>
        </div>
    </div>

    <!-- Quick Stats -->
    <?php 
    $totalSubjects = $list->num_rows;
    $list->data_seek(0);
    $enrolledCount = 0;
    while($r = $list->fetch_assoc()) {
        if((int)$r['enrolled'] === 1) $enrolledCount++;
    }
    ?>
    <div class="filter-card">
        <h4 style="color: var(--accent-blue); margin-bottom: 20px;">
            <i class="fas fa-chart-pie me-2"></i> Quick Stats
        </h4>
        <div class="row g-3">
            <div class="col-md-4">
                <div style="background: rgba(100, 255, 218, 0.05); padding: 20px; border-radius: 10px; border: 1px solid rgba(100, 255, 218, 0.1);">
                    <div style="color: var(--accent-blue); font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?= $totalSubjects ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-book me-1"></i> Available Subjects
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background: rgba(76, 217, 100, 0.05); padding: 20px; border-radius: 10px; border: 1px solid rgba(76, 217, 100, 0.1);">
                    <div style="color: var(--accent-green); font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?= $enrolledCount ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-check-circle me-1"></i> Currently Enrolled
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background: rgba(168, 130, 255, 0.05); padding: 20px; border-radius: 10px; border: 1px solid rgba(168, 130, 255, 0.1);">
                    <div style="color: var(--accent-purple); font-size: 2.5rem; font-weight: 700; margin-bottom: 5px;">
                        <?= $totalSubjects - $enrolledCount ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-clock me-1"></i> Available for Enrollment
                    </div>
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
    const particleCount = 25;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Random properties with different colors
        const size = Math.random() * 6 + 2;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const delay = Math.random() * 15;
        const duration = Math.random() * 15 + 15;
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
    
    // Action button animations
    document.querySelectorAll('.btn-action').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            if (this.classList.contains('btn-enroll')) {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            } else if (this.classList.contains('btn-drop')) {
                this.style.transform = 'translateY(-3px)';
            }
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Search form animation
    const searchForm = document.querySelector('form[method="get"]');
    const searchInput = searchForm.querySelector('input[name="q"]');
    const searchBtn = searchForm.querySelector('button[type="submit"]');
    
    searchInput.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
        searchBtn.style.transform = 'translateY(-2px)';
    });
    
    searchInput.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
        searchBtn.style.transform = 'translateY(0)';
    });
    
    // Grade select animation
    const gradeSelect = document.querySelector('select[name="grade"]');
    gradeSelect.addEventListener('change', function() {
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 200);
    });
    
    // Drop confirmation
    document.querySelectorAll('.btn-drop').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm');
            if (confirmMessage && !confirm(confirmMessage)) {
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
    
    // Subject icon hover effect
    document.querySelectorAll('.subject-icon').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            this.style.transform = 'rotate(10deg) scale(1.1)';
            this.style.background = 'rgba(100, 255, 218, 0.2)';
        });
        
        icon.addEventListener('mouseleave', function() {
            this.style.transform = 'rotate(0) scale(1)';
            this.style.background = 'rgba(100, 255, 218, 0.1)';
        });
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>