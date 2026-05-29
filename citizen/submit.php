<?php
require_once __DIR__ . '/../auth_check.php';
require_role('citizen');
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];
$message = '';
$isEditing = false;
$report = [
  'id' => null,
  'category_id' => '',
  'description' => '',
  'location_text' => '',
  'photo_path' => '',
  'status' => '',
];

$catStmt = $pdo->query("SELECT id, name FROM waste_categories ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$reportId = (int)($_GET['report_id'] ?? $_POST['report_id'] ?? 0);
$canEdit = false;

if ($reportId > 0) {
  $editStmt = $pdo->prepare("
    SELECT id, category_id, description, location_text, photo_path, status
    FROM waste_reports
    WHERE id = ? AND citizen_id = ?
    LIMIT 1
  ");
  $editStmt->execute([$reportId, $userId]);
  $existingReport = $editStmt->fetch(PDO::FETCH_ASSOC);

  if ($existingReport) {
    $report = $existingReport;
    $isEditing = true;
    $canEdit = $report['status'] === 'pending';
    if (!$canEdit) {
      $message = 'Only pending reports can be edited.';
    }
  } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(404);
    exit('Report not found.');
  }
}

function storeUploadedPhoto(array $file, ?string &$errorMessage): ?string
{
  if (empty($file['name'])) {
    return null;
  }

  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $errorMessage = 'Photo upload failed.';
    return null;
  }

  if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
    $errorMessage = 'Photo must be 5 MB or smaller.';
    return null;
  }

  $allowed = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG => 'png',
    IMAGETYPE_WEBP => 'webp',
  ];
  $info = @getimagesize($file['tmp_name']);
  if ($info === false || !isset($allowed[$info[2]])) {
    $errorMessage = 'Photo must be a valid JPG, PNG, or WebP image.';
    return null;
  }

  $extension = $allowed[$info[2]];
  $safeName = 'report_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
  $uploadDir = __DIR__ . '/../uploads/';

  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
  }

  $destination = $uploadDir . $safeName;
  if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $errorMessage = 'Photo upload failed.';
    return null;
  }

  return 'uploads/' . $safeName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoryId = (int)($_POST['category_id'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  $locationText = trim($_POST['location_text'] ?? '');

  if ($categoryId <= 0 || $description === '' || $locationText === '') {
    $message = 'Please fill all required fields.';
  } elseif ($reportId > 0 && !$canEdit) {
    $message = 'Only pending reports can be edited.';
  } else {
    $photoPath = $report['photo_path'] ?: null;
    $uploadedPhotoPath = storeUploadedPhoto($_FILES['photo'] ?? [], $message);

    if ($message === '' && $uploadedPhotoPath !== null) {
      $photoPath = $uploadedPhotoPath;
    }

    if ($message === '') {
      if ($canEdit) {
        $stmt = $pdo->prepare("
          UPDATE waste_reports
          SET category_id = ?, description = ?, location_text = ?, photo_path = ?, updated_at = NOW()
          WHERE id = ? AND citizen_id = ? AND status = 'pending'
        ");
        $stmt->execute([$categoryId, $description, $locationText, $photoPath, $report['id'], $userId]);
      } else {
        $stmt = $pdo->prepare("
          INSERT INTO waste_reports (citizen_id, category_id, description, location_text, photo_path, status, updated_at)
          VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$userId, $categoryId, $description, $locationText, $photoPath]);
      }

      header('Location: index.php');
      exit();
    }
  }
}

$selectedCategoryId = (int)($report['category_id'] ?? 0);
$descriptionValue = $report['description'] ?? '';
$locationValue = $report['location_text'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $isEditing ? 'Edit Waste Report' : 'Submit Waste Report'; ?></title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1><?php echo $isEditing ? 'Edit Waste Report' : 'Submit Waste Report'; ?></h1>
    <span class="user">
      <a href="index.php">Back to Dashboard</a> &nbsp;|&nbsp;
      <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card">
      <?php if ($message): ?>
        <div class="alert error"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if ($isEditing && !empty($report['photo_path'])): ?>
        <p class="muted">
          Current photo:
          <a class="btn secondary" href="view.php?report_id=<?php echo (int)$report['id']; ?>&photo=1" target="_blank" rel="noopener">View uploaded image</a>
        </p>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" data-validate>
        <?php if ($isEditing): ?>
          <input type="hidden" name="report_id" value="<?php echo (int)$report['id']; ?>">
        <?php endif; ?>

        <label for="category_id">Category *</label>
        <select id="category_id" name="category_id" required>
          <option value="">-- select --</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?php echo (int)$category['id']; ?>" <?php echo ((int)$category['id'] === $selectedCategoryId) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($category['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="description">Description *</label>
        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($descriptionValue); ?></textarea>

        <label for="location_text">Location *</label>
        <input type="text" id="location_text" name="location_text" required
               value="<?php echo htmlspecialchars($locationValue); ?>"
               placeholder="Street, landmark, or area name" />

        <label for="photo">Photo (optional)</label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
        <p class="muted">JPG, PNG, or WebP — up to 5 MB.</p>

        <p><button type="submit" class="btn"><?php echo $isEditing ? 'Update Report' : 'Submit Report'; ?></button></p>
      </form>
    </div>
  </div>

  <script src="../assets/app.js"></script>
</body>
</html>
