<?php
// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>调试信息</h2>";

// 1. 检查 PHP 版本
echo "<p><strong>PHP 版本:</strong> " . phpversion() . "</p>";

// 2. 检查 config.php 是否存在
echo "<p><strong>config.php 存在:</strong> " . (file_exists('config.php') ? '是' : '否') . "</p>";

// 3. 尝试加载 config.php
if (file_exists('config.php')) {
    echo "<p>正在加载 config.php...</p>";
    try {
        require_once 'config.php';
        echo "<p style='color: green;'>✓ config.php 加载成功</p>";
        
        // 检查常量是否定义
        echo "<p><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? DB_HOST : '未定义') . "</p>";
        echo "<p><strong>DB_USER:</strong> " . (defined('DB_USER') ? DB_USER : '未定义') . "</p>";
        echo "<p><strong>DB_PASS:</strong> " . (defined('DB_PASS') ? str_repeat('*', strlen(DB_PASS)) : '未定义') . "</p>";
        echo "<p><strong>DB_NAME:</strong> " . (defined('DB_NAME') ? DB_NAME : '未定义') . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ config.php 加载失败: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ config.php 文件不存在</p>";
}

// 4. 尝试连接数据库
echo "<hr><h3>数据库连接测试</h3>";

if (file_exists('config.php')) {
    require_once 'config.php';
    
    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $dbname = defined('DB_NAME') ? DB_NAME : 'warehouse_db';
    
    echo "<p>尝试连接: $host / $user / $dbname</p>";
    
    $conn = @new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>✗ 数据库连接失败: " . $conn->connect_error . "</p>";
        echo "<p><strong>错误代码:</strong> " . $conn->connect_errno . "</p>";
    } else {
        echo "<p style='color: green;'>✓ 数据库连接成功！</p>";
        
        // 检查表是否存在
        $tables = ['users', 'items', 'stock_movements'];
        echo "<hr><h3>数据库表检查</h3>";
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✓ 表 '$table' 存在</p>";
            } else {
                echo "<p style='color: red;'>✗ 表 '$table' 不存在</p>";
            }
        }
        
        $conn->close();
    }
} else {
    echo "<p style='color: red;'>无法测试数据库连接：config.php 不存在</p>";
}

// 5. 检查其他文件
echo "<hr><h3>文件检查</h3>";
$files = ['db.php', 'login.php', 'dashboard.php', 'items.php'];
foreach ($files as $file) {
    echo "<p><strong>$file:</strong> " . (file_exists($file) ? '存在' : '不存在') . "</p>";
}

// 6. 检查 session
echo "<hr><h3>Session 检查</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p><strong>Session 状态:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? '活动' : '未启动') . "</p>";
if (isset($_SESSION['username'])) {
    echo "<p><strong>已登录用户:</strong> " . $_SESSION['username'] . "</p>";
} else {
    echo "<p>未登录</p>";
}

?>

