<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    $allowed = ['Pending', 'Accepted', 'Processing', 'Delivered', 'Cancelled'];
    if (in_array($newStatus, $allowed)) {
        $curStmt = mysqli_prepare($conn, "SELECT status, product_id, quantity FROM orders WHERE id=?");
        mysqli_stmt_bind_param($curStmt, "i", $orderId);
        mysqli_stmt_execute($curStmt);
        $curRes = mysqli_stmt_get_result($curStmt);
        $curOrder = mysqli_fetch_assoc($curRes);

        if ($curOrder) {
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
            mysqli_stmt_execute($stmt);

            if ($newStatus === 'Cancelled' && $curOrder['status'] !== 'Cancelled') {
                $restoreStmt = mysqli_prepare($conn, "UPDATE products SET quantity = quantity + ? WHERE id=?");
                mysqli_stmt_bind_param($restoreStmt, "ii", $curOrder['quantity'], $curOrder['product_id']);
                mysqli_stmt_execute($restoreStmt);
            } elseif ($newStatus !== 'Cancelled' && $curOrder['status'] === 'Cancelled') {
                $deductStmt = mysqli_prepare($conn, "UPDATE products SET quantity = quantity - ? WHERE id=?");
                mysqli_stmt_bind_param($deductStmt, "ii", $curOrder['quantity'], $curOrder['product_id']);
                mysqli_stmt_execute($deductStmt);
            }
        }
    }
    header("Location: admin_orders.php");
    exit();
}

$orders = mysqli_query($conn, "SELECT o.*, p.name AS product_name, b.name AS buyer_name, f.name AS farmer_name FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users b ON o.buyer_id = b.id
    JOIN users f ON o.farmer_id = f.id
    ORDER BY o.created_at DESC");

$statusBn = ['Pending' => 'অপেক্ষমাণ', 'Accepted' => 'গৃহীত', 'Processing' => 'প্রক্রিয়াধীন', 'Delivered' => 'পৌঁছে দেওয়া হয়েছে', 'Cancelled' => 'বাতিল'];

$pageTitle = "অর্ডার ব্যবস্থাপনা (অ্যাডমিন)";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">📋 সকল অর্ডার (অ্যাডমিন)</h2>
<div class="card">
<table>
    <tr><th>ক্রেতা</th><th>কৃষক</th><th>পণ্য</th><th>পরিমাণ</th><th>মূল্য</th><th>অবস্থা</th><th>পরিবর্তন</th></tr>
    <?php if (mysqli_num_rows($orders) === 0): ?>
        <tr><td colspan="7">কোনো অর্ডার নেই।</td></tr>
    <?php else: ?>
        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
        <tr>
            <td><?php echo htmlspecialchars($o['buyer_name']); ?></td>
            <td><?php echo htmlspecialchars($o['farmer_name']); ?></td>
            <td><?php echo htmlspecialchars($o['product_name']); ?></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>৳<?php echo number_format($o['total_price'], 2); ?></td>
            <td><span class="badge badge-<?php echo $o['status']; ?>"><?php echo $statusBn[$o['status']]; ?></span></td>
            <td>
                <form method="POST" action="admin_orders.php" style="display:flex; gap:6px;">
                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                    <select name="status">
                        <?php foreach ($statusBn as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $o['status'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-small">আপডেট</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>
