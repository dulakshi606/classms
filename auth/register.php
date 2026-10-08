<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/functions.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = trim($_POST["role"] ?? "");
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $grade = trim($_POST["grade"] ?? "");

    // Basic validation
    if ($role !== "student" && $role !== "teacher") {

        $msg = "<div class='register-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Invalid role.</span>
                </div>";

    } elseif ($full_name === "" || $email === "" || $password === "") {

        $msg = "<div class='register-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Please fill all required fields.</span>
                </div>";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $msg = "<div class='register-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Please enter a valid email.</span>
                </div>";

    } elseif ($role === "student" && $grade === "") {

        $msg = "<div class='register-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Grade is required for students.</span>
                </div>";

    } elseif (strlen($password) < 6) {

        $msg = "<div class='register-alert'>
                    <span class='alert-icon'>!</span>
                    <span>Password must be at least 6 characters.</span>
                </div>";

    } else {

        // Check duplicate email
        $chk = $conn->prepare(
            "SELECT id FROM users WHERE email=? LIMIT 1"
        );

        $chk->bind_param("s", $email);
        $chk->execute();

        $exists = $chk->get_result()->fetch_assoc();

        if ($exists) {

            $msg = "<div class='register-alert'>
                        <span class='alert-icon'>!</span>
                        <span>This email is already registered. Please login.</span>
                    </div>";

        } else {

            $hash = password_hash(
                $password,
                PASSWORD_BCRYPT
            );

            $conn->begin_transaction();

            try {

                // Insert user
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (role, full_name, email, phone, password_hash)
                    VALUES (?,?,?,?,?)"
                );

                $stmt->bind_param(
                    "sssss",
                    $role,
                    $full_name,
                    $email,
                    $phone,
                    $hash
                );

                $stmt->execute();

                $newUserId = $stmt->insert_id;

                // Student profile
                if ($role === "student") {

                    $p = $conn->prepare(
                        "INSERT INTO student_profiles(user_id, grade)
                         VALUES (?,?)"
                    );

                    $p->bind_param(
                        "is",
                        $newUserId,
                        $grade
                    );

                    $p->execute();
                }

                $conn->commit();

                $msg = "<div class='register-success'>
                            <span class='success-icon'>✓</span>
                            <div>
                                <strong>Registration successful!</strong>
                                <br>
                                <a href='/classms/auth/login.php'>
                                    Login now
                                </a>
                            </div>
                        </div>";

            } catch (Exception $e) {

                $conn->rollback();

                $msg = "<div class='register-alert'>
                            <span class='alert-icon'>!</span>
                            <span>Registration failed. Please try again.</span>
                        </div>";
            }
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
?>

<style>

/* =========================================================
   SAME BLUE & WHITE DESIGN AS LOGIN PAGE
   ========================================================= */

:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --dark: #0f172a;
    --text: #334155;
    --muted: #64748b;
    --border: #e2e8f0;
    --bg: #f8fafc;
    --white: #ffffff;
}


/* PAGE */

body {
    margin: 0;
    min-height: 100vh;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37, 99, 235, 0.08),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 90%,
            rgba(59, 130, 246, 0.08),
            transparent 30%
        ),
        var(--bg);

    font-family:
        "Segoe UI",
        Tahoma,
        Geneva,
        Verdana,
        sans-serif;

    color: var(--text);
}


/* MAIN */

.register-wrapper {
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 35px 15px;
}


/* CARD */

.register-card {
    width: 100%;
    max-width: 600px;

    background: rgba(255, 255, 255, 0.97);

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


/* LOGO */

.register-logo {

    width: 70px;
    height: 70px;

    margin: 0 auto 20px;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    display: flex;
    align-items: center;
    justify-content: center;

    color: white;

    box-shadow:
        0 12px 25px
        rgba(37, 99, 235, 0.25);
}


.register-logo svg {
    width: 36px;
    height: 36px;
}


/* TITLE */

.register-title {

    text-align: center;

    margin-bottom: 8px;

    font-size: 28px;

    font-weight: 700;

    color: var(--dark);

    letter-spacing: -0.5px;
}


.register-subtitle {

    text-align: center;

    color: var(--muted);

    font-size: 14px;

    margin-bottom: 30px;
}


/* ALERT */

.register-alert {

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


.alert-icon {

    width: 22px;
    height: 22px;

    min-width: 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #dc2626;

    color: white;

    font-size: 13px;

    font-weight: 700;
}


/* SUCCESS */

.register-success {

    display: flex;
    align-items: center;

    gap: 12px;

    padding: 14px 15px;

    margin-bottom: 22px;

    border-radius: 12px;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;

    color: #166534;

    font-size: 14px;

    animation: alertShow 0.4s ease;
}


.success-icon {

    width: 26px;
    height: 26px;

    min-width: 26px;

    border-radius: 50%;

    background: #22c55e;

    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
}


.register-success a {

    color: #15803d;

    font-weight: 600;

    text-decoration: none;
}


.register-success a:hover {
    text-decoration: underline;
}


/* FORM GROUP */

.form-group {
    margin-bottom: 20px;
}


/* LABEL */

.form-label-custom {

    display: block;

    margin-bottom: 8px;

    color: #334155;

    font-size: 14px;

    font-weight: 600;
}


/* INPUT WRAPPER */

.input-wrapper {
    position: relative;
}


/* ICON */

.input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;

    transition: 0.25s;
}


.input-icon svg {

    width: 19px;
    height: 19px;
}


/* INPUT */

.register-input {

    width: 100%;

    box-sizing: border-box;

    height: 50px;

    padding: 0 15px 0 48px;

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


.register-input::placeholder {
    color: #94a3b8;
}


.register-input:hover {
    border-color: #cbd5e1;
}


.register-input:focus {

    background: white;

    border-color: var(--primary);

    box-shadow:
        0 0 0 4px
        rgba(37, 99, 235, 0.10);
}


.input-wrapper:focus-within .input-icon {
    color: var(--primary);
}


/* SELECT */

.register-select {

    width: 100%;

    box-sizing: border-box;

    height: 50px;

    padding: 0 42px 0 48px;

    border: 1px solid var(--border);

    border-radius: 13px;

    background: #f8fafc;

    color: var(--dark);

    font-size: 15px;

    outline: none;

    cursor: pointer;

    appearance: none;

    transition: 0.25s;
}


.register-select:focus {

    background: white;

    border-color: var(--primary);

    box-shadow:
        0 0 0 4px
        rgba(37, 99, 235, 0.10);
}


/* SELECT ARROW */

.select-arrow {

    position: absolute;

    right: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;
}


.select-arrow svg {

    width: 18px;
    height: 18px;
}


/* GRADE */

.grade-box {
    transition: 0.3s ease;
}


.grade-box.show {
    animation: gradeShow 0.4s ease;
}


@keyframes gradeShow {

    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* PASSWORD */

.password-input {
    padding-right: 48px;
}


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


/* PASSWORD HINT */

.password-hint {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-top: 7px;

    color: #94a3b8;

    font-size: 12px;
}


.password-hint svg {

    width: 14px;
    height: 14px;
}


/* BUTTON */

.register-button {

    width: 100%;

    height: 52px;

    border: none;

    border-radius: 13px;

    background:
        linear-gradient(
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
        box-shadow 0.2s;
}


.register-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(37, 99, 235, 0.25);
}


.register-button:active {
    transform: translateY(0);
}


.register-button:disabled {

    opacity: 0.75;

    cursor: not-allowed;

    transform: none;

    box-shadow: none;
}


/* BUTTON CONTENT */

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


/* LOGIN */

.login-section {

    margin-top: 28px;

    padding-top: 22px;

    border-top: 1px solid #e2e8f0;

    text-align: center;
}


.login-text {

    color: #64748b;

    font-size: 14px;

    margin: 0;
}


.login-link {

    color: var(--primary);

    font-weight: 600;

    text-decoration: none;

    margin-left: 4px;
}


.login-link:hover {

    color: var(--primary-dark);

    text-decoration: underline;
}


/* SECURITY */

.security-text {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 6px;

    margin-top: 20px;

    color: #94a3b8;

    font-size: 12px;
}


.security-text svg {

    width: 14px;
    height: 14px;
}


/* SPINNER */

.spinner {

    width: 18px;

    height: 18px;

    border: 2px solid
        rgba(255,255,255,0.4);

    border-top-color: white;

    border-radius: 50%;

    animation:
        spin 0.7s linear infinite;
}


@keyframes spin {

    to {
        transform: rotate(360deg);
    }
}


/* RIPPLE */

.ripple {

    position: absolute;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.35);

    transform: scale(0);

    animation:
        rippleAnimation 0.6s linear;

    pointer-events: none;
}


@keyframes rippleAnimation {

    to {

        transform: scale(4);

        opacity: 0;
    }
}


/* MOBILE */

@media (max-width: 576px) {

    .register-wrapper {
        padding: 20px 15px;
    }

    .register-card {

        padding: 30px 22px;

        border-radius: 20px;
    }

    .register-title {
        font-size: 25px;
    }

    .register-logo {

        width: 62px;

        height: 62px;

        border-radius: 17px;
    }

}

</style>


<!-- =========================================================
     REGISTER PAGE
     ========================================================= -->

<div class="register-wrapper">

    <div class="register-card">


        <!-- LOGO -->

        <div class="register-logo">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />

                <circle
                    cx="9"
                    cy="7"
                    r="4" />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19 8v6" />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M22 11h-6" />

            </svg>

        </div>


        <!-- TITLE -->

        <h1 class="register-title">
            Create Account
        </h1>

        <p class="register-subtitle">
            Create your Class Management System account
        </p>


        <!-- MESSAGE -->

        <?= $msg ?>


        <!-- FORM -->

        <form
            method="post"
            id="regForm">


            <!-- ROLE -->

            <div class="form-group">

                <label class="form-label-custom">
                    Account Type
                </label>

                <div class="input-wrapper">

                    <select
                        class="register-select"
                        name="role"
                        id="role"
                        required>

                        <option value="">
                            Select your role
                        </option>

                        <option
                            value="student"
                            <?= (($_POST['role'] ?? '') === 'student')
                                ? 'selected'
                                : '' ?>>

                            Student

                        </option>

                        <option
                            value="teacher"
                            <?= (($_POST['role'] ?? '') === 'teacher')
                                ? 'selected'
                                : '' ?>>

                            Teacher

                        </option>

                    </select>


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
                                d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2" />

                            <circle
                                cx="10"
                                cy="7"
                                r="4" />

                        </svg>

                    </span>


                    <span class="select-arrow">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 9l6 6 6-6" />

                        </svg>

                    </span>

                </div>

            </div>


            <!-- GRADE -->

            <div
                class="form-group grade-box"
                id="gradeBox"
                style="display:none;">

                <label class="form-label-custom">
                    Grade
                </label>

                <div class="input-wrapper">

                    <input
                        class="register-input"
                        name="grade"
                        id="grade"
                        value="<?= e($_POST['grade'] ?? '') ?>"
                        placeholder="Example: 10, 11, 12">

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
                                d="M4 19.5A2.5 2.5 0 016.5 17H20" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8 6h8M8 10h8" />

                        </svg>

                    </span>

                </div>

            </div>


            <!-- FULL NAME -->

            <div class="form-group">

                <label class="form-label-custom">
                    Full Name
                </label>

                <div class="input-wrapper">

                    <input
                        class="register-input"
                        type="text"
                        name="full_name"
                        required
                        autocomplete="name"
                        value="<?= e($_POST['full_name'] ?? '') ?>"
                        placeholder="Enter your full name">

                    <span class="input-icon">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8">

                            <circle
                                cx="12"
                                cy="8"
                                r="4" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 21a8 8 0 0116 0" />

                        </svg>

                    </span>

                </div>

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label class="form-label-custom">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <input
                        class="register-input"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        placeholder="Enter your email">

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


            <!-- PHONE -->

            <div class="form-group">

                <label class="form-label-custom">
                    Phone Number
                    <span
                        style="
                            color:#94a3b8;
                            font-weight:400;
                        ">
                        (Optional)
                    </span>
                </label>

                <div class="input-wrapper">

                    <input
                        class="register-input"
                        type="tel"
                        name="phone"
                        autocomplete="tel"
                        value="<?= e($_POST['phone'] ?? '') ?>"
                        placeholder="Enter your phone number">

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
                                d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2C10.268 21 3 13.732 3 5a2 2 0 012-2z" />

                        </svg>

                    </span>

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label class="form-label-custom">
                    Password
                </label>

                <div class="input-wrapper">

                    <input
                        class="register-input password-input"
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password">


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


                    <!-- SHOW PASSWORD -->

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        aria-label="Show password">

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


                <div class="password-hint">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">

                        <circle
                            cx="12"
                            cy="12"
                            r="9" />

                        <path
                            stroke-linecap="round"
                            d="M12 11v5" />

                        <circle
                            cx="12"
                            cy="8"
                            r=".5"
                            fill="currentColor" />

                    </svg>

                    Minimum 6 characters

                </div>

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="register-button"
                id="registerButton">

                <span
                    class="button-content"
                    id="buttonContent">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />

                        <circle
                            cx="9"
                            cy="7"
                            r="4" />

                        <path
                            stroke-linecap="round"
                            d="M19 8v6M22 11h-6" />

                    </svg>

                    Create Account

                </span>

            </button>

        </form>


        <!-- LOGIN LINK -->

        <div class="login-section">

            <p class="login-text">

                Already have an account?

                <a
                    href="/classms/auth/login.php"
                    class="login-link">

                    Login here

                </a>

            </p>

        </div>


        <!-- SECURITY -->

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
   ROLE / GRADE
   ========================================================= */

const role =
    document.getElementById("role");

const gradeBox =
    document.getElementById("gradeBox");

const grade =
    document.getElementById("grade");


function toggleGrade() {

    if (role.value === "student") {

        gradeBox.style.display = "block";

        grade.required = true;

        gradeBox.classList.remove("show");

        void gradeBox.offsetWidth;

        gradeBox.classList.add("show");

    } else {

        gradeBox.style.display = "none";

        grade.required = false;

        grade.value = "";
    }
}


role.addEventListener(
    "change",
    toggleGrade
);


toggleGrade();


/* =========================================================
   PASSWORD SHOW / HIDE
   ========================================================= */

const passwordInput =
    document.getElementById("password");

const passwordToggle =
    document.getElementById("passwordToggle");

const eyeIcon =
    document.getElementById("eyeIcon");


passwordToggle.addEventListener(
    "click",
    function () {

        if (
            passwordInput.type === "password"
        ) {

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

            passwordInput.type =
                "password";

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

    }
);


/* =========================================================
   FORM SUBMIT
   ========================================================= */

const regForm =
    document.getElementById("regForm");

const registerButton =
    document.getElementById("registerButton");

const buttonContent =
    document.getElementById("buttonContent");


regForm.addEventListener(
    "submit",
    function () {

        registerButton.disabled = true;

        buttonContent.innerHTML = `

            <span class="spinner"></span>

            Creating Account...

        `;

    }
);


/* =========================================================
   RIPPLE EFFECT
   ========================================================= */

registerButton.addEventListener(
    "click",
    function (event) {

        const rect =
            registerButton.getBoundingClientRect();

        const size = 100;

        const x =
            event.clientX -
            rect.left -
            size / 2;

        const y =
            event.clientY -
            rect.top -
            size / 2;


        const ripple =
            document.createElement("span");

        ripple.className = "ripple";

        ripple.style.width =
            size + "px";

        ripple.style.height =
            size + "px";

        ripple.style.left =
            x + "px";

        ripple.style.top =
            y + "px";


        registerButton.appendChild(
            ripple
        );


        setTimeout(
            function () {
                ripple.remove();
            },
            600
        );

    }
);


/* =========================================================
   AUTO FOCUS
   ========================================================= */

const firstInput =
    document.querySelector(
        'input[name="full_name"]'
    );


if (firstInput) {
    firstInput.focus();
}

</script>


<?php
require_once __DIR__ . "/../includes/footer.php";
?>