<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('farmer');

$uid = $_SESSION['user_id'];
$products = mysqli_query($conn, "SELECT * FROM products WHERE farmer_id=$uid ORDER BY created_at DESC");

$pageTitle = "আমার পণ্যসমূহ";
include "includes/header.php";
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <h2 style="color:#2e7d32;">📦 আমার পণ্যসমূহ</h2>
    <a href="add_product.php" class="btn">➕ নতুন পণ্য যুক্ত করুন</a>
</div>
<div class="card">
<table>
    <tr><th>ছবি</th><th>নাম</th><th>ক্যাটাগরি</th><th>মূল্য</th><th>স্টক</th><th>কার্যক্রম</th></tr>
    <?php if (mysqli_num_rows($products) === 0): ?>
        <tr><td colspan="6">আপনার কোনো পণ্য নেই।</td></tr>
    <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <tr>
            <td><img src="<?php echo $p['image'] ? htmlspecialchars($p['image']) : 'https://via.placeholder.com/60'; ?>" width="50" style="border-radius:6px;"></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['category']); ?></td>
            <td>৳<?php echo number_format($p['price'], 2); ?></td>
            <td><?php echo $p['quantity']; ?></td>
            <td>
                <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-small btn-secondary">সম্পাদনা</a>
                <a href="delete_product.php?id=<?php echo $p['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('আপনি কি নিশ্চিত এই পণ্য মুছে ফেলতে চান?');">মুছুন</a>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>
