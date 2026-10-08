<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");
$uid=(int)current_user()['id'];
$msg="";

if($_SERVER['REQUEST_METHOD']==='POST'){
  $aid=(int)($_POST['announcement_id'] ?? 0);
  $text=trim($_POST['reply_text'] ?? '');
  if($aid>0 && $text!==''){
    $st=$conn->prepare("INSERT INTO announcement_replies(announcement_id,student_id,reply_text) VALUES (?,?,?)");
    $st->bind_param("iis",$aid,$uid,$text);
    $st->execute();
    $msg="<div class='alert-success animate-fade-in'>Reply sent successfully!</div>";
  }
}

$ann = $conn->query("
  SELECT a.*, u.full_name teacher_name, s.name subject_name
  FROM announcements a
  JOIN users u ON u.id=a.teacher_id
  LEFT JOIN subjects s ON s.id=a.subject_id
  ORDER BY a.created_at DESC
");

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
    background: rgba(100, 255, 218, 0.1);
    border-radius: 50%;
    animation: float 15s infinite linear;
}

@keyframes float {
    0% { transform: translateY(0) rotate(0deg); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
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

/* Page Title Animation */
.page-title {
    color: var(--text-white);
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--accent-blue);
    display: inline-block;
    position: relative;
    overflow: hidden;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-pink));
    transform: translateX(-100%);
    animation: slide-in 1s ease-out forwards 0.5s;
}

@keyframes slide-in {
    to { transform: translateX(0); }
}

/* Announcement Cards */
.announcement-card {
    background-color: var(--medium-blue);
    border: 1px solid var(--light-blue);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--card-shadow);
    animation: card-appear 0.6s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}

@keyframes card-appear {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.announcement-card:hover {
    transform: translateY(-5px) scale(1.01);
    box-shadow: 0 20px 40px rgba(2, 12, 27, 0.8);
    border-color: var(--accent-blue);
}

.announcement-header {
    border-bottom: 1px solid var(--light-blue);
    padding-bottom: 15px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.announcement-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 1px;
    background: linear-gradient(90deg, var(--accent-blue), var(--accent-pink));
    animation: expand-line 0.8s ease-out forwards 0.3s;
}

@keyframes expand-line {
    to { width: 100%; }
}

.announcement-title {
    color: var(--text-white);
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 10px;
    background: linear-gradient(135deg, var(--accent-blue), var(--text-white));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: text-glow 3s ease-in-out infinite alternate;
}

@keyframes text-glow {
    from { text-shadow: 0 0 5px rgba(100, 255, 218, 0.2); }
    to { text-shadow: 0 0 15px rgba(100, 255, 218, 0.4); }
}

.announcement-meta {
    color: var(--text-muted);
    font-size: 0.9rem;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.announcement-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.announcement-meta span:hover {
    color: var(--accent-blue);
    transform: translateX(3px);
}

.announcement-content {
    color: var(--text-light);
    line-height: 1.7;
    font-size: 1.1rem;
    padding: 15px 0;
    opacity: 0;
    animation: fade-in 0.5s ease-out forwards 0.4s;
}

@keyframes fade-in {
    to { opacity: 1; }
}

/* Reply Form Animation */
.reply-form {
    background: linear-gradient(145deg, var(--light-blue), var(--medium-blue));
    padding: 25px;
    border-radius: 12px;
    margin-top: 25px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    position: relative;
    overflow: hidden;
    animation: form-slide-up 0.5s ease-out forwards 0.6s;
    opacity: 0;
}

@keyframes form-slide-up {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.reply-form::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(100, 255, 218, 0.1), transparent);
    transform: rotate(45deg);
    animation: shine 3s infinite linear;
}

@keyframes shine {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}

.reply-form textarea {
    background-color: rgba(26, 54, 93, 0.7);
    color: var(--text-white);
    border: 1px solid var(--light-blue);
    border-radius: 8px;
    padding: 15px;
    resize: vertical;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.reply-form textarea:focus {
    background-color: rgba(26, 54, 93, 0.9);
    color: var(--text-white);
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.2);
    transform: translateY(-2px);
}

.reply-form textarea::placeholder {
    color: var(--text-muted);
    transition: color 0.3s ease;
}

.reply-form textarea:focus::placeholder {
    color: var(--accent-blue);
}

/* Button Animations */
.btn-primary-custom {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 15px;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.btn-primary-custom::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.7s;
}

.btn-primary-custom:hover::before {
    left: 100%;
}

.btn-primary-custom:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 25px rgba(100, 255, 218, 0.4);
}

.btn-primary-custom:active {
    transform: translateY(-1px) scale(1.02);
}

/* Reply History Animation */
.reply-history {
    background: linear-gradient(145deg, var(--light-blue), rgba(35, 53, 84, 0.8));
    border-radius: 12px;
    padding: 20px;
    margin-top: 25px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    animation: slide-up 0.5s ease-out forwards 0.8s;
    opacity: 0;
}

.reply-history-title {
    color: var(--accent-blue);
    font-weight: 600;
    font-size: 1.2rem;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(100, 255, 218, 0.2);
    position: relative;
}

.reply-history-title::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 60px;
    height: 2px;
    background: var(--accent-blue);
    animation: width-grow 1s ease-out forwards 1s;
}

@keyframes width-grow {
    to { width: 100px; }
}

.reply-item {
    padding: 15px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    animation: reply-item-appear 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes reply-item-appear {
    to { opacity: 1; }
}

.reply-item:last-child {
    border-bottom: none;
}

.reply-item:hover {
    padding-left: 10px;
    border-left: 3px solid var(--accent-blue);
    transition: all 0.3s ease;
}

.reply-date {
    color: var(--accent-pink);
    font-size: 0.85rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.reply-text {
    color: var(--text-light);
    font-size: 1rem;
    line-height: 1.6;
    padding-left: 20px;
    position: relative;
}

.reply-text::before {
    content: '»';
    position: absolute;
    left: 0;
    color: var(--accent-blue);
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(3px); }
}

/* Alert Animation */
.alert-success {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    backdrop-filter: blur(10px);
    animation: pulse 2s infinite, slide-down 0.5s ease-out;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(100, 255, 218, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(100, 255, 218, 0); }
}

@keyframes slide-down {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Back Button Animation */
.btn-outline-custom {
    background: transparent;
    color: var(--accent-blue);
    border: 2px solid var(--accent-blue);
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-top: 20px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
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

/* Floating animation for announcement cards */
.announcement-card:nth-child(1) { animation-delay: 0.1s; }
.announcement-card:nth-child(2) { animation-delay: 0.2s; }
.announcement-card:nth-child(3) { animation-delay: 0.3s; }
.announcement-card:nth-child(4) { animation-delay: 0.4s; }
.announcement-card:nth-child(5) { animation-delay: 0.5s; }

.reply-item:nth-child(1) { animation-delay: 0.1s; }
.reply-item:nth-child(2) { animation-delay: 0.2s; }
.reply-item:nth-child(3) { animation-delay: 0.3s; }

/* Loading animation */
@keyframes loading {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid var(--text-muted);
    border-top-color: var(--accent-blue);
    border-radius: 50%;
    animation: loading 1s linear infinite;
}

/* Scroll animation */
.announcement-card {
    transition: opacity 0.6s ease, transform 0.6s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .announcement-card {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .announcement-title {
        font-size: 1.3rem;
    }
    
    .container-custom {
        padding: 20px 15px;
    }
    
    .announcement-meta {
        flex-direction: column;
        gap: 8px;
    }
}

/* Smooth scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 10px;
}

::-webkit-scrollbar-track {
    background: var(--dark-blue);
}

::-webkit-scrollbar-thumb {
    background: var(--accent-blue);
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #52d3b8;
}
</style>

<!-- Background Animation -->
<div class="bg-animation"></div>
<div class="particles" id="particles-container"></div>

<div class="container-custom">
    <h1 class="page-title">📢 Announcements</h1>
    
    <?= $msg ?>

    <div class="announcements-list">
        <?php 
        $cardCount = 0;
        while($a=$ann->fetch_assoc()): 
            $cardCount++;
        ?>
            <div class="announcement-card" data-delay="<?= $cardCount * 0.1 ?>">
                <div class="announcement-header">
                    <div class="announcement-title"><?= e($a['title']) ?></div>
                    <div class="announcement-meta">
                        <span><i class="fas fa-chalkboard-teacher"></i> Teacher: <?= e($a['teacher_name']) ?></span>
                        <span><i class="fas fa-book"></i> Subject: <?= e($a['subject_name'] ?? 'General') ?></span>
                        <span><i class="far fa-clock"></i> <?= e($a['created_at']) ?></span>
                    </div>
                </div>
                
                <div class="announcement-content">
                    <?= nl2br(e($a['message'])) ?>
                </div>

                <form method="post" class="reply-form">
                    <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                    <textarea class="form-control" name="reply_text" rows="3" placeholder="💬 Type your reply to the teacher..." required></textarea>
                    <button class="btn-primary-custom" type="submit">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </form>

                <?php
                    $rid=(int)$a['id'];
                    $rep = $conn->prepare("SELECT reply_text,created_at FROM announcement_replies WHERE announcement_id=? AND student_id=? ORDER BY created_at DESC");
                    $rep->bind_param("ii",$rid,$uid);
                    $rep->execute();
                    $reps=$rep->get_result();
                ?>
                <?php if($reps->num_rows>0): ?>
                    <div class="reply-history">
                        <div class="reply-history-title"><i class="fas fa-history"></i> My Replies</div>
                        <?php 
                        $replyCount = 0;
                        while($r=$reps->fetch_assoc()): 
                            $replyCount++;
                        ?>
                            <div class="reply-item" data-delay="<?= $replyCount * 0.1 ?>">
                                <div class="reply-date">
                                    <i class="far fa-calendar-alt"></i> <?= e($r['created_at']) ?>
                                </div>
                                <div class="reply-text"><?= nl2br(e($r['reply_text'])) ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        
        <?php if($cardCount == 0): ?>
            <div class="announcement-card" style="text-align: center; padding: 50px;">
                <i class="fas fa-bullhorn" style="font-size: 48px; color: var(--accent-blue); margin-bottom: 20px;"></i>
                <h3 style="color: var(--accent-blue);">No Announcements Yet</h3>
                <p style="color: var(--text-muted);">Check back later for updates from your teachers.</p>
            </div>
        <?php endif; ?>
    </div>

    <a class="btn-outline-custom" href="/classms/student/dashboard.php">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
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
        const delay = Math.random() * 15;
        const duration = Math.random() * 10 + 10;
        
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${posX}%`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;
        
        container.appendChild(particle);
    }
    
    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const delay = entry.target.getAttribute('data-delay') || 0;
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, delay * 1000);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });
    
    // Observe all announcement cards
    document.querySelectorAll('.announcement-card, .reply-item').forEach(el => {
        observer.observe(el);
    });
    
    // Form submission animation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="loading-spinner"></span> Sending...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        });
    });
    
    // Hover effect for cards
    document.querySelectorAll('.announcement-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-5px) scale(1.01)';
        });
    });
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>