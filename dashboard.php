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

// 获取统计数据
$totalItems = app_query_or_fail($conn, "SELECT COUNT(*) as count FROM items", "Dashboard total items query")->fetch_assoc()['count'];
$lowStockItems = app_query_or_fail($conn, "SELECT COUNT(*) as count FROM items WHERE quantity < 10", "Dashboard low stock query")->fetch_assoc()['count'];
$totalMovements = app_query_or_fail($conn, "SELECT COUNT(*) as count FROM stock_movements", "Dashboard total movements query")->fetch_assoc()['count'];
$salesTodayResult = app_query_or_fail($conn, "SELECT SUM(sm.quantity * i.price) as total 
                                  FROM stock_movements sm 
                                  JOIN items i ON sm.item_id = i.id 
                                  WHERE sm.movement_type = 'OUT' 
                                    AND DATE(sm.created_at) = CURDATE()", "Dashboard sales today query");
$totalSalesToday = $salesTodayResult->fetch_assoc()['total'] ?? 0;

// 获取低库存物品列表
$lowStockList = app_query_or_fail($conn, "SELECT * FROM items WHERE quantity < 10 ORDER BY quantity ASC LIMIT 5", "Dashboard low stock list query");

// 最近三天进出货
$recentMovements = app_query_or_fail($conn, "SELECT DATE(created_at) AS movement_date,
    SUM(CASE WHEN movement_type = 'IN' THEN quantity ELSE 0 END) AS total_in,
    SUM(CASE WHEN movement_type = 'OUT' THEN quantity ELSE 0 END) AS total_out
    FROM stock_movements
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
    GROUP BY DATE(created_at)
    ORDER BY movement_date DESC", "Dashboard recent movements query");

include 'header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Items Card -->
    <div class="col-md-4">
        <a href="items.php" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 stat-card" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Items</h6>
                        <h2 class="mb-0 text-primary"><?= $totalItems ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="bi bi-box text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>

    <!-- Low Stock Items Card -->
    <div class="col-md-4">
        <a href="items.php?filter=lowstock" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 stat-card" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Low Stock Items</h6>
                        <h2 class="mb-0 text-warning"><?= $lowStockItems ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>

    <!-- Today's Sales Card -->
    <div class="col-md-4">
        <a href="report.php" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 stat-card" style="cursor: pointer;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Today's Sales (OUT)</h6>
                        <h2 class="mb-0 text-success"><?= number_format($totalSalesToday) ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="bi bi-lightning-charge text-success" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        </a>
    </div>
</div>

<!-- Low Stock Alert -->
<?php if ($lowStockItems > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Low Stock Alert</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Current Quantity</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $lowStockList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= $row['quantity'] ?></span></td>
                                    <td><?= htmlspecialchars($row['location']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent 3-day movements -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0 text-light">Recent 3-Day Movements</h5>
            </div>
            <div class="card-body pb-1">
                <div class="table-responsive modern-table">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total IN</th>
                                <th>Total OUT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentMovements->num_rows > 0): ?>
                                <?php while ($row = $recentMovements->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['movement_date'] ?></td>
                                        <td><span class="badge badge-qty bg-success-subtle text-success"><?= $row['total_in'] ?></span></td>
                                        <td><span class="badge badge-qty bg-danger"><?= $row['total_out'] ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

