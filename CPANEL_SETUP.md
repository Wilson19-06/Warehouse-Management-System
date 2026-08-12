# cPanel 设置指南

## 问题 1: HTTP 500 错误

HTTP 500 错误通常是数据库连接问题。请按照以下步骤配置：

### 步骤 1: 获取数据库信息

1. 登录您的 cPanel
2. 找到 **MySQL Databases** 或 **数据库** 选项
3. 查看您的数据库信息：
   - **数据库名称**：通常是 `cpanel用户名_dbname`
   - **数据库用户名**：通常是 `cpanel用户名_dbuser`
   - **数据库密码**：您设置的密码
   - **主机名**：通常是 `localhost`

### 步骤 2: 配置数据库连接

编辑项目中的 `config.php` 文件，填入您的数据库信息：

```php
define('DB_HOST', 'localhost');              // 通常是 localhost
define('DB_USER', 'yourcpanel_warehouse');   // 您的数据库用户名
define('DB_PASS', 'your_password_here');     // 您的数据库密码
define('DB_NAME', 'yourcpanel_warehouse_db'); // 您的数据库名称
```

**示例：**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'wilson_warehouse');
define('DB_PASS', 'MySecurePassword123');
define('DB_NAME', 'wilson_warehouse_db');
```

### 步骤 3: 创建数据库和表

1. 在 cPanel 中打开 **phpMyAdmin**
2. 选择您的数据库
3. 点击 **SQL** 标签
4. 复制 `db/init.sql` 文件中的所有 SQL 代码
5. 粘贴到 SQL 编辑器中
6. 点击 **执行** 或 **Go**

### 步骤 4: 创建管理员账户

在 phpMyAdmin 的 SQL 标签中运行：

```sql
INSERT INTO users (username, password_hash, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
```

这个密码是 `password` 的哈希值。登录后请立即修改密码。

或者使用注册页面 `register.php` 创建账户。

## 问题 2: 自动跳转到之前的页面

已修复！现在系统会：
- 如果未登录，自动跳转到 `login.php`
- 如果已登录，自动跳转到 `dashboard.php`

## 常见问题排查

### 1. 仍然出现 HTTP 500 错误

**检查清单：**
- [ ] `config.php` 中的数据库信息是否正确
- [ ] 数据库是否已创建
- [ ] 数据库表是否已创建（运行 `db/init.sql`）
- [ ] 数据库用户名和密码是否正确
- [ ] 数据库用户是否有权限访问数据库

**查看错误日志：**
- 在 cPanel 中找到 **错误日志** 或 **Error Logs**
- 查看最新的错误信息
- 错误信息会显示具体的问题

### 2. 数据库连接失败

**解决方法：**
1. 确认 `config.php` 文件存在且配置正确
2. 确认数据库用户有权限访问数据库
3. 在 cPanel 的 MySQL Databases 中，确保用户已添加到数据库

### 3. 表不存在错误

**解决方法：**
1. 打开 phpMyAdmin
2. 选择您的数据库
3. 运行 `db/init.sql` 中的所有 SQL 语句

### 4. 权限问题

**解决方法：**
- 确保 PHP 文件权限为 644
- 确保目录权限为 755
- 在 cPanel 文件管理器中可以设置权限

## 文件上传到 cPanel

1. 将所有文件上传到 `public_html/warehousing/` 目录（或您想要的目录）
2. 确保 `config.php` 文件已正确配置
3. 确保数据库已创建并运行了 `db/init.sql`
4. 访问 `https://yourdomain.com/warehousing/login.php`

## 安全建议

1. **修改默认密码**：创建账户后立即修改密码
2. **保护 config.php**：确保 `config.php` 文件不在公开目录中（可选）
3. **定期备份**：定期备份数据库
4. **更新 PHP 版本**：确保使用较新的 PHP 版本（7.4+）

## 测试连接

创建一个测试文件 `test_db.php`：

```php
<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
} else {
    echo "数据库连接成功！";
}

$conn->close();
?>
```

访问 `test_db.php`，如果显示"数据库连接成功！"，说明配置正确。

**重要：测试完成后请删除 `test_db.php` 文件！**

