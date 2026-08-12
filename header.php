<?php
// 确保 session 已启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 共享的导航栏和头部
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 确保数据库连接已加载
if (!isset($conn)) {
    include 'db.php';
}

// 从数据库重新获取角色（确保是最新的）
$currentUser = $_SESSION['username'] ?? '';
$userRole = $_SESSION['role'] ?? 'user'; // 默认角色

if ($currentUser && isset($conn)) {
    $userRole = app_fetch_user_role($conn, $currentUser, $userRole);
}
$_SESSION['role'] = $userRole;

$roleBadge = $userRole === 'admin' ? 'badge bg-danger' : 'badge bg-secondary';
$roleText = $userRole === 'admin' ? '管理员' : '普通用户';

// 获取当前页面名称
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warehouse Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<!-- Top navigation bar -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-box-seam me-2"></i>Warehouse Management
        </a>
        <input type="checkbox" id="navbar-toggle" style="display: none;">
        <label for="navbar-toggle" class="navbar-toggler" style="cursor: pointer; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; padding: 4px 8px;">
            <span class="navbar-toggler-icon"></span>
        </label>
        <div class="navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'items.php' || $currentPage === 'index.php' ? 'active' : '' ?>" href="items.php">
                        <i class="bi bi-box me-1"></i>Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'movement.php' ? 'active' : '' ?>" href="movement.php">
                        <i class="bi bi-arrow-left-right me-1"></i>Movement
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'report.php' ? 'active' : '' ?>" href="report.php">
                        <i class="bi bi-graph-up me-1"></i>Report
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                    <span class="<?= $roleBadge ?> ms-2"><?= $roleText ?></span>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4 page-fade">

