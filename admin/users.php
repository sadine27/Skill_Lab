<?php
require_once __DIR__ . '/../auth_check.php';
require_role('admin');
require_once __DIR__ . '/../db.php';

$message = '';

// Toggle active/inactive
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId > 0 && in_array($action, ['deactivate', 'activate'], true)) {
        $isActive = ($action === 'activate') ? 1 : 0;

        // Prevent locking the default admin out by accident (optional safety)
        // You can remove this if you want.
        if ($userId === (int)($_SESSION['user_id'] ?? 0) && $isActive === 0) {
            $message = "You cannot deactivate your own account while logged in.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $userId]);
            $message = "User updated successfully.";
        }
    }
}

$users = $pdo->query("
    SELECT id, name, email, role, is_active, created_at
    FROM users
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Users</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>User Management</h1>
    <span class="user">
      Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
      &nbsp;|&nbsp; <a href="index.php">Admin Dashboard</a>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0;">All Users</h3>
        <span class="grow"></span>
        <?php if ($users): ?>
          <input type="text" data-filter="users-table" placeholder="Filter users…" style="max-width:260px;">
        <?php endif; ?>
      </div>

      <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <?php if (!$users): ?>
        <p class="empty">No users found.</p>
      <?php else: ?>
      <table class="data" id="users-table">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo (int)$u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
            <td>
              <?php if ((int)$u['is_active'] === 1): ?>
                <span class="badge collected">active</span>
              <?php else: ?>
                <span class="badge rejected">inactive</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($u['created_at']); ?></td>
            <td>
              <form method="POST" style="margin:0; display:inline-flex; gap:6px;"
                    data-confirm="Are you sure you want to update this user?">
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <?php if ((int)$u['is_active'] === 1): ?>
                  <input type="hidden" name="action" value="deactivate">
                  <button class="btn small" type="submit">Deactivate</button>
                <?php else: ?>
                  <input type="hidden" name="action" value="activate">
                  <button class="btn small" type="submit">Activate</button>
                <?php endif; ?>
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