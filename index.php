<?php
session_start();

// 检查用户是否已登录
if (isset($_SESSION['username'])) {
    // 如果已登录，重定向到 dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // 如果未登录，重定向到登录页面
    header("Location: login.php");
    exit();
}
?>