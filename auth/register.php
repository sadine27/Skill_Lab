<?php
require_once __DIR__ . '/../db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'citizen';

    if ($name === '' || $email === '' || $password === '') {
        $message = 'All fields are required.';
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
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="text" name="name" placeholder="Name" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <select name="role">
            <option value="citizen">Citizen</option>
            <option value="collector">Collector</option>
            <option value="admin">Admin</option>
        </select><br><br>
        <button type="submit">Register</button>
    </form>

    <p><a href="login.php">Login here</a></p>
</body>
</html>