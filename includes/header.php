<?php require_once __DIR__ . "/auth.php"; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ClassMS - School Management System</title>
  <link rel="stylesheet" href="/classms/assets/css/style.css">
  <script defer src="/classms/assets/js/app.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
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
    }

    body {
        background: linear-gradient(135deg, #0a192f 0%, #112240 50%, #233554 100%);
        color: var(--text-white);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
    }

    /* Animated Background */
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

    /* Navigation Bar */
    .navbar-custom {
        background: linear-gradient(135deg, var(--medium-blue), var(--light-blue)) !important;
        border-bottom: 2px solid rgba(100, 255, 218, 0.2);
        box-shadow: 0 4px 20px rgba(2, 12, 27, 0.5);
        padding: 0.8rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        animation: nav-slide-down 0.5s ease-out;
    }

    @keyframes nav-slide-down {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .navbar-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent-blue), var(--accent-pink), var(--accent-gold));
        animation: header-line 2s ease-out;
    }

    @keyframes header-line {
        from { transform: translateX(-100%); }
        to { transform: translateX(0); }
    }

    .navbar-brand-custom {
        font-size: 1.8rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--accent-blue), var(--text-white));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .navbar-brand-custom:hover {
        transform: translateY(-2px);
    }

    .nav-link-custom {
        color: var(--text-muted) !important;
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        margin: 0 0.2rem;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .nav-link-custom::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(100, 255, 218, 0.1), transparent);
        transition: left 0.7s;
    }

    .nav-link-custom:hover::before {
        left: 100%;
    }

    .nav-link-custom:hover {
        color: var(--text-white) !important;
        background: rgba(100, 255, 218, 0.1);
        transform: translateY(-2px);
    }

    .nav-link-custom.active {
        color: var(--accent-blue) !important;
        background: rgba(100, 255, 218, 0.15);
        border-left: 3px solid var(--accent-blue);
    }

    .user-info {
        background: rgba(100, 255, 218, 0.1);
        border-radius: 10px;
        padding: 8px 15px;
        border: 1px solid rgba(100, 255, 218, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-right: 15px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: bold;
        color: var(--dark-blue);
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-white);
    }

    .user-role {
        font-size: 0.75rem;
        color: var(--accent-blue);
        text-transform: capitalize;
    }

    .btn-outline-light-custom {
        background: transparent;
        color: var(--accent-blue);
        border: 2px solid var(--accent-blue);
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-outline-light-custom::before {
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

    .btn-outline-light-custom:hover::before {
        left: 100%;
    }

    .btn-outline-light-custom:hover {
        background: rgba(100, 255, 218, 0.1);
        color: var(--text-white);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
    }

    .btn-light-custom {
        background: linear-gradient(135deg, var(--accent-blue), #52d3b8);
        color: var(--dark-blue);
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-light-custom::before {
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

    .btn-light-custom:hover::before {
        left: 100%;
    }

    .btn-light-custom:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 20px rgba(100, 255, 218, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar-custom {
            padding: 0.5rem 0;
        }
        
        .navbar-brand-custom {
            font-size: 1.5rem;
        }
        
        .user-info {
            margin-right: 0;
            margin-bottom: 10px;
        }
        
        .nav-link-custom {
            margin: 0.2rem 0;
            padding: 0.5rem !important;
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

    /* Main content area */
    .main-content {
        min-height: calc(100vh - 76px);
        padding: 30px 0;
    }

    /* Role-based navigation */
    .role-nav {
        background: rgba(100, 255, 218, 0.05);
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid rgba(100, 255, 218, 0.1);
    }

    .role-nav .nav-link-custom {
        margin: 0.3rem;
    }

    /* Dropdown menu styling */
    .dropdown-menu-custom {
        background: var(--medium-blue) !important;
        border: 1px solid rgba(100, 255, 218, 0.2) !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 30px rgba(2, 12, 27, 0.5) !important;
        animation: dropdown-appear 0.3s ease-out;
    }

    @keyframes dropdown-appear {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-item-custom {
        color: var(--text-muted) !important;
        padding: 10px 15px !important;
        transition: all 0.2s ease;
    }

    .dropdown-item-custom:hover {
        background: rgba(100, 255, 218, 0.1) !important;
        color: var(--text-white) !important;
        transform: translateX(5px);
    }

    .dropdown-divider-custom {
        border-color: rgba(100, 255, 218, 0.1) !important;
        margin: 8px 0 !important;
    }
  </style>
</head>
<body>
  <!-- Animated Background -->
  <div class="bg-animation"></div>

  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <!-- Brand -->
      <a class="navbar-brand navbar-brand-custom" href="/classms/index.php">
        <i class="fas fa-graduation-cap"></i> ClassMS
      </a>

      <!-- Mobile Toggle -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
      </button>

      <!-- Navigation Content -->
      <div class="collapse navbar-collapse" id="navbarContent">
        <!-- Role-based Navigation -->
        <?php if(current_user()): ?>
          <?php $role = current_user()['role']; ?>
          <ul class="navbar-nav me-auto">
            <?php if($role === 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="/classms/admin/dashboard.php">
                  <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'teachers.php' ? 'active' : '' ?>" 
                   href="/classms/admin/teachers.php">
                  <i class="fas fa-chalkboard-teacher"></i> Teachers
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>" 
                   href="/classms/admin/students.php">
                  <i class="fas fa-user-graduate"></i> Students
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>" 
                   href="/classms/admin/subjects.php">
                  <i class="fas fa-book"></i> Subjects
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" 
                   href="/classms/admin/settings.php">
                  <i class="fas fa-cog"></i> Settings
                </a>
              </li>
              
            <?php elseif($role === 'teacher'): ?>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="/classms/teacher/dashboard.php">
                  <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : '' ?>" 
                   href="/classms/teacher/students.php">
                  <i class="fas fa-user-graduate"></i> Students
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>" 
                   href="/classms/teacher/subjects.php">
                  <i class="fas fa-book"></i> Subjects
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" 
                   href="/classms/teacher/reports.php">
                  <i class="fas fa-chart-bar"></i> Reports
                </a>
              </li>
              
            <?php elseif($role === 'student'): ?>
              
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" 
                   href="/classms/student/profile.php">
                  <i class="fas fa-user"></i> Profile
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'marks.php' ? 'active' : '' ?>" 
                   href="/classms/student/marks.php">
                  <i class="fas fa-chart-line"></i> Marks
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link nav-link-custom <?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>" 
                   href="/classms/student/chat.php">
                  <i class="fas fa-comments"></i> Messages
                </a>
              </li>
            <?php endif; ?>
          </ul>
        <?php endif; ?>

        <!-- User Info & Auth Buttons -->
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3">
          <?php if(current_user()): ?>
            <div class="user-info">
              <div class="user-avatar">
                <?= strtoupper(substr(current_user()['full_name'], 0, 1)) ?>
              </div>
              <div class="user-details">
                <span class="user-name"><?= e(current_user()['full_name']) ?></span>
                <span class="user-role"><?= e(current_user()['role']) ?></span>
              </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="dropdown">
              <button class="btn btn-outline-light-custom dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i> Account
              </button>
              <ul class="dropdown-menu dropdown-menu-custom">
                <li>
                 
                </li>
                <li>
                  
                </li>
                <li><hr class="dropdown-divider dropdown-divider-custom"></li>
                <li>
                  <a class="dropdown-item dropdown-item-custom" href="/classms/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                  </a>
                </li>
              </ul>
            </div>
          <?php else: ?>
            <div class="d-flex flex-column flex-md-row gap-2">
              <a class="btn-outline-light-custom" href="/classms/auth/login.php">
                <i class="fas fa-sign-in-alt"></i> Login
              </a>
              <a class="btn-light-custom" href="/classms/auth/register.php">
                <i class="fas fa-user-plus"></i> Register
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container">