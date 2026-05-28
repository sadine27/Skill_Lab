<?php
require_once __DIR__ . '/../auth_check.php';
require_role('collector');
require_once __DIR__ . '/../db.php';

$collectorId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reportId = (int)($_POST['report_id'] ?? 0);
  $newStatus = $_POST['status'] ?? '';

  if ($reportId > 0 && in_array($newStatus, ['in_progress','collected','rejected'], true)) {
    $stmt = $pdo->prepare("UPDATE waste_reports SET status = ? WHERE id = ? AND assigned_to = ?");
    $stmt->execute([$newStatus, $reportId, $collectorId]);

    $log = $pdo->prepare("INSERT INTO collection_logs (report_id, collector_id, action, notes) VALUES (?,?,?,?)");
    $log->execute([$reportId, $collectorId, "status_changed", "Changed status to $newStatus"]);
  }

  header("Location: index.php");
  exit();
}

$stmt = $pdo->prepare("
  SELECT wr.id, wc.name AS category, wr.description, wr.location_text, wr.status, wr.created_at
  FROM waste_reports wr
  JOIN waste_categories wc ON wc.id = wr.category_id
  WHERE wr.assigned_to = ?
  ORDER BY wr.created_at DESC
");
$stmt->execute([$collectorId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Collector Dashboard</title></head>
<body>
<h2>Collector Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> |
  <a href="../auth/logout.php">Logout</a>
</p>

<h3>Assigned Reports</h3>
<?php if (!$reports): ?>
  <p>No assigned reports.</p>
<?php else: ?>
<table border="1" cellpadding="8" cellspacing="0">
<tr>
  <th>ID</th><th>Category</th><th>Description</th><th>Location</th><th>Status</th><th>Update</th>
</tr>
<?php foreach ($reports as $r): ?>
<tr>
  <td><?php echo (int)$r['id']; ?></td>
  <td><?php echo htmlspecialchars($r['category']); ?></td>
  <td><?php echo htmlspecialchars($r['description']); ?></td>
  <td><?php echo htmlspecialchars($r['location_text']); ?></td>
  <td><?php echo htmlspecialchars($r['status']); ?></td>
  <td>
    <form method="POST" style="margin:0;">
      <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
      <select name="status" required>
        <option value="in_progress">in_progress</option>
        <option value="collected">collected</option>
        <option value="rejected">rejected</option>
      </select>
      <button type="submit">Update</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>