<?php
session_start();

// All protected pages live in subfolders (citizen/, collector/, admin/),
// so a relative redirect works regardless of where the app is deployed.
if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

function require_role(string $role): void {
  if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
    http_response_code(403);
    die("403 Forbidden - Access denied");
  }
}