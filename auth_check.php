<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: /waste_system/auth/login.php");
  exit();
}

function require_role(string $role): void {
  if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
    http_response_code(403);
    die("403 Forbidden - Access denied");
  }
}