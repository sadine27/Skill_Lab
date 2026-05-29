<?php
session_start();
require_once __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } elseif ($user['role'] === 'collector') {
            header("Location: ../collector/index.php");
        } else {
            header("Location: ../citizen/index.php");
        }
        exit();
    } else {
        $message = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container" style="max-width:420px;">
        <div class="card">
            <h2>Login</h2>
            <?php if ($message): ?>
                <div class="alert error"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <p style="margin-top:16px;"><button type="submit" class="btn">Login</button></p>
            </form>

            <p class="muted">New here? <a href="register.php">Create an account</a></p>
        </div>
    </div>

    <script src="../assets/app.js"></script>
</body>
</html>