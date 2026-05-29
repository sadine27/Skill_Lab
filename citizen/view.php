<?php
require_once __DIR__ . '/../auth_check.php';
require_role('citizen');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/report_helpers.php';

$userId = $_SESSION['user_id'];
$reportId = (int)($_GET['report_id'] ?? ($_GET['id'] ?? 0));
$isPhotoRequest = isset($_GET['photo']);

function serveReportPhoto(array $report): void
{
  if (empty($report['photo_path'])) {
    http_response_code(404);
    exit('404 Error Occurred');
  }

  $uploadsRoot = realpath(__DIR__ . '/../uploads');
  $photoPath = realpath(__DIR__ . '/../' . ltrim($report['photo_path'], '/\\'));

  if ($uploadsRoot === false || $photoPath === false || strpos($photoPath, $uploadsRoot) !== 0 || !is_file($photoPath)) {
    http_response_code(404);
    exit('404 Error Occurred');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($photoPath) ?: 'application/octet-stream';

  if (strpos($mimeType, 'image/') !== 0) {
    http_response_code(404);
    exit('404 Error Occurred');
  }

  header('Content-Type: ' . $mimeType);
  header('Content-Length: ' . filesize($photoPath));
  readfile($photoPath);
  exit();
}

if ($reportId <= 0) {
  http_response_code(404);
  exit('404 Error Occurred');
}

$report = loadCitizenReport($pdo, $reportId, $userId);

if (!$report) {
  http_response_code(404);
  exit('404 Error Occurred');
}

if ($isPhotoRequest) {
  serveReportPhoto($report);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Report</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Report Details</h1>
    <span class="user">
      <a href="index.php">Back to Dashboard</a>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card" style="max-width:820px;">
      <h2>Report Details</h2>
      <p>
        <a class="btn secondary" href="index.php">Back to Dashboard</a>
        <?php if ($report['status'] === 'pending'): ?>
          <a class="btn" href="submit.php?report_id=<?php echo (int)$report['id']; ?>">Edit Report</a>
        <?php endif; ?>
      </p>

      <div class="data"><strong>Category:</strong> <?php echo htmlspecialchars($report['category']); ?></div>
      <div class="data"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?></div>
      <div class="data"><strong>Location:</strong> <?php echo htmlspecialchars($report['location_text']); ?></div>
      <div class="data"><strong>Status:</strong> <span class="<?php echo reportStatusBadgeClass($report['status']); ?>"><?php echo htmlspecialchars(reportStatusLabel($report['status'])); ?></span></div>
      <div class="data"><strong>Created:</strong> <?php echo htmlspecialchars($report['created_at']); ?></div>
      <div class="data"><strong>Updated:</strong> <?php echo htmlspecialchars($report['updated_at'] ?? $report['created_at']); ?></div>

      <?php if (!empty($report['photo_path'])): ?>
        <div class="data">
          <strong>Uploaded Photo:</strong>
          <div style="margin-top: 10px;">
            <img src="view.php?report_id=<?php echo (int)$report['id']; ?>&photo=1" alt="Uploaded report photo" style="max-width:100%;height:auto;border-radius:8px;border:1px solid #ddd;">
          </div>
        </div>
      <?php else: ?>
        <div class="data"><strong>Uploaded Photo:</strong> Not available</div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
