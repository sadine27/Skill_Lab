<?php
session_start();
$role = $_SESSION['user_role'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><title>Waste System</title></head>
<body>
<h2>Waste Management System</h2>

<?php if (!$role): ?>
  <p><a href="auth/login.php">Login</a> | <a href="auth/register.php">Register</a></p>
<?php else: ?>
  <p>
    Logged in as <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>
    (<?php echo htmlspecialchars($role); ?>)
    | <a href="auth/logout.php">Logout</a>
  </p>

  <?php if ($role === 'admin'): ?>
    <p><a href="admin/index.php">Go to Admin Dashboard</a></p>
  <?php elseif ($role === 'collector'): ?>
    <p><a href="collector/index.php">Go to Collector Dashboard</a></p>
  <?php else: ?>
    <p><a href="citizen/index.php">Go to Citizen Dashboard</a></p>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>