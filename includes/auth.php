<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/functions.php";

function require_login(){
  if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: /classms/auth/login.php");
    exit();
  }
}

function require_role($role){
  require_login();
  if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== $role) {
    header("Location: /classms/auth/login.php");
    exit();
  }
}

function current_user(){
  return $_SESSION['user'] ?? null;
}
