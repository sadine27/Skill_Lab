<?php
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];
$message = '';

$catStmt = $pdo->query("SELECT id, name FROM waste_categories ORDER BY name");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $categoryId = (int)($_POST['category_id'] ?? 0);
  $description = trim($_POST['description'] ?? '');
  $locationText = trim($_POST['location_text'] ?? '');

  if ($categoryId <= 0 || $description === '' || $locationText === '') {
    $message = 'Please fill all required fields.';
  } else {
    $photoPath = null;

    // handle optional photo upload
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Photo upload failed (error ' . (int)$_FILES['photo']['error'] . ').';
      } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        $message = 'Photo must be 5 MB or smaller.';
      } else {
        // Verify the actual file contents are an image — never trust the
        // client-supplied MIME type or filename extension (both are spoofable).
        $allowed = [
          IMAGETYPE_JPEG => 'jpg',
          IMAGETYPE_PNG  => 'png',
          IMAGETYPE_WEBP => 'webp',
        ];
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        if ($info === false || !isset($allowed[$info[2]])) {
          $message = 'Photo must be a valid JPG, PNG, or WebP image.';
        } else {
          $ext = $allowed[$info[2]];
          $safeName = 'report_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

          $uploadDir = __DIR__ . '/../uploads/';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
          }

          $dest = $uploadDir . $safeName;
          if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photoPath = 'uploads/' . $safeName;
          } else {
            $message = 'Photo upload failed.';
          }
        }
      }
    }

    if ($message === '') {
      $stmt = $pdo->prepare("
        INSERT INTO waste_reports (citizen_id, category_id, description, location_text, photo_path)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$userId, $categoryId, $description, $locationText, $photoPath]);

      header("Location: index.php");
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Waste Report</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Submit Waste Report</h1>
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

      <form method="POST" enctype="multipart/form-data" data-validate>
        <label for="category_id">Category *</label>
        <select id="category_id" name="category_id" required>
          <option value="">-- select --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>">
              <?php echo htmlspecialchars($c['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="description">Description *</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <label for="location_text">Location *</label>
        <input type="text" id="location_text" name="location_text" required
               placeholder="Street, landmark, or area name" />

        <label for="photo">Photo (optional)</label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" />
        <p class="muted">JPG, PNG, or WebP — up to 5 MB.</p>

        <p><button type="submit" class="btn">Submit Report</button></p>
      </form>
    </div>
  </div>

  <script src="../assets/app.js"></script>
</body>
</html>