<?php
require_once __DIR__ . "/../includes/auth.php";

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password_hash'])) {

        $_SESSION['user'] = [
            "id"        => $u["id"],
            "role"      => $u["role"],
            "full_name" => $u["full_name"],
            "email"     => $u["email"]
        ];

        // Login success animation
        $_SESSION['login_animation'] = true;

        if ($u['role'] === 'student') {
            redirect("/classms/student/dashboard.php");
        }

        redirect("/classms/teacher/dashboard.php");

    } else {

        $msg = "<div class='login-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Invalid email or password.</span>
                </div>";
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<style>

/* =========================================================
   CLASS MANAGEMENT SYSTEM - LOGIN UI
   ========================================================= */

:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #eff6ff;

    --dark: #0f172a;
    --text: #334155;
    --muted: #64748b;

    --border: #e2e8f0;
    --white: #ffffff;

    --bg: #f8fafc;
}


/* ---------- PAGE ---------- */

body {
    margin: 0;
    min-height: 100vh;
    background:
        radial-gradient(circle at 10% 10%, rgba(37, 99, 235, 0.08), transparent 30%),
        radial-gradient(circle at 90% 90%, rgba(59, 130, 246, 0.08), transparent 30%),
        var(--bg);

    font-family:
        "Segoe UI",
        Tahoma,
        Geneva,
        Verdana,
        sans-serif;

    color: var(--text);
}


/* ---------- MAIN WRAPPER ---------- */

.login-wrapper {
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 30px 15px;
}


/* ---------- LOGIN CARD ---------- */

.login-card {
    width: 100%;
    max-width: 440px;

    background: rgba(255, 255, 255, 0.96);

    border: 1px solid rgba(226, 232, 240, 0.9);

    border-radius: 24px;

    padding: 40px;

    box-shadow:
        0 20px 50px rgba(15, 23, 42, 0.08),
        0 5px 15px rgba(15, 23, 42, 0.04);

    animation: cardShow 0.6s ease;
}


@keyframes cardShow {

    from {
        opacity: 0;
        transform: translateY(25px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }

}


/* ---------- LOGO ---------- */

.login-logo {
    width: 70px;
    height: 70px;

    margin: 0 auto 20px;

    border-radius: 20px;

    background: linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    display: flex;
    align-items: center;
    justify-content: center;

    color: white;

    box-shadow:
        0 12px 25px rgba(37, 99, 235, 0.25);
}


.login-logo svg {
    width: 36px;
    height: 36px;
}


/* ---------- TITLE ---------- */

.login-title {
    text-align: center;

    margin-bottom: 8px;

    font-size: 28px;

    font-weight: 700;

    color: var(--dark);

    letter-spacing: -0.5px;
}


.login-subtitle {
    text-align: center;

    color: var(--muted);

    font-size: 14px;

    margin-bottom: 30px;
}


/* ---------- ALERT ---------- */

.login-alert {

    display: flex;
    align-items: center;

    gap: 10px;

    padding: 13px 15px;

    margin-bottom: 22px;

    border-radius: 12px;

    background: #fef2f2;

    border: 1px solid #fecaca;

    color: #b91c1c;

    font-size: 14px;

    animation: alertShow 0.4s ease;
}


.alert-icon {

    width: 22px;
    height: 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #dc2626;

    color: white;

    font-size: 13px;
    font-weight: 700;
}


@keyframes alertShow {

    from {
        opacity: 0;
        transform: translateY(-8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }

}


/* ---------- FORM GROUP ---------- */

.form-group {
    margin-bottom: 22px;
}


/* ---------- LABEL ---------- */

.form-label-custom {

    display: block;

    margin-bottom: 8px;

    color: #334155;

    font-size: 14px;

    font-weight: 600;
}


/* ---------- INPUT WRAPPER ---------- */

.input-wrapper {

    position: relative;
}


/* ---------- INPUT ICON ---------- */

.input-icon {

    position: absolute;

    left: 15px;
    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;

    transition: 0.3s;
}


.input-icon svg {

    width: 19px;
    height: 19px;
}


/* ---------- INPUT ---------- */

.login-input {

    width: 100%;

    box-sizing: border-box;

    height: 52px;

    padding: 0 48px;

    border: 1px solid var(--border);

    border-radius: 13px;

    background: #f8fafc;

    color: var(--dark);

    font-size: 15px;

    outline: none;

    transition:
        border-color 0.25s,
        box-shadow 0.25s,
        background 0.25s;
}


.login-input::placeholder {
    color: #94a3b8;
}


.login-input:hover {
    border-color: #cbd5e1;
}


.login-input:focus {

    background: white;

    border-color: var(--primary);

    box-shadow:
        0 0 0 4px rgba(37, 99, 235, 0.10);
}


.login-input:focus + .input-icon {
    color: var(--primary);
}


/* ---------- PASSWORD BUTTON ---------- */

.password-toggle {

    position: absolute;

    right: 14px;
    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    color: #94a3b8;

    cursor: pointer;

    padding: 5px;

    display: flex;
    align-items: center;
    justify-content: center;
}


.password-toggle:hover {
    color: var(--primary);
}


.password-toggle svg {
    width: 19px;
    height: 19px;
}


/* ---------- LOGIN BUTTON ---------- */

.login-button {

    width: 100%;

    height: 52px;

    border: none;

    border-radius: 13px;

    background: linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    color: white;

    font-size: 15px;

    font-weight: 600;

    letter-spacing: 0.2px;

    cursor: pointer;

    position: relative;

    overflow: hidden;

    transition:
        transform 0.2s,
        box-shadow 0.2s,
        background 0.2s;
}


.login-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px rgba(37, 99, 235, 0.25);

    background: linear-gradient(
        135deg,
        #1d4ed8,
        #1e40af
    );
}


.login-button:active {
    transform: translateY(0);
}


.login-button:disabled {

    cursor: not-allowed;

    opacity: 0.75;

    transform: none;

    box-shadow: none;
}


/* ---------- BUTTON CONTENT ---------- */

.button-content {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;
}


.button-content svg {

    width: 19px;
    height: 19px;
}


/* ---------- REGISTER SECTION ---------- */

.register-section {

    margin-top: 28px;

    padding-top: 22px;

    border-top: 1px solid #e2e8f0;

    text-align: center;
}


.register-text {

    color: #64748b;

    font-size: 14px;

    margin: 0;
}


.register-link {

    color: var(--primary);

    font-weight: 600;

    text-decoration: none;

    margin-left: 4px;

    transition: color 0.2s;
}


.register-link:hover {

    color: var(--primary-dark);

    text-decoration: underline;
}


/* ---------- SECURITY TEXT ---------- */

.security-text {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 6px;

    margin-top: 22px;

    color: #94a3b8;

    font-size: 12px;
}


.security-text svg {
    width: 14px;
    height: 14px;
}


/* ---------- SUCCESS SCREEN ---------- */

.login-success {

    position: fixed;

    inset: 0;

    z-index: 9999;

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(15, 23, 42, 0.55);

    backdrop-filter: blur(6px);

    animation: successBackground 1.8s forwards;
}


.success-box {

    width: 130px;

    height: 130px;

    background: white;

    border-radius: 25px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    box-shadow:
        0 25px 60px rgba(15, 23, 42, 0.25);

    animation: successBox 0.5s ease;
}


.success-circle {

    width: 55px;

    height: 55px;

    border-radius: 50%;

    background: #22c55e;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 30px;

    font-weight: bold;

    animation: checkAnimation 0.5s ease;
}


.success-text {

    margin-top: 10px;

    color: #334155;

    font-size: 13px;

    font-weight: 600;
}


@keyframes successBox {

    from {
        opacity: 0;
        transform: scale(0.8);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }

}


@keyframes checkAnimation {

    from {
        transform: scale(0);
    }

    to {
        transform: scale(1);
    }

}


@keyframes successBackground {

    0% {
        opacity: 1;
        visibility: visible;
    }

    75% {
        opacity: 1;
        visibility: visible;
    }

    100% {
        opacity: 0;
        visibility: hidden;
    }

}


/* ---------- SPINNER ---------- */

.spinner {

    width: 18px;
    height: 18px;

    border: 2px solid rgba(255,255,255,0.4);

    border-top-color: white;

    border-radius: 50%;

    animation: spin 0.7s linear infinite;
}


@keyframes spin {

    to {
        transform: rotate(360deg);
    }

}


/* ---------- MOBILE ---------- */

@media (max-width: 576px) {

    .login-wrapper {
        padding: 20px 15px;
    }

    .login-card {

        padding: 30px 22px;

        border-radius: 20px;
    }

    .login-title {
        font-size: 25px;
    }

    .login-logo {

        width: 62px;
        height: 62px;

        border-radius: 17px;
    }

}


/* ---------- RIPPLE ---------- */

.ripple {

    position: absolute;

    border-radius: 50%;

    background: rgba(255,255,255,0.35);

    transform: scale(0);

    animation: rippleAnimation 0.6s linear;

    pointer-events: none;
}


@keyframes rippleAnimation {

    to {

        transform: scale(4);

        opacity: 0;
    }

}

</style>


<!-- =========================================================
     LOGIN SUCCESS ANIMATION
     ========================================================= -->

<?php if (isset($_SESSION['login_animation']) && $_SESSION['login_animation']): ?>

<div class="login-success" id="loginSuccess">

    <div class="success-box">

        <div class="success-circle">
            ✓
        </div>

        <div class="success-text">
            Login Successful
        </div>

    </div>

</div>

<?php
    unset($_SESSION['login_animation']);
endif;
?>


<!-- =========================================================
     LOGIN PAGE
     ========================================================= -->

<div class="login-wrapper">

    <div class="login-card">


        <!-- Logo -->

        <div class="login-logo">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 19.5A2.5 2.5 0 016.5 17H20" />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8 6h8M8 10h8M8 14h5" />

            </svg>

        </div>


        <!-- Heading -->

        <h1 class="login-title">
            Welcome Back
        </h1>

        <p class="login-subtitle">
            Sign in to access your class management account
        </p>


        <!-- Error Message -->

        <?= $msg ?>


        <!-- Login Form -->

        <form method="post" id="loginForm">


            <!-- Email -->

            <div class="form-group">

                <label class="form-label-custom">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <input
                        class="login-input"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="Enter your email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                    >

                    <span class="input-icon">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 8l9 6 9-6" />

                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2" />

                        </svg>

                    </span>

                </div>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label class="form-label-custom">
                    Password
                </label>

                <div class="input-wrapper">

                    <input
                        class="login-input"
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >

                    <span class="input-icon">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <rect
                                x="4"
                                y="10"
                                width="16"
                                height="11"
                                rx="2" />

                            <path
                                stroke-linecap="round"
                                d="M8 10V7a4 4 0 018 0v3" />

                        </svg>

                    </span>


                    <!-- Show Password -->

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        aria-label="Show password"
                    >

                        <svg
                            id="eyeIcon"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />

                            <circle
                                cx="12"
                                cy="12"
                                r="2.5" />

                        </svg>

                    </button>

                </div>

            </div>


            <!-- Login Button -->

            <button
                type="submit"
                class="login-button"
                id="loginButton"
            >

                <span class="button-content" id="buttonContent">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10 17l5-5-5-5" />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12H3" />

                    </svg>

                    Sign In

                </span>

            </button>

        </form>


        <!-- Register -->

        <div class="register-section">

            <p class="register-text">

                Don't have an account?

                <a
                    href="/classms/auth/register.php"
                    class="register-link">

                    Register here

                </a>

            </p>

        </div>


        <!-- Security -->

        <div class="security-text">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z" />

            </svg>

            Secure Class Management System

        </div>

    </div>

</div>


<script>

/* =========================================================
   PASSWORD SHOW / HIDE
   ========================================================= */

const passwordInput =
    document.getElementById("password");

const passwordToggle =
    document.getElementById("passwordToggle");

const eyeIcon =
    document.getElementById("eyeIcon");


passwordToggle.addEventListener("click", function () {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";

        passwordToggle.setAttribute(
            "aria-label",
            "Hide password"
        );

        eyeIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 3l18 18" />

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10.6 10.6a2 2 0 002.8 2.8" />

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9.9 5.1A9.5 9.5 0 0112 5c6 0 9.5 7 9.5 7a17 17 0 01-3.2 4.2" />

            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6.1 6.1C3.9 7.5 2.5 12 2.5 12s3.5 6 9.5 6c1.3 0 2.5-.3 3.6-.8" />
        `;

    } else {

        passwordInput.type = "password";

        passwordToggle.setAttribute(
            "aria-label",
            "Show password"
        );

        eyeIcon.innerHTML = `
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z" />

            <circle
                cx="12"
                cy="12"
                r="2.5" />
        `;
    }

});


/* =========================================================
   FORM SUBMIT LOADING
   ========================================================= */

const loginForm =
    document.getElementById("loginForm");

const loginButton =
    document.getElementById("loginButton");

const buttonContent =
    document.getElementById("buttonContent");


loginForm.addEventListener("submit", function () {

    loginButton.disabled = true;

    buttonContent.innerHTML = `
        <span class="spinner"></span>
        Signing In...
    `;

});


/* =========================================================
   BUTTON RIPPLE EFFECT
   ========================================================= */

loginButton.addEventListener("click", function (event) {

    const rect =
        loginButton.getBoundingClientRect();

    const size = 100;

    const x =
        event.clientX - rect.left - size / 2;

    const y =
        event.clientY - rect.top - size / 2;


    const ripple =
        document.createElement("span");

    ripple.className = "ripple";

    ripple.style.width = size + "px";
    ripple.style.height = size + "px";
    ripple.style.left = x + "px";
    ripple.style.top = y + "px";


    loginButton.appendChild(ripple);


    setTimeout(function () {

        ripple.remove();

    }, 600);

});


/* =========================================================
   AUTO FOCUS
   ========================================================= */

const emailInput =
    document.querySelector('input[name="email"]');

if (emailInput) {

    emailInput.focus();

}


/* =========================================================
   REMOVE SUCCESS SCREEN
   ========================================================= */

const loginSuccess =
    document.getElementById("loginSuccess");

if (loginSuccess) {

    setTimeout(function () {

        loginSuccess.style.display = "none";

    }, 1800);

}

</script>


<?php
require_once __DIR__ . "/../includes/footer.php";
?>