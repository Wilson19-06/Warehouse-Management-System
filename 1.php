<?php 
session_start();

$conn = new mysqli("localhost", "root", "", "warehouse_db");
if ($conn->connect_error) die("DB Connection failed:" . $conn->connect_error);
    
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $item = $_POST['item_name'];
        $qty = $_POST['quantity'];
        $loc = $_POST['location'];

        if (isset($_POST['save'])) {
            $stmt = $conn->prepare("INSERT INTO items (item_name, quantity, location) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $item, $qty, $loc);
        } elseif (isset($_POST['update'])) {
            $stmt = $conn->prepare("UPDATE items SET item_name=?, quantity=?, location=? WHERE id=?");
            $stmt->bind_param("sisi", $item, $qty, $loc, $id);
        }
        $stmt->execute();
        header("Location: index.php");
        exit();
    }

    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $conn->query("DELETE FROM items WHERE id=$id");
        header("Location: index.php");
        exit();
    }
 ?>
 <!DOCTYPE html>
 <html>
 <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warehouse</title>
    <link rel="stylesheet" type="text/css" href="style.css">
 </head>
<div class="container mt-4">

    <h2>Warehouse Management</h2>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <span class="me-3">Welcome,<?= $_SESSION['username'] ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
    <?php 
    $editItem =null;
    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $editItem = $conn->query("SELECT * FROM items WHERE id=$id")->fetch_assoc();
    }
    ?>
    <form method="POST" class="card p-3 mb-3">
        <h5><?= $editItem ? "Edit item": "Add NEW Item" ?></h5>
        <input type="hidden" name="id" value="<?= $editItem['id'] ??''?> " >
        <input type="text" name="item_name" class="form-control mb-2" placeholder="Item Name"
        value="<?= $editItem['item_name'] ??''?> " required>
        <input type="number" name="quantity" class="form-control mb-2" placeholder="Quantity"
        value="<?= $editItem['quantity'] ??''?> " required>
        <input type="text" name="location" class="form-control mb-2" placeholder="Location"
        value="<?= $editItem['location'] ??''?> ">
        <button class="submit" name="<?= $editItem ? 'update': 'save' ?>" class="btn btn-<?= $editItem ? 'primary':'success'?> " >
            <?= $editItem ? 'Update': 'Save' ?>
        </button>
        <?php if ($editItem): ?><a href="index.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </form>
    
    <form method="GET" class="d-flex mb-3">
        <input type="text" name="search" class="form-control mb-2" placeholder="Search Items..." value="<?= $_GET['search'] ??''?> ">
        <button class="btn btn-info">Search</button>
    </form>

    <?php 
    $search = $_GET['search'] ??'';
    $result = $conn->query("SELECT * FROM items WHERE item_name LIKE '%$search%' ORDER BY id DESC ");
     ?>
     <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr><th>ID</th><th>Item Name</th><th>Quantity</th><th>Location</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows): while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?> </td>
                <td><?= $row['item_name'] ?> </td>
                <td><?= $row['quantity'] ?> </td>
                <td><?= $row['location'] ?> </td>
                <td>                
                    <a href="?edit=<?= $row['id'] ?> " class="btn btn-danger btn-sm">Edit</a>
                    <a href="delete_confirm.php?type=item&id=<?= $row['id'] ?>&redirect=1.php" class="btn btn-warning btn-sm">Delete</a>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="5" class="text-center">No Items Found</td></tr>
        <?php endif ?>
        </tbody>        
     </table>
</div> 
 </body>
 </html>