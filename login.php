<?php
session_start();  // 启动 session

// 使用共享的数据库连接
// 如果数据库连接失败，db.php 会显示错误并终止执行
@include 'db.php';

// 检查数据库连接是否成功
if (!isset($conn) || $conn->connect_error) {
    die("数据库连接失败。请检查 config.php 中的配置，或访问 debug.php 查看详细错误信息。");
}

// 显示错误消息
$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// 处理登录
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = app_find_user_by_username($conn, $username);

    // 使用 password_hash / password_verify 与 users.password_hash 字段
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role']; // 保存角色到 session
        
        // 调试信息（可以删除）
        // echo "<script>console.log('User role from DB:', '" . $user['role'] . "');</script>";
        
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fee2e2; border-radius: 12px; padding: 12px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="text-start">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" name="login" class="glow-btn mb-3">Login</button>
        </form>
        <p class="mt-2 text-muted">Don't have an account? <a href="register.php" class="auth-link">Create one</a></p>
    </div>
</div>
</body>
</html>
