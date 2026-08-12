<?php
// 数据库初始化脚本
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

// 先检查数据库是否存在，如果不存在就创建
$conn_admin = new mysqli($host, $user, $pass);

if ($conn_admin->connect_error) {
    die("初始连接失败: " . $conn_admin->connect_error);
}

// 创建数据库
$conn_admin->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn_admin->close();

// 现在连接到指定数据库
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "<h2>数据库初始化</h2>";

// 1. 创建 users 表
echo "<p><strong>步骤 1: 创建 users 表</strong></p>";
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_users) === TRUE) {
    echo "<p style='color: green;'>✓ users 表创建成功</p>";
} else {
    echo "<p style='color: red;'>✗ users 表创建失败: " . $conn->error . "</p>";
    echo "<p>SQL: " . htmlspecialchars($sql_users) . "</p>";
}

// 验证表是否存在
$check = $conn->query("SHOW TABLES LIKE 'users'");
if ($check && $check->num_rows > 0) {
    echo "<p style='color: green;'>✓ 验证: users 表确实存在</p>";
} else {
    echo "<p style='color: red;'>✗ 验证失败: users 表不存在！</p>";
    die("无法继续，users 表不存在");
}

// 2. 创建 items 表
echo "<p><strong>步骤 2: 创建 items 表</strong></p>";
$sql_items = "CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(255) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level INT NOT NULL DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_items) === TRUE) {
    echo "<p style='color: green;'>✓ items 表创建成功</p>";
} else {
    echo "<p style='color: red;'>✗ items 表创建失败: " . $conn->error . "</p>";
}

// 3. 创建 stock_movements 表
echo "<p><strong>步骤 3: 创建 stock_movements 表</strong></p>";
$sql_movements = "CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    reference_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql_movements) === TRUE) {
    echo "<p style='color: green;'>✓ stock_movements 表创建成功</p>";
} else {
    echo "<p style='color: red;'>✗ stock_movements 表创建失败: " . $conn->error . "</p>";
}

// 4. 创建默认用户
echo "<hr><h3>步骤 4: 创建默认用户</h3>";

$defaults = [
    ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
    ['user', password_hash('user123', PASSWORD_DEFAULT), 'user']
];

foreach ($defaults as [$username, $password_hash, $role]) {
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $username, $password_hash, $role);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ 用户 '<strong>$username</strong>' 创建成功</p>";
        } else {
            if (strpos($stmt->error, 'Duplicate entry') !== false) {
                echo "<p style='color: orange;'>⚠ 用户 '$username' 已存在（跳过）</p>";
            } else {
                echo "<p style='color: red;'>✗ 创建用户失败: " . $stmt->error . "</p>";
            }
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>✗ Prepare 失败: " . $conn->error . "</p>";
    }
}

echo "<hr><h3>✓ 初始化完成！</h3>";
echo "<p><strong>默认用户：</strong></p>";
echo "<ul>";
echo "<li>用户名: <code>admin</code> | 密码: <code>admin123</code> | 角色: 管理员</li>";
echo "<li>用户名: <code>user</code> | 密码: <code>user123</code> | 角色: 普通用户</li>";
echo "</ul>";
echo "<p><a href='login.php' style='color: blue; text-decoration: underline; font-size: 16px;'>→ 去登录</a></p>";

$conn->close();
?>


