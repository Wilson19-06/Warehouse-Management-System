<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['username'];

// Ensure price column
$priceColumn = $conn->query("SHOW COLUMNS FROM items LIKE 'price'");
if (!$priceColumn || $priceColumn->num_rows === 0) {
    $conn->query("ALTER TABLE items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0");
}

app_require_warehouse_schema($conn);

$userRole = app_fetch_user_role($conn, $currentUser, 'user');
$_SESSION['role'] = $userRole;

if ($userRole !== 'admin') {
    header("Location: items.php");
    exit();
}

// 获取日期范围过滤
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$item_filter = $_GET['item_id'] ?? '';

// 检查 items 表是否存在 item_code 字段
$itemCodeColumn = $conn->query("SHOW COLUMNS FROM items LIKE 'item_code'");
$hasItemCode = $itemCodeColumn && $itemCodeColumn->num_rows > 0;
$itemCodeSelect = $hasItemCode ? "i.item_code" : "CONCAT('ITEM-', i.id)";

// 构建查询
$query = "SELECT sm.*, i.item_name, i.price, $itemCodeSelect AS item_code 
          FROM stock_movements sm 
          JOIN items i ON sm.item_id = i.id 
          WHERE 1=1";

if ($date_from) {
    $query .= " AND DATE(sm.created_at) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(sm.created_at) <= '$date_to'";
}
if ($item_filter) {
    $query .= " AND sm.item_id = $item_filter";
}

$query .= " ORDER BY sm.created_at DESC";
$movements = $conn->query($query);

// 计算统计（受筛选影响）
$statsQuery = "SELECT 
    SUM(CASE WHEN sm.movement_type = 'IN' THEN sm.quantity ELSE 0 END) AS total_in,
    SUM(CASE WHEN sm.movement_type = 'OUT' THEN sm.quantity ELSE 0 END) AS total_out,
    SUM(CASE WHEN sm.movement_type = 'OUT' THEN sm.quantity * i.price ELSE 0 END) AS revenue
    FROM stock_movements sm
    JOIN items i ON sm.item_id = i.id
    WHERE 1=1";

if ($date_from) $statsQuery .= " AND DATE(sm.created_at) >= '$date_from'";
if ($date_to) $statsQuery .= " AND DATE(sm.created_at) <= '$date_to'";
if ($item_filter) $statsQuery .= " AND sm.item_id = $item_filter";

$stats = $conn->query($statsQuery)->fetch_assoc();
$totalIn = $stats['total_in'] ?? 0;
$totalOut = $stats['total_out'] ?? 0;
$totalRevenue = $stats['revenue'] ?? 0;

// 获取所有items用于过滤
$items = $conn->query("SELECT * FROM items ORDER BY item_name");

include 'header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-graph-up me-2"></i>Sales Report</h2>
    </div>
</div>

<!-- Statistics Summary -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total IN (Units)</h6>
                <h3 class="mb-0 text-success"><?= number_format($totalIn) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total OUT (Units)</h6>
                <h3 class="mb-0 text-danger"><?= number_format($totalOut) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted mb-2">Sales Revenue</h6>
                <h3 class="mb-0 text-warning">$<?= number_format($totalRevenue, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Filters</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select">
                    <option value="">All Items</option>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <option value="<?= $item['id'] ?>" <?= $item_filter == $item['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['item_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-gradient w-100">
                    <i class="bi bi-funnel me-1"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card shadow-sm modern-table-card">
    <div class="card-body p-0">
        <div class="table-responsive modern-table">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total Value</th>
                        <th>Reference Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($movements->num_rows > 0): ?>
                        <?php while ($row = $movements->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['item_code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['item_name']) ?></td>
                                <td>
                                    <?php if ($row['movement_type'] === 'IN'): ?>
                                        <span class="badge bg-success"><i class="bi bi-arrow-down-circle me-1"></i>IN</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-arrow-up-circle me-1"></i>OUT</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td>
                                    <?php if ($row['movement_type'] === 'IN'): ?>
                                        <span class="text-muted">$<?= number_format($row['quantity'] * $row['price'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">$<?= number_format($row['quantity'] * $row['price'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['reference_note'] ?: '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

