<?php
// Builds an ephemeral SQLite database for the test suite.
// Usage: php seed_sqlite.php /path/to/test.db
// Mirrors schema.sql (MySQL) in SQLite dialect + seeds known accounts.
//
// Seeded users (password 'pass123' for all):
//   id 1  citizen@test.local    citizen
//   id 2  collector@test.local  collector
//   id 3  collector2@test.local collector   (for horizontal-access tests)
//   id 4  admin@test.local      admin

$path = $argv[1] ?? null;
if (!$path) {
    fwrite(STDERR, "usage: php seed_sqlite.php <db-path>\n");
    exit(1);
}
@unlink($path);

$pdo = new PDO("sqlite:$path");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'citizen' CHECK (role IN ('citizen','collector','admin')),
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE waste_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  description TEXT
);
CREATE TABLE waste_reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  citizen_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  description TEXT NOT NULL,
  location_text TEXT NOT NULL,
  photo_path TEXT,
  status TEXT NOT NULL DEFAULT 'pending'
    CHECK (status IN ('pending','in_progress','collected','rejected')),
  assigned_to INTEGER,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE collection_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  report_id INTEGER NOT NULL,
  collector_id INTEGER NOT NULL,
  action TEXT NOT NULL,
  notes TEXT,
  logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
");

$hash = fn($p) => password_hash($p, PASSWORD_DEFAULT);

$users = [
    ['Cathy Citizen',    'citizen@test.local',    'citizen'],
    ['Carl Collector',   'collector@test.local',  'collector'],
    ['Cora Collector',   'collector2@test.local', 'collector'],
    ['Adam Admin',       'admin@test.local',       'admin'],
];
$ins = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
foreach ($users as $u) {
    $ins->execute([$u[0], $u[1], $hash('pass123'), $u[2]]);
}

$cats = ['Household', 'Recyclable', 'Organic', 'Electronic', 'Construction', 'Hazardous'];
$cins = $pdo->prepare("INSERT INTO waste_categories (name) VALUES (?)");
foreach ($cats as $c) {
    $cins->execute([$c]);
}

echo "seeded: $path\n";
