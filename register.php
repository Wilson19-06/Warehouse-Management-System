<?php 
// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 调试：检查 config.php
if (!file_exists('config.php')) {
    die("<h2>错误：config.php 不存在！</h2>");
}

require_once 'config.php';

// 调试：检查常量
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
    die("<h2>错误：数据库配置常量未定义！</h2><p>请检查 config.php</p>");
}

// 连接数据库
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("<h2>数据库连接失败</h2><p>" . htmlspecialchars($conn->connect_error) . "</p><p>请检查 config.php 中的数据库配置</p>");
}

$conn->set_charset("utf8mb4");

$error = '';
$success = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // bcrypt hash
    $role = $_POST['role'] ?? 'user';

    // 只允许 admin / user 两种角色，防止乱传值
    if ($role !== 'admin' && $role !== 'user') {
        $role = 'user';
    }

    // Insert into users table used by Node.js app as well
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Registration successful! Please login.';
        header("Location: login.php");
        exit();
    } else {
        $error = 'Username already exists!';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Create Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fee2e2; border-radius: 12px; padding: 12px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mb-3" style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #d1fae5; border-radius: 12px; padding: 12px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="text-start">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create a password" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="user" selected>普通用户 (User)</option>
                    <option value="admin">管理员 (Admin)</option>
                </select>
            </div>
            <button type="submit" name="register" class="glow-btn mb-3">Register</button>
        </form>
        <p class="mt-2 text-muted">Already have an account? <a href="login.php" class="auth-link">Login</a></p>
    </div>
</div>
</body>
</html>
