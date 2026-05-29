<?php
require_once __DIR__ . '/../auth_check.php';
require_role('admin');
require_once __DIR__ . '/../db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="waste_reports.csv"');

$out = fopen('php://output', 'w');

// CSV header
fputcsv($out, [
  'id', 'citizen', 'category', 'description', 'location', 'status',
  'assigned_to', 'created_at', 'updated_at'
]);

$stmt = $pdo->query("
  SELECT wr.id,
         u.name AS citizen_name,
         wc.name AS category_name,
         wr.description,
         wr.location_text,
         wr.status,
         c.name AS collector_name,
         wr.created_at,
         wr.updated_at
  FROM waste_reports wr
  JOIN users u ON u.id = wr.citizen_id
  JOIN waste_categories wc ON wc.id = wr.category_id
  LEFT JOIN users c ON c.id = wr.assigned_to
  ORDER BY wr.created_at DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    $row['id'],
    $row['citizen_name'],
    $row['category_name'],
    $row['description'],
    $row['location_text'],
    $row['status'],
    $row['collector_name'] ?? '',
    $row['created_at'],
    $row['updated_at'],
  ]);
}

fclose($out);
exit;