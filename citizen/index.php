<?php
require_once __DIR__ . '/../auth_check.php';
require_role('citizen');
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reportId = (int)($_POST['report_id'] ?? 0);

  if ($reportId > 0) {
    $stmt = $pdo->prepare("
      UPDATE waste_reports
      SET status = 'rejected', updated_at = NOW()
      WHERE id = ? AND citizen_id = ? AND status = 'pending'
    ");
    $stmt->execute([$reportId, $userId]);
  }

  header('Location: index.php');
  exit();
}

$stmt = $pdo->prepare("
  SELECT wr.id, wc.name AS category, wr.description, wr.location_text, wr.status,
         wr.created_at, wr.updated_at
  FROM waste_reports wr
  JOIN waste_categories wc ON wc.id = wr.category_id
  WHERE wr.citizen_id = ?
  ORDER BY wr.created_at DESC
");
$stmt->execute([$userId]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Citizen Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Citizen Dashboard</h1>
    <span class="user">
      Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card">
      <div class="toolbar">
        <a href="submit.php" class="btn">+ Submit New Waste Report</a>
        <span class="grow"></span>
        <?php if ($reports): ?>
          <input type="text" data-filter="reports-table" placeholder="Filter your reports…" style="max-width:240px;">
        <?php endif; ?>
      </div>

      <h3>Your Reports</h3>

      <?php if (!$reports): ?>
        <p class="empty">No reports yet. Submit your first one above.</p>
      <?php else: ?>
        <table class="data" id="reports-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Description</th>
              <th>Location</th>
              <th>Status</th>
              <th>Created</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $report): ?>
              <tr>
                <td><?php echo (int)$report['id']; ?></td>
                <td><?php echo htmlspecialchars($report['category']); ?></td>
                <td><?php echo htmlspecialchars($report['description']); ?></td>
                <td><?php echo htmlspecialchars($report['location_text']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $report['status'])); ?></span></td>
                <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                <td><?php echo htmlspecialchars($report['updated_at'] ?? $report['created_at']); ?></td>
                <td>
                  <a class="btn small" href="view.php?report_id=<?php echo (int)$report['id']; ?>">View</a>
                  <?php if ($report['status'] === 'pending'): ?>
                    <a class="btn small" href="submit.php?report_id=<?php echo (int)$report['id']; ?>">Edit</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this pending report?');">
                      <input type="hidden" name="report_id" value="<?php echo (int)$report['id']; ?>">
                      <button type="submit" class="btn small">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="muted">—</span>
                  <?php endif; ?>
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
