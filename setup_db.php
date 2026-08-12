<?php
/**
 * cPanel 数据库初始化脚本
 * 
 * 使用方法：
 * 1. 上传此文件到你的项目根目录
 * 2. 访问 http://yourdomain.com/setup_db.php
 * 3. 按照提示完成初始化
 * 4. 删除此文件（为了安全起见）
 */

// 启用错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 必需的 config.php
if (!file_exists('config.php')) {
    die("<h2 style='color: red;'>错误：config.php 不存在</h2><p>请先确保 config.php 已正确配置</p>");
}

require_once 'config.php';

// 获取数据库配置
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';
$dbname = defined('DB_NAME') ? DB_NAME : 'warehouse_db';

// 连接数据库
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("<h2 style='color: red;'>数据库连接失败</h2><p>" . htmlspecialchars($conn->connect_error) . "</p><p>请检查 config.php 中的数据库配置</p>");
}

$conn->set_charset("utf8mb4");

echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>数据库初始化</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h3 { color: #007bff; margin-top: 20px; }
        p { line-height: 1.6; color: #666; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info-box { background: #e7f3ff; border: 1px solid #007bff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .step { background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 数据库初始化</h1>
HTML;

// 验证表是否存在并完整
$tables_to_create = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'items' => "CREATE TABLE IF NOT EXISTS items (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'stock_movements' => "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        movement_type ENUM('IN', 'OUT') NOT NULL,
        quantity INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        reference_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// 创建表
echo "<h3>✓ 第一步：创建数据表</h3>";
$tables_created = [];
foreach ($tables_to_create as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ 表 '$table_name' 创建成功</p>";
        $tables_created[$table_name] = true;
    } else {
        echo "<p class='error'>✗ 表 '$table_name' 创建失败：" . $conn->error . "</p>";
        $tables_created[$table_name] = false;
    }
}

// 验证表是否真的存在
echo "<h3>✓ 第二步：验证表</h3>";
$check_result = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$dbname'");
$existing_tables = [];
if ($check_result) {
    while ($row = $check_result->fetch_assoc()) {
        $existing_tables[] = $row['TABLE_NAME'];
        echo "<p class='success'>✓ 数据库中存在表：{$row['TABLE_NAME']}</p>";
    }
}

// 创建默认用户
echo "<h3>✓ 第三步：创建默认用户</h3>";

$defaults = [
    ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
    ['user', password_hash('user123', PASSWORD_DEFAULT), 'user']
];

foreach ($defaults as [$username, $password_hash, $role]) {
    // 先检查用户是否存在
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo "<p class='warning'>⚠ 用户 '<strong>$username</strong>' 已存在（跳过）</p>";
        $check_stmt->close();
        continue;
    }
    $check_stmt->close();
    
    // 插入新用户
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $username, $password_hash, $role);
        if ($stmt->execute()) {
            echo "<p class='success'>✓ 用户 '<strong>$username</strong>' 创建成功</p>";
        } else {
            echo "<p class='error'>✗ 创建用户失败：" . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p class='error'>✗ Prepare 失败：" . $conn->error . "</p>";
    }
}

// 显示默认凭证
echo <<<HTML
<div class="info-box">
<h3>📋 默认用户信息</h3>
<table>
<tr>
    <th>用户名</th>
    <th>密码</th>
    <th>角色</th>
</tr>
<tr>
    <td><code>admin</code></td>
    <td><code>admin123</code></td>
    <td>管理员</td>
</tr>
<tr>
    <td><code>user</code></td>
    <td><code>user123</code></td>
    <td>普通用户</td>
</tr>
</table>
</div>

<div class="step">
<h3>⚠️ 重要提示</h3>
<p><strong>为了安全起见，请立即删除此文件 (setup_db.php)！</strong></p>
<p>初始化完成后，此文件应该被删除，以防止其他人恶意访问。</p>
</div>

<div class="info-box">
<h3>✓ 下一步</h3>
<p>1. <a href="login.php" style="color: #007bff; text-decoration: none;">前往登录页面</a></p>
<p>2. 用上面的默认账户登录</p>
<p>3. 删除此文件 (setup_db.php)</p>
<p>4. 修改管理员密码</p>
</div>

</div>
</body>
</html>
HTML;

$conn->close();
?>
