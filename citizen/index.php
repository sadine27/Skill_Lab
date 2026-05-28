<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT wr.id, wc.name AS category, wr.description, wr.location_text, wr.status, wr.created_at
  FROM waste_reports wr
  JOIN waste_categories wc ON wc.id = wr.category_id
  WHERE wr.citizen_id = ?
  ORDER BY wr.created_at DESC
");
$stmt->execute([$userId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Citizen Dashboard</title>
</head>
<body>
  <h2>Citizen Dashboard</h2>
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> |
    <a href="../auth/logout.php">Logout</a>
  </p>

  <p><a href="submit.php">Submit New Waste Report</a></p>

  <h3>Your Reports</h3>

  <?php if (!$reports): ?>
    <p>No reports yet.</p>
  <?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0">
      <tr>
        <th>ID</th>
        <th>Category</th>
        <th>Description</th>
        <th>Location</th>
        <th>Status</th>
        <th>Created</th>
      </tr>
      <?php foreach ($reports as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['category']); ?></td>
          <td><?php echo htmlspecialchars($r['description']); ?></td>
          <td><?php echo htmlspecialchars($r['location_text']); ?></td>
          <td><?php echo htmlspecialchars($r['status']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>