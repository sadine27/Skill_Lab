<?php
require_once __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'citizen';

    // Only citizens and collectors may self-register; admins are seeded in the DB.
    if (!in_array($role, ['citizen', 'collector'], true)) {
        $role = 'citizen';
    }

    if ($name === '' || $email === '' || $password === '') {
        $message = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hashedPassword, $role]);
            $message = 'Registration successful. You can now log in.';
        } catch (PDOException $e) {
            $message = 'Email already exists or database error.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container" style="max-width:420px;">
        <div class="card">
            <h2>Register</h2>
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'successful') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" data-validate data-min-password="6">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <p class="muted">At least 6 characters.</p>

                <label for="role">Account type</label>
                <select id="role" name="role">
                    <option value="citizen">Citizen</option>
                    <option value="collector">Collector</option>
                </select>

                <p style="margin-top:16px;"><button type="submit" class="btn">Register</button></p>
            </form>

            <p class="muted">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script src="../assets/app.js"></script>
</body>
</html>