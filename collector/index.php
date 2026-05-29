<?php
require_once __DIR__ . '/../auth_check.php';
require_role('collector');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/report_helpers.php';

$collectorId = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reportId = (int)($_POST['report_id'] ?? 0);
  $newStatus = $_POST['status'] ?? '';
  $notes = trim($_POST['notes'] ?? '');

  if ($reportId > 0) {
    $result = updateCollectorReportStatus($pdo, $reportId, $collectorId, $newStatus, $notes);
    if ($result['success']) {
      header('Location: index.php');
      exit();
    }

    $message = $result['message'];
  }
}

$reports = loadCollectorReports($pdo, $collectorId);
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

      <?php if ($message): ?>
        <div class="alert error"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if (!$reports): ?>
        <p class="empty">No assigned reports right now.</p>
      <?php else: ?>
        <table class="data" id="jobs-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Category</th>
              <th>Description</th>
              <th>Location</th>
              <th>Status</th>
              <th>Created</th>
              <th>Updated</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $report): ?>
              <tr>
                <td><?php echo (int)$report['id']; ?></td>
                <td><?php echo htmlspecialchars($report['category']); ?></td>
                <td><?php echo htmlspecialchars($report['description']); ?></td>
                <td><?php echo htmlspecialchars($report['location_text']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars(reportStatusLabel($report['status'])); ?></span></td>
                <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                <td><?php echo htmlspecialchars($report['updated_at'] ?? $report['created_at']); ?></td>
                <td>
                  <?php $allowedStatuses = reportNextStatuses($report['status']); ?>
                  <?php if (!$allowedStatuses): ?>
                    <span class="muted">—</span>
                  <?php else: ?>
                    <form method="POST" style="display:grid; gap:6px; margin:0;"
                          data-confirm="Update the status of report #<?php echo (int)$report['id']; ?>?">
                      <input type="hidden" name="report_id" value="<?php echo (int)$report['id']; ?>">
                      <select name="status" required style="width:auto;">
                        <?php foreach ($allowedStatuses as $allowedStatus): ?>
                          <option value="<?php echo htmlspecialchars($allowedStatus); ?>">
                            <?php echo htmlspecialchars(reportStatusLabel($allowedStatus)); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <textarea name="notes" rows="3" placeholder="Collection notes (optional)"></textarea>
                      <button type="submit" class="btn small">Update</button>
                    </form>
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
