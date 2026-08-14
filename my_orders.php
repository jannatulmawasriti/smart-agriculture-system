<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('buyer');

$uid = $_SESSION['user_id'];
$orders = mysqli_query($conn, "SELECT o.*, p.name AS product_name, u.name AS farmer_name, u.phone AS farmer_phone FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.farmer_id = u.id
    WHERE o.buyer_id=$uid ORDER BY o.created_at DESC");

$statusBn = ['Pending' => 'অপেক্ষমাণ', 'Accepted' => 'গৃহীত', 'Processing' => 'প্রক্রিয়াধীন', 'Delivered' => 'পৌঁছে দেওয়া হয়েছে', 'Cancelled' => 'বাতিল'];
$flow = ['Pending', 'Accepted', 'Processing', 'Delivered'];

$pageTitle = "আমার অর্ডারসমূহ";
include "includes/header.php";
?>
<script src="js/location.js"></script>
<script>const STATUS_BN = <?php echo json_encode($statusBn, JSON_UNESCAPED_UNICODE); ?>;</script>
<h2 style="color:#2e7d32; margin-bottom:15px;">📦 আমার অর্ডারসমূহ</h2>
<div class="card">
<table>
    <tr><th>পণ্য</th><th>বিক্রেতা</th><th>পরিমাণ</th><th>মোট মূল্য</th><th>ঠিকানা</th><th>অবস্থা</th><th>তারিখ</th><th>ট্র্যাকিং</th></tr>
    <?php if (mysqli_num_rows($orders) === 0): ?>
        <tr><td colspan="8">আপনার কোনো অর্ডার নেই।</td></tr>
    <?php else: ?>
        <?php while ($o = mysqli_fetch_assoc($orders)): ?>
        <tr>
            <td><?php echo htmlspecialchars($o['product_name']); ?></td>
            <td><?php echo htmlspecialchars($o['farmer_name']); ?><br><small><?php echo htmlspecialchars($o['farmer_phone']); ?></small></td>
            <td><?php echo $o['quantity']; ?></td>
            <td>৳<?php echo number_format($o['total_price'], 2); ?></td>
            <td><?php echo htmlspecialchars($o['delivery_address']); ?></td>
            <td>
                <span class="badge badge-<?php echo $o['status']; ?>" id="status-badge-<?php echo $o['id']; ?>"><?php echo $statusBn[$o['status']]; ?></span>
                <?php if ($o['status'] === 'Processing'): ?><span class="live-badge"><span class="live-dot"></span>লাইভ</span><?php endif; ?>
            </td>
            <td><?php echo date("d M Y", strtotime($o['created_at'])); ?></td>
            <td>
                <?php if ($o['status'] !== 'Cancelled'): ?>
                    <button type="button" class="btn btn-small tracking-btn" onclick="toggleTracking(<?php echo $o['id']; ?>)">🚚 ট্র্যাকিং দেখুন</button>
                <?php else: ?>
                    <span style="color:#999; font-size:0.85rem;">বাতিল হয়েছে</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($o['status'] !== 'Cancelled'): ?>
        <tr id="tracking-row-<?php echo $o['id']; ?>" style="display:none;">
            <td colspan="8">
                <div class="tracking-panel">
                    <div class="tracking-panel-head">
                        <span class="tracking-panel-title">🚚 ডেলিভারি ট্র্যাকিং</span>
                        <span class="live-badge"><span class="live-dot"></span>লাইভ</span>
                    </div>
                    <div class="status-steps">
                        <?php
                        $currentIdx = array_search($o['status'], $flow);
                        foreach ($flow as $i => $step):
                            $cls = $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'current' : '');
                        ?>
                            <div class="status-step <?php echo $cls; ?>">
                                <span class="status-step-dot"></span>
                                <span class="status-step-label"><?php echo $statusBn[$step]; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="map-box" id="tracking-map-<?php echo $o['id']; ?>"></div>
                    <div class="location-name-chip" id="tracking-location-<?php echo $o['id']; ?>">📍 জায়গার নাম লোড হচ্ছে...</div>
                    <div class="updated-at" id="tracking-updated-<?php echo $o['id']; ?>">লোকেশন লোড হচ্ছে...</div>
                </div>
                <script>
                    window.__trackingMaps = window.__trackingMaps || {};
                    window.__trackingMaps[<?php echo $o['id']; ?>] = initOrderTrackingMap({
                        mapContainerId: 'tracking-map-<?php echo $o['id']; ?>',
                        updatedAtId: 'tracking-updated-<?php echo $o['id']; ?>',
                        locationNameId: 'tracking-location-<?php echo $o['id']; ?>',
                        pollUrl: 'get_order_location.php?order_id=<?php echo $o['id']; ?>',
                        onStatus: function (status) {
                            var badge = document.getElementById('status-badge-<?php echo $o['id']; ?>');
                            if (badge && status) {
                                badge.className = 'badge badge-' + status;
                                badge.innerText = STATUS_BN[status] || status;
                            }
                        }
                    });
                </script>
            </td>
        </tr>
        <?php endif; ?>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<script>
function toggleTracking(orderId) {
    const row = document.getElementById('tracking-row-' + orderId);
    if (row) {
        const willShow = row.style.display === 'none';
        row.style.display = willShow ? 'table-row' : 'none';
        // এখন row visible হলো, তাই Leaflet map-কে বলে দিচ্ছি সাইজ আবার হিসাব করতে —
        // নাহলে আগে display:none অবস্থায় বানানো map ভাঙা/ফাঁকা থেকে যায়।
        if (willShow && window.__trackingMaps && window.__trackingMaps[orderId]) {
            window.__trackingMaps[orderId].refresh();
        }
    }
}
</script>
<?php include "includes/footer.php"; ?>
