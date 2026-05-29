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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Admin Dashboard</h1>
    <span class="user">
      Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0;">All Reports</h3>
        <span class="grow"></span>
        <?php if ($reports): ?>
          <input type="text" data-filter="reports-table" placeholder="Filter reports…" style="max-width:260px;">
        <?php endif; ?>
      </div>

      <?php if (!$reports): ?>
        <p class="empty">No reports submitted yet.</p>
      <?php else: ?>
      <table class="data" id="reports-table">
        <thead>
          <tr>
            <th>ID</th><th>Citizen</th><th>Category</th><th>Description</th><th>Location</th>
            <th>Status</th><th>Assigned To</th><th>Assign Collector</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['citizen_name']); ?></td>
            <td><?php echo htmlspecialchars($r['category_name']); ?></td>
            <td><?php echo htmlspecialchars($r['description']); ?></td>
            <td><?php echo htmlspecialchars($r['location_text']); ?></td>
            <td><span class="badge <?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $r['status'])); ?></span></td>
            <td><?php echo htmlspecialchars($r['collector_name'] ?? 'Not assigned'); ?></td>
            <td>
              <form method="POST" style="display:flex; gap:6px; margin:0;"
                    data-confirm="Assign report #<?php echo (int)$r['id']; ?> to the selected collector?">
                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                <select name="collector_id" required style="width:auto;">
                  <option value="">-- select --</option>
                  <?php foreach ($collectors as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>">
                      <?php echo htmlspecialchars($c['name'] . " (" . $c['email'] . ")"); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn small">Assign</button>
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