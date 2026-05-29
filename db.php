<?php
// Default local (XAMPP) MySQL config. Override via env vars for testing/CI.
$host = 'localhost';
$db   = 'waste_system';
$user = 'root';
$pass = '';

// WS_DB_DSN lets a test/CI harness point the app at another database
// (e.g. an ephemeral SQLite file) without touching the production config.
$dsn    = getenv('WS_DB_DSN') ?: "mysql:host=$host;dbname=$db;charset=utf8mb4";
$dbUser = getenv('WS_DB_USER');
$dbPass = getenv('WS_DB_PASS');
$dbUser = ($dbUser === false) ? $user : $dbUser;
$dbPass = ($dbPass === false) ? $pass : $dbPass;

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("DB connection failed: " . $e->getMessage());
}