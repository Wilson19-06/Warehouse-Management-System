<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ensure price column
$priceColumn = $conn->query("SHOW COLUMNS FROM items LIKE 'price'");
if (!$priceColumn || $priceColumn->num_rows === 0) {
    $conn->query("ALTER TABLE items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0");
}

app_require_warehouse_schema($conn);

// 获取角色
$currentUser = $_SESSION['username'] ?? '';
$userRole = app_fetch_user_role($conn, $currentUser, 'user');
$_SESSION['role'] = $userRole;

if ($userRole !== 'admin') {
    header("Location: items.php");
    exit();
}

// 处理添加movement（只有管理员）
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id']);
    $movement_type = $_POST['movement_type'];
    $quantity = intval($_POST['quantity']);
    $reference_note = $_POST['reference_note'] ?? '';

    // 检查OUT时库存是否足够
    if ($movement_type === 'OUT') {
        $item = $conn->query("SELECT quantity FROM items WHERE id = $item_id")->fetch_assoc();
        if ($item['quantity'] < $quantity) {
            $_SESSION['error'] = 'Insufficient stock!';
            header("Location: movement.php");
            exit();
        }
    }

    // 插入movement记录
    $stmt = $conn->prepare("INSERT INTO stock_movements (item_id, movement_type, quantity, reference_note) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $item_id, $movement_type, $quantity, $reference_note);
    $stmt->execute();

    // 更新items表的quantity
    $change = $movement_type === 'IN' ? $quantity : -$quantity;
    $conn->query("UPDATE items SET quantity = quantity + $change WHERE id = $item_id");

    header("Location: movement.php");
    exit();
}

// 检查 items 表是否存在 item_code 字段
$itemCodeColumn = $conn->query("SHOW COLUMNS FROM items LIKE 'item_code'");
$hasItemCode = $itemCodeColumn && $itemCodeColumn->num_rows > 0;
$itemCodeSelect = $hasItemCode ? "i.item_code" : "CONCAT('ITEM-', i.id)";

// 获取所有movements
$filter = $_GET['filter'] ?? '';
$query = "SELECT sm.*, i.item_name, i.price, $itemCodeSelect AS item_code 
           FROM stock_movements sm 
           JOIN items i ON sm.item_id = i.id 
           WHERE 1=1";
if ($filter === 'IN') {
    $query .= " AND sm.movement_type = 'IN'";
} elseif ($filter === 'OUT') {
    $query .= " AND sm.movement_type = 'OUT'";
}
$query .= " ORDER BY sm.created_at DESC";
$movements = $conn->query($query);

// 获取所有items用于下拉框（重新查询，因为可能被修改）
$itemsResult = $conn->query("SELECT * FROM items ORDER BY item_name");

// 显示错误消息
$error = '';
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

include 'header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger mb-3" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fee2e2; border-radius: 12px; padding: 12px;">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-arrow-left-right me-2"></i>Stock Movement</h2>
    </div>
</div>

<!-- Add Movement Form (Admin only) -->
<?php if ($userRole === 'admin'): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Add Stock Movement</h5>
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select" required>
                    <option value="">Select Item...</option>
                    <?php 
                    $itemsResult->data_seek(0); // 重置指针
                    while ($item = $itemsResult->fetch_assoc()): ?>
                        <option value="<?= $item['id'] ?>">
                            <?= htmlspecialchars($item['item_name']) ?> (Stock: <?= $item['quantity'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="movement_type" class="form-select" required>
                    <option value="IN">IN (进货)</option>
                    <option value="OUT">OUT (出货)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Reference Note</label>
                <input type="text" name="reference_note" class="form-control" placeholder="Optional">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-gradient w-100">
                    <i class="bi bi-plus-circle me-1"></i>Add
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Filter buttons -->
<div class="mb-3">
    <a href="movement.php" class="btn btn-sm <?= $filter === '' ? 'btn-gradient' : 'btn-outline-light' ?> me-2">
        All Movements
    </a>
    <a href="movement.php?filter=IN" class="btn btn-sm <?= $filter === 'IN' ? 'btn-gradient' : 'btn-outline-light' ?> me-2">
        <i class="bi bi-arrow-down-circle me-1"></i>IN (进货)
    </a>
    <a href="movement.php?filter=OUT" class="btn btn-sm <?= $filter === 'OUT' ? 'btn-gradient' : 'btn-outline-light' ?>">
        <i class="bi bi-arrow-up-circle me-1"></i>OUT (出货)
    </a>
</div>

<!-- Movements Table -->
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
                                    <?php if ($row['movement_type'] === 'OUT'): ?>
                                        <span class="text-success">$<?= number_format($row['quantity'] * $row['price'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">$<?= number_format($row['quantity'] * $row['price'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['reference_note'] ?: '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No movements found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

