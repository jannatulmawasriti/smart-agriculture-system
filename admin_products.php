<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    header("Location: admin_products.php");
    exit();
}

$products = mysqli_query($conn, "SELECT p.*, u.name AS farmer_name FROM products p JOIN users u ON p.farmer_id=u.id ORDER BY p.created_at DESC");

$pageTitle = "পণ্য ব্যবস্থাপনা";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">📦 পণ্য ব্যবস্থাপনা (অ্যাডমিন)</h2>
<div class="card">
<table>
    <tr><th>নাম</th><th>বিক্রেতা</th><th>ক্যাটাগরি</th><th>মূল্য</th><th>স্টক</th><th>কার্যক্রম</th></tr>
    <?php if (mysqli_num_rows($products) === 0): ?>
        <tr><td colspan="6">কোনো পণ্য নেই।</td></tr>
    <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['farmer_name']); ?></td>
            <td><?php echo htmlspecialchars($p['category']); ?></td>
            <td>৳<?php echo number_format($p['price'], 2); ?></td>
            <td><?php echo $p['quantity']; ?></td>
            <td><a href="admin_products.php?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('মুছে ফেলতে চান?');">মুছুন</a></td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>
