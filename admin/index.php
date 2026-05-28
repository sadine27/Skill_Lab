<?php
require_once __DIR__ . '/../auth_check.php';
require_role('admin');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reportId = (int)($_POST['report_id'] ?? 0);
  $collectorId = (int)($_POST['collector_id'] ?? 0);

  if ($reportId > 0 && $collectorId > 0) {
    $stmt = $pdo->prepare("UPDATE waste_reports SET assigned_to = ?, status = 'in_progress' WHERE id = ?");
    $stmt->execute([$collectorId, $reportId]);
  }
  header("Location: index.php");
  exit();
}

$collectors = $pdo->query("SELECT id, name, email FROM users WHERE role='collector'")->fetchAll(PDO::FETCH_ASSOC);

$reports = $pdo->query("
  SELECT wr.*, u.name AS citizen_name, wc.name AS category_name, c.name AS collector_name
  FROM waste_reports wr
  JOIN users u ON u.id = wr.citizen_id
  JOIN waste_categories wc ON wc.id = wr.category_id
  LEFT JOIN users c ON c.id = wr.assigned_to
  ORDER BY wr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Admin Dashboard</title></head>
<body>
<h2>Admin Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> |
  <a href="../auth/logout.php">Logout</a>
</p>

<h3>All Reports</h3>
<table border="1" cellpadding="8" cellspacing="0">
<tr>
  <th>ID</th><th>Citizen</th><th>Category</th><th>Description</th><th>Location</th>
  <th>Status</th><th>Assigned To</th><th>Assign Collector</th>
</tr>

<?php foreach ($reports as $r): ?>
<tr>
  <td><?php echo (int)$r['id']; ?></td>
  <td><?php echo htmlspecialchars($r['citizen_name']); ?></td>
  <td><?php echo htmlspecialchars($r['category_name']); ?></td>
  <td><?php echo htmlspecialchars($r['description']); ?></td>
  <td><?php echo htmlspecialchars($r['location_text']); ?></td>
  <td><?php echo htmlspecialchars($r['status']); ?></td>
  <td><?php echo htmlspecialchars($r['collector_name'] ?? 'Not assigned'); ?></td>
  <td>
    <form method="POST" style="margin:0;">
      <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
      <select name="collector_id" required>
        <option value="">--select--</option>
        <?php foreach ($collectors as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>">
            <?php echo htmlspecialchars($c['name'] . " (" . $c['email'] . ")"); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Assign</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>