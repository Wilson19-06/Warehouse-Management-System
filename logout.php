<?php
session_start();

// 清除所有 session 变量
$_SESSION = array();

// 删除 session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// 销毁 session
session_destroy();

// 重定向到登录页面
header("Location: login.php");
exit();
?>
