<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ensure price column exists (with error handling)
try {
    $priceColumn = $conn->query("SHOW COLUMNS FROM items LIKE 'price'");
    if (!$priceColumn || $priceColumn->num_rows === 0) {
        $conn->query("ALTER TABLE items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0");
    }
} catch (Exception $e) {
    // 如果表不存在或其他错误，记录但不中断执行
    error_log("Price column check failed: " . $e->getMessage());
}

app_require_warehouse_schema($conn);

// 获取角色（带错误处理）
$currentUser = $_SESSION['username'] ?? '';
$userRole = app_fetch_user_role($conn, $currentUser, 'user');
$_SESSION['role'] = $userRole;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($userRole !== 'admin') {
        header("Location: items.php");
        exit();
    }
    
    $id = $_POST['id'] ?? null;
    $item = $_POST['item_name'];
    $qty = $_POST['quantity'];
    $loc = $_POST['location'];
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    if (isset($_POST['save'])) {
        $stmt = $conn->prepare("INSERT INTO items (item_name, quantity, location, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisd", $item, $qty, $loc, $price);
    } elseif (isset($_POST['update'])) {
        $stmt = $conn->prepare("UPDATE items SET item_name=?, quantity=?, location=?, price=? WHERE id=?");
        $stmt->bind_param("sisdi", $item, $qty, $loc, $price, $id);
    }
    $stmt->execute();
    header("Location: items.php");
    exit();
}

if (isset($_GET['delete'])) {
    if ($userRole !== 'admin') {
        header("Location: items.php");
        exit();
    }
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM items WHERE id=$id");
    header("Location: items.php");
    exit();
}

$editItem = null;
if (isset($_GET['edit'])) {
    if ($userRole === 'admin') {
        $id = intval($_GET['edit']);
        $editItem = $conn->query("SELECT * FROM items WHERE id=$id")->fetch_assoc();
    }
}

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$query = "SELECT * FROM items WHERE 1=1";
if ($search) {
    $query .= " AND item_name LIKE '%$search%'";
}
if ($filter === 'lowstock') {
    $query .= " AND quantity < 10";
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);

include 'header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-box me-2"></i>Items Management</h2>
    </div>
</div>

<!-- Item form card - 只有管理员可以添加/编辑 -->
<?php if ($userRole === 'admin'): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3"><?= $editItem ? 'Edit Item' : 'Add New Item' ?></h5>
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?= $editItem['id'] ?? '' ?>">

            <div class="col-md-4">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-control"
                       placeholder="Item Name"
                       value="<?= $editItem['item_name'] ?? '' ?>" required>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control"
                       placeholder="Quantity"
                       value="<?= $editItem['quantity'] ?? '' ?>" required>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control"
                       placeholder="Location"
                       value="<?= $editItem['location'] ?? '' ?>">
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="price" class="form-control"
                       placeholder="Price"
                       value="<?= $editItem['price'] ?? '' ?>" required>
            </div>

            <div class="col-md-2 text-md-end">
                <button type="submit"
                        name="<?= $editItem ? 'update' : 'save' ?>"
                        class="btn btn-gradient w-100">
                    <?= $editItem ? 'Update' : 'Save' ?>
                </button>
                <?php if ($editItem): ?>
                    <a href="items.php" class="btn btn-outline-light w-100 mt-2">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i> 您当前是 <strong>普通用户</strong>，只能查看物品列表，无法添加或编辑。
    </div>
<?php endif; ?>

<!-- Search and Filter bar -->
<div class="card shadow-sm mb-4 search-card">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-lg-6">
                <div class="input-group modern-input">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-0"
                           placeholder="Search items by name..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <button class="btn btn-gradient rounded-pill px-4"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-auto">
                <a href="items.php?filter=lowstock" class="btn btn-gradient rounded-pill px-4 <?= $filter === 'lowstock' ? 'active-filter' : '' ?>">
                    <i class="bi bi-exclamation-triangle me-1"></i>Low Stock
                </a>
            </div>
            <?php if ($filter === 'lowstock' || $search): ?>
                <div class="col-auto">
                    <a href="items.php" class="btn btn-link text-decoration-none text-secondary">Clear Filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Items Table -->
<div class="card shadow-sm modern-table-card">
    <div class="card-body p-0">
        <div class="table-responsive modern-table">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Item Name</th>
                        <th style="width: 120px;">Quantity</th>
                                <th style="width: 140px;">Price</th>
                        <th>Location</th>
                        <th style="width: 160px;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-semibold text-muted"><?= $row['id'] ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold"><?= htmlspecialchars($row['item_name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['quantity'] < 10): ?>
                                        <span class="badge badge-qty bg-warning text-dark"><?= $row['quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-qty bg-success-subtle text-success"><?= $row['quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td class="text-center">
                                    <?php if ($userRole === 'admin'): ?>
                                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-soft-primary me-2">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </a>
                                        <a href="delete_confirm.php?type=item&id=<?= $row['id'] ?>&redirect=items.php"
                                           class="btn btn-sm btn-soft-danger">
                                            <i class="bi bi-trash3 me-1"></i>Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">只读</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

