<?php

require_once __DIR__ . '/../includes/report_helpers.php';

function createTestPdo(): PDO
{
  $pdo = new PDO('sqlite::memory:');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $pdo->exec("
    CREATE TABLE waste_categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      description TEXT
    );

    CREATE TABLE waste_reports (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      citizen_id INTEGER NOT NULL,
      category_id INTEGER NOT NULL,
      description TEXT NOT NULL,
      location_text TEXT NOT NULL,
      photo_path TEXT,
      status TEXT NOT NULL,
      assigned_to INTEGER,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE collection_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      report_id INTEGER NOT NULL,
      collector_id INTEGER NOT NULL,
      action TEXT NOT NULL,
      notes TEXT,
      logged_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
  ");

  return $pdo;
}

function seedTestData(PDO $pdo): array
{
  $pdo->exec("INSERT INTO waste_categories (name, description) VALUES ('Plastic', 'Plastic waste')");

  $pdo->exec("
    INSERT INTO waste_reports (citizen_id, category_id, description, location_text, photo_path, status, assigned_to)
    VALUES
      (1, 1, 'Citizen one report', 'Main street', 'uploads/report_a.jpg', 'pending', 10),
      (2, 1, 'Citizen two report', 'Side street', 'uploads/report_b.jpg', 'pending', 11),
      (1, 1, 'Collected report', 'Park lane', NULL, 'in_progress', 10)
  ");

  return [
    'citizen_one_report' => 1,
    'citizen_two_report' => 2,
    'assigned_report' => 3,
  ];
}

function fail(string $message): void
{
  throw new RuntimeException($message);
}

function assertTrue(bool $condition, string $message): void
{
  if (!$condition) {
    fail($message);
  }
}

function assertSame($expected, $actual, string $message): void
{
  if ($expected !== $actual) {
    fail($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
  }
}
