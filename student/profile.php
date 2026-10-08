<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");
$uid = (int)current_user()['id'];
$msg = "";

// Get user info for display
$user_info = $conn->prepare("SELECT full_name, email FROM users WHERE id=?");
$user_info->bind_param("i", $uid);
$user_info->execute();
$user_data = $user_info->get_result()->fetch_assoc();

$p = $conn->prepare("SELECT * FROM student_profiles WHERE user_id=?");
$p->bind_param("i",$uid); $p->execute();
$profile = $p->get_result()->fetch_assoc();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $grade = trim($_POST['grade'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $gname = trim($_POST['guardian_name'] ?? '');
  $gphone = trim($_POST['guardian_phone'] ?? '');
  $dob = $_POST['dob'] ?? null;

  if($profile){
    $stmt = $conn->prepare("UPDATE student_profiles SET grade=?,address=?,guardian_name=?,guardian_phone=?,dob=? WHERE user_id=?");
    $stmt->bind_param("sssssi",$grade,$address,$gname,$gphone,$dob,$uid);
  }else{
    $stmt = $conn->prepare("INSERT INTO student_profiles(user_id,grade,address,guardian_name,guardian_phone,dob) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss",$uid,$grade,$address,$gname,$gphone,$dob);
  }
  $stmt->execute();
  $msg = "<div class='alert-success'>Profile updated successfully!</div>";

  $p->execute();
  $profile = $p->get_result()->fetch_assoc();
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

/* Profile Header */
.profile-header {
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

.profile-header::before {
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

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 20px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    animation: avatar-pulse 3s infinite;
}

@keyframes avatar-pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(100, 255, 218, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(100, 255, 218, 0); }
}

.profile-name {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
    background: linear-gradient(135deg, var(--accent-blue), var(--text-white));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.profile-email {
    color: var(--text-muted);
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-email i {
    color: var(--accent-blue);
}

.profile-role {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-block;
    animation: role-glow 2s infinite alternate;
}

@keyframes role-glow {
    from { box-shadow: 0 0 5px rgba(100, 255, 218, 0.3); }
    to { box-shadow: 0 0 15px rgba(100, 255, 218, 0.6); }
}

/* Form Container */
.form-container {
    background: linear-gradient(145deg, var(--medium-blue), var(--light-blue));
    border-radius: 20px;
    padding: 40px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    box-shadow: var(--card-shadow);
    animation: form-appear 0.8s ease-out forwards 0.2s;
    opacity: 0;
}

@keyframes form-appear {
    to { opacity: 1; }
}

/* Form Title */
.form-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 30px;
    color: var(--text-white);
    position: relative;
    padding-bottom: 15px;
}

.form-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--accent-blue);
    animation: title-line 1s ease-out forwards;
}

@keyframes title-line {
    from { width: 0; }
    to { width: 60px; }
}

/* Form Labels */
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

/* Form Inputs */
.form-control-custom {
    background-color: var(--input-bg) !important;
    color: var(--text-white) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 10px !important;
    padding: 12px 15px !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
}

.form-control-custom:focus {
    background-color: rgba(26, 54, 93, 0.9) !important;
    color: var(--text-white) !important;
    border-color: var(--accent-blue) !important;
    box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1) !important;
    transform: translateY(-2px);
}

.form-control-custom::placeholder {
    color: var(--text-muted) !important;
}

/* Form Groups */
.form-group {
    margin-bottom: 25px;
    animation: form-group-appear 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes form-group-appear {
    to { opacity: 1; }
}

/* Success Message */
.alert-success {
    background: rgba(100, 255, 218, 0.1);
    color: var(--accent-blue);
    border: 1px solid rgba(100, 255, 218, 0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
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

/* Buttons */
.btn-submit {
    background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
    color: var(--dark-blue);
    border: none;
    padding: 14px 40px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-submit::before {
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

.btn-submit:hover::before {
    left: 100%;
}

.btn-submit:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 25px rgba(100, 255, 218, 0.4);
}

.btn-submit:active {
    transform: translateY(-1px) scale(1.02);
}

.btn-back {
    background: transparent;
    color: var(--accent-blue);
    border: 2px solid var(--accent-blue);
    padding: 14px 40px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
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

/* Form Row Animation */
.form-row {
    animation: row-appear 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes row-appear {
    to { opacity: 1; }
}

/* Info Cards */
.info-card {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 25px;
    border: 1px solid rgba(100, 255, 218, 0.1);
    margin-bottom: 25px;
    transition: all 0.3s ease;
    animation: info-card-appear 0.6s ease-out forwards;
    opacity: 0;
}

@keyframes info-card-appear {
    to { opacity: 1; }
}

.info-card:hover {
    transform: translateY(-5px);
    border-color: var(--accent-blue);
    box-shadow: 0 10px 30px rgba(2, 12, 27, 0.5);
}

.info-card-title {
    color: var(--accent-blue);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-card-content {
    color: var(--text-light);
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .container-custom {
        padding: 20px 15px;
    }
    
    .profile-header {
        padding: 20px;
        text-align: center;
    }
    
    .form-container {
        padding: 25px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
        margin: 0 auto 15px;
    }
    
    .profile-name {
        font-size: 1.6rem;
    }
    
    .btn-submit, .btn-back {
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
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
<div class="particles" id="particles-container"></div>

<div class="container-custom">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?= strtoupper(substr($user_data['full_name'], 0, 1)) ?>
        </div>
        <div class="profile-name"><?= e($user_data['full_name']) ?></div>
        <div class="profile-email">
            <i class="fas fa-envelope"></i> <?= e($user_data['email']) ?>
        </div>
        <div class="profile-role">
            <i class="fas fa-user-graduate"></i> Student Profile
        </div>
    </div>
    
    <?= $msg ?>
    
    <!-- Form Container -->
    <div class="form-container">
        <h2 class="form-title">
            <i class="fas fa-user-edit"></i> Edit Student Details
        </h2>
        
        <form method="post" class="row g-4">
            <!-- Basic Information Card -->
            <div class="info-card col-12" style="animation-delay: 0.1s;">
                <div class="info-card-title">
                    <i class="fas fa-user-circle"></i> Basic Information
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-graduation-cap"></i> Grade
                        </label>
                        <input class="form-control form-control-custom" name="grade" required 
                               value="<?= e($profile['grade'] ?? '10') ?>"
                               placeholder="Enter your grade">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">
                            <i class="fas fa-home"></i> Address
                        </label>
                        <input class="form-control form-control-custom" name="address" 
                               value="<?= e($profile['address'] ?? '') ?>"
                               placeholder="Enter your complete address">
                    </div>
                </div>
            </div>
            
            <!-- Guardian Information Card -->
            <div class="info-card col-12" style="animation-delay: 0.2s;">
                <div class="info-card-title">
                    <i class="fas fa-users"></i> Guardian Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-user-friends"></i> Guardian Name
                        </label>
                        <input class="form-control form-control-custom" name="guardian_name" 
                               value="<?= e($profile['guardian_name'] ?? '') ?>"
                               placeholder="Enter guardian's full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-phone-alt"></i> Guardian Phone
                        </label>
                        <input class="form-control form-control-custom" name="guardian_phone" 
                               value="<?= e($profile['guardian_phone'] ?? '') ?>"
                               placeholder="Enter guardian's phone number">
                    </div>
                </div>
            </div>
            
            <!-- Personal Details Card -->
            <div class="info-card col-12" style="animation-delay: 0.3s;">
                <div class="info-card-title">
                    <i class="fas fa-calendar-alt"></i> Personal Details
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-birthday-cake"></i> Date of Birth
                        </label>
                        <input type="date" class="form-control form-control-custom" name="dob" 
                               value="<?= e($profile['dob'] ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">
                            <i class="fas fa-info-circle"></i> Profile Status
                        </label>
                        <div class="info-card-content">
                            <?php if($profile): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-check-circle" style="color: var(--accent-blue);"></i>
                                    <span>Your profile is complete</span>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-exclamation-circle" style="color: var(--accent-pink);"></i>
                                    <span>Please complete your profile information</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="col-12" style="animation-delay: 0.4s;">
                <div class="d-flex flex-wrap gap-3">
                    <button class="btn-submit" type="submit">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a class="btn-back" href="/classms/student/dashboard.php">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Current Profile Preview -->
    <?php if($profile): ?>
    <div class="form-container mt-4" style="animation-delay: 0.6s;">
        <h2 class="form-title">
            <i class="fas fa-eye"></i> Current Profile Preview
        </h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="info-card" style="animation-delay: 0.1s;">
                    <div class="info-card-title">
                        <i class="fas fa-graduation-cap"></i> Grade
                    </div>
                    <div class="info-card-content">
                        <?= e($profile['grade']) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="info-card" style="animation-delay: 0.2s;">
                    <div class="info-card-title">
                        <i class="fas fa-home"></i> Address
                    </div>
                    <div class="info-card-content">
                        <?= e($profile['address'] ?: 'Not provided') ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card" style="animation-delay: 0.3s;">
                    <div class="info-card-title">
                        <i class="fas fa-users"></i> Guardian
                    </div>
                    <div class="info-card-content">
                        <?= e($profile['guardian_name'] ?: 'Not provided') ?>
                        <?php if($profile['guardian_phone']): ?>
                            <br><small class="text-muted"><?= e($profile['guardian_phone']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card" style="animation-delay: 0.4s;">
                    <div class="info-card-title">
                        <i class="fas fa-birthday-cake"></i> Date of Birth
                    </div>
                    <div class="info-card-content">
                        <?= e($profile['dob'] ?: 'Not provided') ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="info-card" style="animation-delay: 0.5s;">
                    <div class="info-card-title">
                        <i class="fas fa-clock"></i> Last Updated
                    </div>
                    <div class="info-card-content">
                        Profile was last updated
                        <?php 
                        $updated_at = $profile['updated_at'] ?? 'recently';
                        echo $updated_at !== 'recently' ? 'on ' . date('F j, Y', strtotime($updated_at)) : 'recently';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
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
    
    // Form input focus animations
    const inputs = document.querySelectorAll('.form-control-custom');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-5px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
    
    // Form submission animation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const button = this.querySelector('button[type="submit"]');
        const originalHTML = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.disabled = true;
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }, 2000);
    });
    
    // Stagger animations for form groups
    const formGroups = document.querySelectorAll('.form-group, .info-card');
    formGroups.forEach((group, index) => {
        group.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Add pulse animation to required fields
    const requiredFields = document.querySelectorAll('input[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
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
});
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>