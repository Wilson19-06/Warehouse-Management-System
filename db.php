<?php
// Shared MySQL connection for all PHP pages
// Supports both local (XAMPP) and cloud server environments

function app_is_development()
{
    $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    return strpos($httpHost, 'localhost') !== false ||
        strpos($httpHost, '127.0.0.1') !== false ||
        getenv('APP_ENV') === 'development';
}

function app_debug_enabled()
{
    $debugFromQuery = isset($_GET['debug']) && $_GET['debug'] === '1';

    return $debugFromQuery || app_is_development() || (defined('APP_DEBUG') && APP_DEBUG);
}

function app_fail($publicMessage, $logMessage = null)
{
    if ($logMessage) {
        error_log($logMessage);
    }

    if (app_debug_enabled() && $logMessage) {
        die($publicMessage . "<br><br>Details: " . htmlspecialchars($logMessage, ENT_QUOTES, 'UTF-8'));
    }

    die($publicMessage);
}

function app_show_runtime_error($details)
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (app_debug_enabled()) {
        echo "<h2>Application Error</h2>";
        echo "<p>Please fix the issue below and retry.</p>";
        echo "<pre style='white-space: pre-wrap; background:#f5f5f5; padding:12px; border-radius:6px;'>" .
            htmlspecialchars($details, ENT_QUOTES, 'UTF-8') .
            "</pre>";
        echo "<p>Hint: remove ?debug=1 after fixing the issue.</p>";
        return;
    }

    echo "Application error. Please check your server error log and config.php settings.";
}

function app_register_shutdown_handler()
{
    if (defined('APP_SHUTDOWN_HANDLER_REGISTERED')) {
        return;
    }

    define('APP_SHUTDOWN_HANDLER_REGISTERED', true);
    ini_set('log_errors', '1');

    register_shutdown_function(function () {
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $details = "{$error['message']} in {$error['file']} on line {$error['line']}";
        error_log('Fatal application error: ' . $details);
        app_show_runtime_error($details);
    });
}

app_register_shutdown_handler();

// 优先使用 config.php 中的配置，如果没有则使用环境变量或默认值
$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

if (file_exists($configPath)) {
    require_once $configPath;

    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
        app_fail(
            "Database configuration is incomplete. Please define DB_HOST, DB_USER and DB_NAME in config.php.",
            "config.php is present but required DB constants are missing."
        );
    }

    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $user = defined('DB_USER') ? DB_USER : 'root';
    $pass = defined('DB_PASS') ? DB_PASS : '';
    $dbname = defined('DB_NAME') ? DB_NAME : 'warehouse_db';
} else {
    // 从环境变量读取配置，如果没有则使用默认值（适用于 XAMPP）
    // cPanel 用户可以在 cPanel 中设置环境变量，或直接修改下面的值
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $dbname = getenv('DB_NAME') ?: 'warehouse_db';
}

if (!class_exists('mysqli')) {
    app_fail(
        "MySQLi extension is not enabled on this server.",
        "Class mysqli not found. Enable mysqli in cPanel PHP extensions."
    );
}

// Disable automatic mysqli exceptions so app_* handlers can produce clearer messages.
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// 尝试连接数据库
$conn = new mysqli($host, $user, $pass, $dbname);

// 如果连接失败，显示友好的错误信息
if ($conn->connect_error) {
    $details = "Database connection failed: {$conn->connect_error} | host={$host} | user={$user} | database={$dbname}";

    if (app_is_development()) {
        app_fail(
            "数据库连接错误，请检查 config.php 中的配置。<br>主机: {$host}<br>用户: {$user}<br>数据库: {$dbname}",
            $details
        );
    }

    app_fail("Database connection error. Please check your database configuration in config.php", $details);
}

// 设置字符集为 UTF-8
$conn->set_charset("utf8mb4");

function app_query_or_fail(mysqli $conn, string $sql, string $context)
{
    $result = $conn->query($sql);

    if ($result === false) {
        app_fail(
            "Database query error. Please verify the warehouse tables were imported into the database selected in config.php.",
            "{$context} failed: {$conn->error} | SQL: {$sql}"
        );
    }

    return $result;
}

function app_find_user_by_username(mysqli $conn, string $username)
{
    $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");

    if (!$stmt) {
        app_fail(
            "Database query error. Please verify the users table exists in the selected database.",
            "Prepare failed in app_find_user_by_username: {$conn->error}"
        );
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        app_fail(
            "Database query error. Please verify the users table exists in the selected database.",
            "Execute failed in app_find_user_by_username: {$error}"
        );
    }

    $stmt->bind_result($id, $fetchedUsername, $passwordHash, $role);

    $user = null;
    if ($stmt->fetch()) {
        $user = [
            'id' => (int) $id,
            'username' => $fetchedUsername,
            'password_hash' => $passwordHash,
            'role' => $role,
        ];
    }

    $stmt->close();

    return $user;
}

function app_fetch_user_role(mysqli $conn, string $username, string $fallback = 'user')
{
    $stmt = $conn->prepare("SELECT role FROM users WHERE username = ? LIMIT 1");

    if (!$stmt) {
        error_log("Prepare failed in app_fetch_user_role: {$conn->error}");
        return $fallback;
    }

    $stmt->bind_param("s", $username);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        error_log("Execute failed in app_fetch_user_role: {$error}");
        return $fallback;
    }

    $stmt->bind_result($role);
    $resolvedRole = $fallback;

    if ($stmt->fetch() && $role) {
        $resolvedRole = $role;
    }

    $stmt->close();

    return $resolvedRole;
}

function app_table_exists(mysqli $conn, string $table)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");

    if (!$stmt) {
        error_log("Prepare failed in app_table_exists: {$conn->error}");
        return false;
    }

    $stmt->bind_param("s", $table);
    if (!$stmt->execute()) {
        error_log("Execute failed in app_table_exists: {$stmt->error}");
        $stmt->close();
        return false;
    }
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int) $count > 0;
}

function app_column_exists(mysqli $conn, string $table, string $column)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");

    if (!$stmt) {
        error_log("Prepare failed in app_column_exists: {$conn->error}");
        return false;
    }

    $stmt->bind_param("ss", $table, $column);
    if (!$stmt->execute()) {
        error_log("Execute failed in app_column_exists: {$stmt->error}");
        $stmt->close();
        return false;
    }
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int) $count > 0;
}

function app_require_warehouse_schema(mysqli $conn)
{
    $requiredSchema = [
        'users' => ['username', 'password_hash', 'role'],
        'items' => ['item_name', 'quantity', 'location', 'price'],
        'stock_movements' => ['item_id', 'movement_type', 'quantity', 'created_at'],
    ];

    $missingTables = [];
    $missingColumns = [];

    foreach ($requiredSchema as $table => $columns) {
        if (!app_table_exists($conn, $table)) {
            $missingTables[] = $table;
            continue;
        }

        foreach ($columns as $column) {
            if (!app_column_exists($conn, $table, $column)) {
                $missingColumns[] = "{$table}.{$column}";
            }
        }
    }

    if (!$missingTables && !$missingColumns) {
        return;
    }

    $details = [];
    if ($missingTables) {
        $details[] = 'Missing tables: ' . implode(', ', $missingTables);
    }
    if ($missingColumns) {
        $details[] = 'Missing columns: ' . implode(', ', $missingColumns);
    }

    $databaseName = defined('DB_NAME') ? DB_NAME : 'unknown';
    app_fail(
        "Database schema is incomplete for this warehouse application. Import db/init.sql into the database selected in config.php.",
        "Warehouse schema validation failed for database '{$databaseName}'. " . implode(' | ', $details)
    );
}


