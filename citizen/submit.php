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
    if (!empty($_FILES['photo']['name'])) {
      $allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!in_array($_FILES['photo']['type'], $allowed, true)) {
        $message = 'Photo must be JPG/PNG/WebP.';
      } else {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $safeName = 'report_' . time() . '_' . rand(1000,9999) . '.' . $ext;

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $dest = $uploadDir . $safeName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
          $photoPath = 'uploads/' . $safeName;
        } else {
          $message = 'Photo upload failed.';
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
<html>
<head>
  <title>Submit Waste Report</title>
</head>
<body>
  <h2>Submit Waste Report</h2>
  <p><a href="index.php">Back to Dashboard</a> | <a href="../auth/logout.php">Logout</a></p>

  <?php if ($message): ?>
    <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <label>Category *</label><br>
    <select name="category_id" required>
      <option value="">-- select --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>">
          <?php echo htmlspecialchars($c['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <br><br>

    <label>Description *</label><br>
    <textarea name="description" rows="4" cols="50" required></textarea>
    <br><br>

    <label>Location *</label><br>
    <input type="text" name="location_text" size="50" required />
    <br><br>

    <label>Photo (optional)</label><br>
    <input type="file" name="photo" accept="image/*" />
    <br><br>

    <button type="submit">Submit Report</button>
  </form>
</body>
</html>