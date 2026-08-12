<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$type = $_GET['type'] ?? 'item';
$id = intval($_GET['id'] ?? 0);
$redirect = $_GET['redirect'] ?? 'items.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    if ($type === 'item') {
        $conn->query("DELETE FROM items WHERE id = $id");
    }
    header("Location: $redirect");
    exit();
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-4">Confirm Delete</h4>
                <p class="mb-4">Are you sure you want to delete this <?= htmlspecialchars($type) ?>?</p>
                <form method="POST" class="d-inline">
                    <button type="submit" name="confirm" class="btn btn-gradient me-2">Yes, Delete</button>
                    <a href="<?= htmlspecialchars($redirect) ?>" class="btn btn-outline-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

