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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Collector Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Collector Dashboard</h1>
    <span class="user">
      Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0;">Assigned Reports</h3>
        <span class="grow"></span>
        <?php if ($reports): ?>
          <input type="text" data-filter="jobs-table" placeholder="Filter jobs…" style="max-width:240px;">
        <?php endif; ?>
      </div>

      <?php if (!$reports): ?>
        <p class="empty">No assigned reports right now.</p>
      <?php else: ?>
      <table class="data" id="jobs-table">
        <thead>
          <tr>
            <th>ID</th><th>Category</th><th>Description</th>
            <th>Location</th><th>Status</th><th>Update</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['category']); ?></td>
            <td><?php echo htmlspecialchars($r['description']); ?></td>
            <td><?php echo htmlspecialchars($r['location_text']); ?></td>
            <td><span class="badge <?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $r['status'])); ?></span></td>
            <td>
              <form method="POST" style="display:flex; gap:6px; margin:0;"
                    data-confirm="Update the status of report #<?php echo (int)$r['id']; ?>?">
                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                <select name="status" required style="width:auto;">
                  <option value="in_progress">In Progress</option>
                  <option value="collected">Collected</option>
                  <option value="rejected">Rejected</option>
                </select>
                <button type="submit" class="btn small">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <script src="../assets/app.js"></script>
</body>
</html>