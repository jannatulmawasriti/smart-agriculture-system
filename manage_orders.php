<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('farmer');

$uid = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    $allowed = ['Pending', 'Accepted', 'Processing', 'Delivered', 'Cancelled'];
    if (in_array($newStatus, $allowed)) {
        // অর্ডারের বর্তমান status ও quantity/product_id আগে থেকে জেনে নেওয়া হচ্ছে,
        // যাতে "Cancelled" করলে স্টক ফেরত দেওয়া যায় (একবারই, বারবার না)।
        $curStmt = mysqli_prepare($conn, "SELECT status, product_id, quantity FROM orders WHERE id=? AND farmer_id=?");
        mysqli_stmt_bind_param($curStmt, "ii", $orderId, $uid);
        mysqli_stmt_execute($curStmt);
        $curRes = mysqli_stmt_get_result($curStmt);
        $curOrder = mysqli_fetch_assoc($curRes);

        if ($curOrder) {
            $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=? AND farmer_id=?");
            mysqli_stmt_bind_param($stmt, "sii", $newStatus, $orderId, $uid);
            mysqli_stmt_execute($stmt);

            // আগে Cancelled ছিল না, এখন Cancelled হলো -> স্টক ফেরত দাও
            if ($newStatus === 'Cancelled' && $curOrder['status'] !== 'Cancelled') {
                $restoreStmt = mysqli_prepare($conn, "UPDATE products SET quantity = quantity + ? WHERE id=?");
                mysqli_stmt_bind_param($restoreStmt, "ii", $curOrder['quantity'], $curOrder['product_id']);
                mysqli_stmt_execute($restoreStmt);
            }
            // আগে Cancelled ছিল, এখন অন্য status-এ ফিরিয়ে আনা হলো -> স্টক আবার বিয়োগ করো
            elseif ($newStatus !== 'Cancelled' && $curOrder['status'] === 'Cancelled') {
                $deductStmt = mysqli_prepare($conn, "UPDATE products SET quantity = quantity - ? WHERE id=?");
                mysqli_stmt_bind_param($deductStmt, "ii", $curOrder['quantity'], $curOrder['product_id']);
                mysqli_stmt_execute($deductStmt);
            }
        }
    }
    header("Location: manage_orders.php");
    exit();
}

$orders = mysqli_query($conn, "SELECT o.*, p.name AS product_name, u.name AS buyer_name, u.phone AS buyer_phone FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.farmer_id=$uid ORDER BY o.created_at DESC");

$statusBn = ['Pending' => 'অপেক্ষমাণ', 'Accepted' => 'গৃহীত', 'Processing' => 'প্রক্রিয়াধীন', 'Delivered' => 'পৌঁছে দেওয়া হয়েছে', 'Cancelled' => 'বাতিল'];

$pageTitle = "অর্ডার ব্যবস্থাপনা";
include "includes/header.php";
?>
<script src="js/location.js"></script>
<h2 style="color:#2e7d32; margin-bottom:15px;">📋 অর্ডার ব্যবস্থাপনা</h2>
<div class="card">
<table>
    <tr><th>ক্রেতা</th><th>পণ্য</th><th>পরিমাণ</th><th>মোট মূল্য</th><th>ঠিকানা</th><th>বর্তমান অবস্থা</th><th>স্ট্যাটাস পরিবর্তন</th><th>লাইভ লোকেশন</th></tr>
    <?php if (mysqli_num_rows($orders) === 0): ?>
        <tr><td colspan="8">এখনো কোনো অর্ডার আসেনি।</td></tr>
    <?php else: ?>
        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
        <tr>
            <td><?php echo htmlspecialchars($o['buyer_name']); ?><br><small><?php echo htmlspecialchars($o['buyer_phone']); ?></small></td>
            <td><?php echo htmlspecialchars($o['product_name']); ?></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>৳<?php echo number_format($o['total_price'], 2); ?></td>
            <td>
                <?php echo htmlspecialchars($o['delivery_address']); ?>
                <?php if ($o['delivery_lat'] && $o['delivery_lng']): ?>
                    <br><a href="https://www.openstreetmap.org/?mlat=<?php echo $o['delivery_lat']; ?>&mlon=<?php echo $o['delivery_lng']; ?>#map=16/<?php echo $o['delivery_lat']; ?>/<?php echo $o['delivery_lng']; ?>" target="_blank" rel="noopener" style="font-size:0.85rem; color:#1565c0;">🗺️ ম্যাপে দেখুন</a>
                <?php endif; ?>
            </td>
            <td><span class="badge badge-<?php echo $o['status']; ?>"><?php echo $statusBn[$o['status']]; ?></span></td>
            <td>
                <form method="POST" action="manage_orders.php" style="display:flex; gap:6px;">
                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                    <select name="status">
                        <?php foreach ($statusBn as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $o['status'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-small">আপডেট</button>
                </form>
            </td>
            <td>
                <?php if (in_array($o['status'], ['Accepted', 'Processing'])): ?>
                    <button type="button" class="location-btn btn-small" id="share-loc-btn-<?php echo $o['id']; ?>">📍 লাইভ লোকেশন শেয়ার করুন</button>
                    <div class="location-status" id="share-loc-status-<?php echo $o['id']; ?>"></div>
                    <script>
                        initLocationSharing({
                            orderId: <?php echo $o['id']; ?>,
                            buttonId: 'share-loc-btn-<?php echo $o['id']; ?>',
                            statusId: 'share-loc-status-<?php echo $o['id']; ?>'
                        });
                    </script>
                <?php else: ?>
                    <span style="color:#999; font-size:0.85rem;">প্রযোজ্য নয়</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<p style="color:#666; font-size:0.9rem; margin-top:10px;">💡 "লাইভ লোকেশন শেয়ার করুন" চাপলে ডেলিভারি চলাকালীন আপনার বর্তমান অবস্থান ক্রেতা তার "আমার অর্ডারসমূহ" পেজ থেকে লাইভ দেখতে পাবেন। ডেলিভারি শেষে বন্ধ করে দিন।</p>
<?php include "includes/footer.php"; ?>
