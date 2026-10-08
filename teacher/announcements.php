<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");
$tid=(int)current_user()['id'];
$msg="";

if(isset($_POST['create'])){
  $subject_id = ($_POST['subject_id'] === '0') ? null : (int)$_POST['subject_id'];
  $title=trim($_POST['title'] ?? '');
  $message=trim($_POST['message'] ?? '');
  $st=$conn->prepare("INSERT INTO announcements(teacher_id,subject_id,title,message) VALUES (?,?,?,?)");
  $st->bind_param("iiss",$tid,$subject_id,$title,$message);
  $st->execute();
  $msg="<div class='alert-success'>✅ Announcement posted successfully</div>";
}

if(isset($_POST['update'])){
  $id=(int)$_POST['id'];
  $subject_id = ($_POST['subject_id'] === '0') ? null : (int)$_POST['subject_id'];
  $title=trim($_POST['title'] ?? '');
  $message=trim($_POST['message'] ?? '');
  $st=$conn->prepare("UPDATE announcements SET subject_id=?, title=?, message=? WHERE id=? AND teacher_id=?");
  $st->bind_param("issii",$subject_id,$title,$message,$id,$tid);
  $st->execute();
  $msg="<div class='alert-success'>✅ Announcement updated</div>";
}

if(isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  $st=$conn->prepare("DELETE FROM announcements WHERE id=? AND teacher_id=?");
  $st->bind_param("ii",$id,$tid);
  $st->execute();
  redirect("/classms/teacher/announcements.php");
}

$edit=null;
if(isset($_GET['edit'])){
  $id=(int)$_GET['edit'];
  $st=$conn->prepare("SELECT * FROM announcements WHERE id=? AND teacher_id=?");
  $st->bind_param("ii",$id,$tid);
  $st->execute();
  $edit=$st->get_result()->fetch_assoc();
}

$subj=$conn->prepare("SELECT id,name,grade FROM subjects WHERE teacher_id=? ORDER BY name");
$subj->bind_param("i",$tid); $subj->execute();
$subjRes=$subj->get_result();

$list=$conn->prepare("
  SELECT a.*, s.name subject_name, s.grade
  FROM announcements a
  LEFT JOIN subjects s ON s.id=a.subject_id
  WHERE a.teacher_id=?
  ORDER BY a.created_at DESC
");
$list->bind_param("i",$tid); $list->execute();
$res=$list->get_result();

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

.btn-edit-custom {
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

.btn-edit-custom:hover {
    background: rgba(100, 255, 218, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(100, 255, 218, 0.2);
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

/* Announcements Container */
.announcements-container {
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

/* Announcements Table */
.announcements-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
}

.announcements-table thead th {
    background: linear-gradient(135deg, var(--light-blue), var(--medium-blue));
    color: var(--accent-blue);
    font-weight: 600;
    padding: 18px 20px;
    border-bottom: 2px solid rgba(100, 255, 218, 0.2);
    position: relative;
    overflow: hidden;
}

.announcements-table thead th::after {
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

.announcements-table tbody tr {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
    transition: all 0.3s ease;
}

@keyframes row-appear {
    to { opacity: 1; }
}

.announcements-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
}

.announcements-table td {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    vertical-align: middle;
}

.announcements-table tr:last-child td {
    border-bottom: none;
}

/* Subject Badge */
.subject-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-general {
    background: rgba(255, 209, 102, 0.1);
    color: var(--accent-gold);
    border: 1px solid rgba(255, 209, 102, 0.3);
}

.badge-subject {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.3);
}

/* Date Badge */
.date-badge {
    color: var(--text-muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Message Preview */
.message-preview {
    color: var(--text-muted);
    font-size: 0.9rem;
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    
    .form-card, .announcements-container {
        padding: 20px;
    }
    
    .announcements-container {
        overflow-x: auto;
    }
    
    .announcements-table {
        min-width: 600px;
    }
    
    .btn-edit-custom, .btn-danger-custom {
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
                    <i class="fas fa-bullhorn"></i> Manage Announcements
                </h1>
                <div class="page-subtitle">
                    Create and manage announcements for your students
                </div>
            </div>
            <a class="btn-outline-custom" href="/classms/teacher/dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Announcement Form -->
        <div class="col-lg-4">
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-<?= $edit ? 'edit' : 'plus-circle' ?>"></i>
                    <?= $edit ? 'Edit Announcement' : 'Create New Announcement' ?>
                </div>
                
                <?= $msg ?>
                
                <form method="post">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-book"></i> Subject (Optional)
                        </label>
                        <select class="form-select-custom" name="subject_id">
                            <option value="0">📢 General Announcement (All Students)</option>
                            <?php
                                // Reset pointer for form
                                $subjRes->data_seek(0);
                                while($s = $subjRes->fetch_assoc()):
                            ?>
                                <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0)===(int)$s['id'])?'selected':'' ?>>
                                    📚 <?= e($s['name']) ?> (Grade <?= e($s['grade']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Select a subject to target specific students
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-heading"></i> Announcement Title
                        </label>
                        <input class="form-control-custom" name="title" required 
                               value="<?= e($edit['title'] ?? '') ?>"
                               placeholder="Enter announcement title">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i> Announcement Message
                        </label>
                        <textarea class="form-control-custom" name="message" rows="5" required
                                  placeholder="Type your announcement message here..."><?= e($edit['message'] ?? '') ?></textarea>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 5px;">
                            <i class="fas fa-edit"></i> Students will receive this message
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if($edit): ?>
                            <button class="btn-primary-custom" name="update">
                                <i class="fas fa-save"></i> Update Announcement
                            </button>
                            <a class="btn-outline-custom text-center" href="/classms/teacher/announcements.php">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        <?php else: ?>
                            <button class="btn-primary-custom" name="create">
                                <i class="fas fa-paper-plane"></i> Post Announcement
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Quick Stats -->
            <?php 
            $totalAnnouncements = $res->num_rows;
            $res->data_seek(0);
            $subjectAnnouncements = 0;
            $generalAnnouncements = 0;
            
            while($r = $res->fetch_assoc()) {
                if ($r['subject_id']) {
                    $subjectAnnouncements++;
                } else {
                    $generalAnnouncements++;
                }
            }
            $res->data_seek(0);
            ?>
            <div class="form-card">
                <div class="form-title">
                    <i class="fas fa-chart-pie"></i> Announcement Stats
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div style="background: rgba(100, 255, 218, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(100, 255, 218, 0.2);">
                        <div style="color: var(--accent-blue); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $totalAnnouncements ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Total</div>
                    </div>
                    <div style="background: rgba(255, 209, 102, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(255, 209, 102, 0.2);">
                        <div style="color: var(--accent-gold); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $subjectAnnouncements ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Subject Specific</div>
                    </div>
                    <div style="background: rgba(168, 130, 255, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(168, 130, 255, 0.2);">
                        <div style="color: var(--accent-purple); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= $generalAnnouncements ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">General</div>
                    </div>
                    <div style="background: rgba(76, 217, 100, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(76, 217, 100, 0.2);">
                        <div style="color: var(--accent-green); font-size: 2rem; font-weight: 700; margin-bottom: 5px;">
                            <?= date('M j') ?>
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Today</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Announcements List -->
        <div class="col-lg-8">
            <div class="announcements-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="form-title mb-0">
                            <i class="fas fa-list-check"></i> My Announcements
                        </h2>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">
                            Total: <?= $totalAnnouncements ?> announcements
                        </div>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-clock"></i> <?= date('F j, Y') ?>
                    </div>
                </div>
                
                <!-- Announcements Table -->
                <div class="table-responsive">
                    <table class="announcements-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Title</th>
                                <th style="width: 20%;">Subject</th>
                                <th style="width: 20%;">Date Posted</th>
                                <th style="width: 30%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($res->num_rows === 0): ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-bullhorn"></i>
                                            </div>
                                            <h3 style="color: var(--accent-blue); margin-bottom: 15px;">
                                                No Announcements Yet
                                            </h3>
                                            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                                                You haven't created any announcements yet. Start by creating your first announcement!
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: 
                                $rowDelay = 0;
                                while($r = $res->fetch_assoc()): 
                                    $rowDelay += 0.05;
                                    $subjectName = $r['subject_name'] ? e($r['subject_name']) . ' (Grade ' . e($r['grade']) . ')' : 'General';
                            ?>
                                <tr style="animation-delay: <?= $rowDelay ?>s;">
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-white); margin-bottom: 5px;">
                                            <?= e($r['title']) ?>
                                        </div>
                                        <div class="message-preview">
                                            <?= substr(e($r['message']), 0, 50) ?>...
                                        </div>
                                    </td>
                                    <td>
                                        <span class="subject-badge <?= $r['subject_id'] ? 'badge-subject' : 'badge-general' ?>">
                                            <i class="fas fa-<?= $r['subject_id'] ? 'book' : 'bullhorn' ?>"></i>
                                            <?= $subjectName ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-badge">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?= date('M j, Y', strtotime($r['created_at'])) ?>
                                        </div>
                                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 3px;">
                                            <?= date('g:i A', strtotime($r['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn-edit-custom" href="?edit=<?= (int)$r['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a class="btn-danger-custom"
                                               onclick="return confirm('Are you sure you want to delete this announcement?')"
                                               href="?delete=<?= (int)$r['id'] ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <?php if($r['subject_id']): ?>
                                                <a class="btn-edit-custom" href="/classms/teacher/submissions.php?subject_id=<?= (int)$r['subject_id'] ?>">
                                                    <i class="fas fa-eye"></i> View Students
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Info Tip -->
                <div style="background: rgba(100, 255, 218, 0.05); border: 1px solid rgba(100, 255, 218, 0.1); border-radius: 10px; padding: 20px; margin-top: 20px;">
                    <div style="color: var(--accent-gold); display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-lightbulb" style="font-size: 1.2rem;"></i>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 5px; color: var(--text-white);">Announcement Tips</div>
                            <div style="color: var(--text-light); font-size: 0.9rem;">
                                • <b>General announcements</b> are visible to all your students<br>
                                • <b>Subject-specific announcements</b> are only visible to students enrolled in that subject<br>
                                • Use clear, concise titles and include all necessary information in the message
                            </div>
                        </div>
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
    const rows = document.querySelectorAll('.announcements-table tbody tr');
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
    
    // Subject selection animation
    const subjectSelect = document.querySelector('select[name="subject_id"]');
    subjectSelect.addEventListener('change', function() {
        const optionText = this.options[this.selectedIndex].text;
        const icon = optionText.includes('General') ? '📢' : '📚';
        
        // Animate the label
        const label = this.previousElementSibling;
        label.style.transform = 'translateY(-3px)';
        label.style.color = 'var(--accent-blue)';
        
        setTimeout(() => {
            label.style.transform = 'translateY(0)';
            label.style.color = 'var(--text-light)';
        }, 300);
    });
    
    // Delete confirmation with animation
    document.querySelectorAll('.btn-danger-custom').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this announcement?')) {
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
    
    // Subject badge hover effect
    const subjectBadges = document.querySelectorAll('.subject-badge');
    subjectBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            if (this.classList.contains('badge-general')) {
                this.style.transform = 'scale(1.05) rotate(2deg)';
            } else {
                this.style.transform = 'scale(1.05) rotate(-2deg)';
            }
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>