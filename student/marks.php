<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");
$uid=(int)current_user()['id'];

$q = $conn->prepare("
  SELECT tm.term, tm.marks, s.name subject_name, s.grade
  FROM term_marks tm
  JOIN subjects s ON s.id=tm.subject_id
  WHERE tm.student_id=?
  ORDER BY tm.term, s.name
");
$q->bind_param("i",$uid);
$q->execute();
$res = $q->get_result();

// Calculate statistics
$term_data = [];
$subject_data = [];
$total_marks = 0;
$total_count = 0;
$max_marks = 0;
$min_marks = 100;

while($r = $res->fetch_assoc()) {
    $marks = (int)$r['marks'];
    $term = $r['term'];
    $subject = $r['subject_name'];
    
    // Track term data
    if(!isset($term_data[$term])) {
        $term_data[$term] = ['total' => 0, 'count' => 0];
    }
    $term_data[$term]['total'] += $marks;
    $term_data[$term]['count']++;
    
    // Track subject data
    if(!isset($subject_data[$subject])) {
        $subject_data[$subject] = ['total' => 0, 'count' => 0];
    }
    $subject_data[$subject]['total'] += $marks;
    $subject_data[$subject]['count']++;
    
    // Overall statistics
    $total_marks += $marks;
    $total_count++;
    $max_marks = max($max_marks, $marks);
    $min_marks = min($min_marks, $marks);
    
    // Store for later use
    $marks_data[] = $r;
}

// Calculate averages
$overall_average = $total_count > 0 ? $total_marks / $total_count : 0;
$overall_average = round($overall_average, 2);

// Reset pointer for main loop
$res->data_seek(0);

require_once __DIR__ . "/../includes/header.php";
?>

<style>
/* Dark Blue Theme with White Text - Same as announcements */
:root {
    --dark-blue: #0a192f;
    --medium-blue: #112240;
    --light-blue: #233554;
    --accent-blue: #64ffda;
    --accent-pink: #ff6b9d;
    --accent-gold: #ffd166;
    --accent-purple: #a882ff;
    --text-white: #ffffff;
    --text-light: #e6f1ff;
    --text-muted: #a8b2d1;
    --card-shadow: 0 10px 30px -15px rgba(2, 12, 27, 0.7);
}

body {
    background-color: var(--dark-blue) !important;
    color: var(--text-white) !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

/* Background Animation - Same as announcements */
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

/* Page Title Animation */
.page-title {
    color: var(--text-white);
    font-weight: 700;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--accent-gold);
    display: inline-block;
    position: relative;
    overflow: hidden;
    font-size: 2.2rem;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, var(--accent-gold), var(--accent-pink));
    transform: translateX(-100%);
    animation: slide-in 1s ease-out forwards 0.5s;
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
    border: 1px solid rgba(255, 209, 102, 0.1);
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
    border-color: var(--accent-gold);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    font-size: 1.5rem;
    animation: icon-bounce 2s infinite;
}

@keyframes icon-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 10px 0;
    background: linear-gradient(135deg, var(--accent-gold), var(--accent-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Marks Table */
.marks-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 15px;
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
    padding: 18px 15px;
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
}

@keyframes row-appear {
    to { opacity: 1; }
}

.marks-table tbody tr:hover {
    background: rgba(100, 255, 218, 0.05);
    transform: translateX(5px);
    transition: all 0.3s ease;
}

.marks-table td {
    padding: 16px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text-light);
    position: relative;
}

.marks-table tr:last-child td {
    border-bottom: none;
}

/* Term Badge */
.term-badge {
    background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-block;
    animation: badge-glow 2s infinite alternate;
}

@keyframes badge-glow {
    from { box-shadow: 0 0 5px rgba(168, 130, 255, 0.3); }
    to { box-shadow: 0 0 15px rgba(168, 130, 255, 0.6); }
}

/* Marks Badge */
.marks-badge {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    padding: 8px 18px;
    border-radius: 25px;
    font-weight: 700;
    font-size: 1rem;
    display: inline-block;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    min-width: 70px;
    text-align: center;
}

.marks-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.7s;
}

.marks-badge:hover::before {
    left: 100%;
}

.marks-badge:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 15px rgba(100, 255, 218, 0.4);
}

/* Grade Indicator */
.grade-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.grade-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
}

/* Progress Bar */
.progress-container {
    margin: 25px 0;
    animation: fade-in 0.8s ease-out forwards 0.4s;
    opacity: 0;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.progress-bar {
    height: 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple));
    border-radius: 4px;
    width: 0;
    animation: progress-fill 1.5s ease-out forwards;
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

@keyframes progress-fill {
    to { width: var(--progress-width); }
}

@keyframes progress-shine {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Back Button */
.btn-back {
    background: transparent;
    color: var(--accent-blue);
    border: 2px solid var(--accent-blue);
    padding: 12px 35px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    animation: button-appear 0.8s ease-out forwards 0.6s;
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
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .marks-container {
        padding: 20px;
        overflow-x: auto;
    }
    
    .marks-table {
        min-width: 600px;
    }
    
    .page-title {
        font-size: 1.8rem;
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
    <h1 class="page-title">📊 Term Test Marks</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card" style="animation-delay: 0.1s;">
            <div class="stat-icon" style="background: rgba(100, 255, 218, 0.1); color: var(--accent-blue);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value"><?= $overall_average ?></div>
            <div class="stat-label">Overall Average</div>
        </div>
        
        <div class="stat-card" style="animation-delay: 0.2s;">
            <div class="stat-icon" style="background: rgba(255, 107, 157, 0.1); color: var(--accent-pink);">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-value"><?= $max_marks ?></div>
            <div class="stat-label">Highest Score</div>
        </div>
        
        <div class="stat-card" style="animation-delay: 0.3s;">
            <div class="stat-icon" style="background: rgba(255, 209, 102, 0.1); color: var(--accent-gold);">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="stat-value"><?= $total_count ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        
        <div class="stat-card" style="animation-delay: 0.4s;">
            <div class="stat-icon" style="background: rgba(168, 130, 255, 0.1); color: var(--accent-purple);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-value"><?= count($subject_data) ?></div>
            <div class="stat-label">Subjects</div>
        </div>
    </div>
    
    <!-- Marks Table -->
    <div class="marks-container">
        <?php if($total_count > 0): ?>
            <table class="marks-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Term</th>
                        <th style="width: 35%;">Subject</th>
                        <th style="width: 25%;">Grade</th>
                        <th style="width: 25%;">Marks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rowDelay = 0;
                    while($r = $res->fetch_assoc()): 
                        $marks = (int)$r['marks'];
                        $percentage = ($marks / 100) * 100;
                        $rowDelay += 0.05;
                    ?>
                        <tr style="animation-delay: <?= $rowDelay ?>s;">
                            <td>
                                <span class="term-badge">
                                    <i class="fas fa-calendar-alt me-2"></i>Term <?= e($r['term']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-book me-3" style="color: var(--accent-blue);"></i>
                                    <span style="font-weight: 500;"><?= e($r['subject_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="grade-indicator">
                                    <div class="grade-dot" style="background: 
                                        <?= $marks >= 80 ? 'var(--accent-blue)' : 
                                           ($marks >= 60 ? 'var(--accent-gold)' : 
                                           'var(--accent-pink)') ?>;">
                                    </div>
                                    <span><?= e($r['grade']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="marks-badge">
                                    <?= $marks ?> / 100
                                </span>
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span>Performance</span>
                                        <span><?= $marks ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" 
                                             style="--progress-width: <?= $percentage ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 style="color: var(--accent-blue); margin-bottom: 15px;">No Marks Available</h3>
                <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                    Your term test marks will appear here once they are published by your teachers.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Term-wise Analysis -->
    <?php if(count($term_data) > 0): ?>
    <div class="marks-container" style="animation-delay: 0.4s;">
        <h3 style="color: var(--accent-blue); margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-chart-pie"></i> Term-wise Performance
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach($term_data as $term => $data): 
                $average = round($data['total'] / $data['count'], 2);
                $percentage = $average;
            ?>
            <div style="background: rgba(255, 255, 255, 0.03); padding: 20px; border-radius: 10px; border: 1px solid rgba(100, 255, 218, 0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h5 style="color: var(--text-white); margin: 0;">
                        <i class="fas fa-calendar me-2" style="color: var(--accent-purple);"></i>
                        Term <?= $term ?>
                    </h5>
                    <span class="marks-badge" style="font-size: 0.9rem; padding: 5px 15px;">
                        Avg: <?= $average ?>
                    </span>
                </div>
                <div class="progress-label">
                    <span>Average Score</span>
                    <span><?= $average ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" 
                         style="--progress-width: <?= $percentage ?>%;
                                background: linear-gradient(90deg, var(--accent-purple), var(--accent-pink));">
                    </div>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 10px;">
                    <i class="fas fa-book me-1"></i> <?= $data['count'] ?> subjects
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <a class="btn-back" href="/classms/student/dashboard.php">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
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
    
    // Stagger row animations
    const rows = document.querySelectorAll('.marks-table tbody tr');
    rows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Marks badge animation on hover
    document.querySelectorAll('.marks-badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1) rotate(2deg)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) rotate(0deg)';
        });
    });
    
    // Term badge animation
    document.querySelectorAll('.term-badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Progress bar animation on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const progressFill = entry.target.querySelector('.progress-fill');
                if (progressFill) {
                    const width = progressFill.style.getPropertyValue('--progress-width');
                    progressFill.style.width = '0%';
                    setTimeout(() => {
                        progressFill.style.width = width;
                    }, 100);
                }
            }
        });
    }, {
        threshold: 0.2
    });
    
    document.querySelectorAll('.progress-container').forEach(container => {
        observer.observe(container);
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>