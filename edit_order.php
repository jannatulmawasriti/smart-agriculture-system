<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('buyer');

$uid = $_SESSION['user_id'];
$orderId = intval($_GET['id'] ?? $_POST['order_id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT o.*, p.name AS product_name, p.price AS product_price, p.quantity AS stock_quantity
    FROM orders o JOIN products p ON o.product_id = p.id
    WHERE o.id=? AND o.buyer_id=?");
mysqli_stmt_bind_param($stmt, "ii", $orderId, $uid);
mysqli_stmt_execute($stmt);
$order = mysqli_stmt_get_result($stmt)->fetch_assoc();

// অর্ডারটা এই ইউজারের না হলে, বা "Pending" ছাড়া অন্য কোনো অবস্থায় থাকলে এডিট করতে দেওয়া হবে না —
// কারণ বিক্রেতা একবার গ্রহণ/প্রসেস শুরু করে ফেললে ঠিকানা/পরিমাণ বদলানো আর নিরাপদ না।
if (!$order || $order['status'] !== 'Pending') {
    echo "<script>window.location.href='my_orders.php';</script>";
    exit();
}

// এডিট করার সময় স্টকে ফেরত হিসাব করার জন্য: বর্তমান অর্ডারের quantity বাদ দিয়ে যা স্টকে আছে + এই অর্ডারেরটা
$availableForEdit = $order['stock_quantity'] + $order['quantity'];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $quantity = intval($_POST['quantity']);
    $address = trim($_POST['delivery_address']);
    $lat = ($_POST['delivery_lat'] ?? '') !== '' ? floatval($_POST['delivery_lat']) : null;
    $lng = ($_POST['delivery_lng'] ?? '') !== '' ? floatval($_POST['delivery_lng']) : null;

    if ($quantity <= 0 || $quantity > $availableForEdit) {
        $error = "সঠিক পরিমাণ প্রবেশ করান। সর্বোচ্চ স্টক: " . $availableForEdit;
    } elseif ($address === "") {
        $error = "ডেলিভারি ঠিকানা দিতে হবে।";
    } else {
        $total = $quantity * $order['product_price'];
        $stmt2 = mysqli_prepare($conn, "UPDATE orders SET quantity=?, total_price=?, delivery_address=?, delivery_lat=?, delivery_lng=? WHERE id=? AND buyer_id=? AND status='Pending'");
        mysqli_stmt_bind_param($stmt2, "idsddii", $quantity, $total, $address, $lat, $lng, $orderId, $uid);
        if (mysqli_stmt_execute($stmt2)) {
            // স্টক আপডেট: পুরনো quantity ফেরত দিয়ে নতুন quantity বাদ
            $qtyDiff = $order['quantity'] - $quantity; // পজিটিভ হলে স্টকে যোগ হবে, নেগেটিভ হলে বিয়োগ হবে
            mysqli_query($conn, "UPDATE products SET quantity = quantity + $qtyDiff WHERE id = " . intval($order['product_id']));
            header("Location: my_orders.php");
            exit();
        } else {
            $error = "অর্ডার আপডেট করতে ব্যর্থ হয়েছে।";
        }
    }
}

$pageTitle = "অর্ডার সম্পাদনা করুন";
include "includes/header.php";
?>
<div class="form-box">
    <h2>✏️ অর্ডার সম্পাদনা করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <div class="card" style="margin-bottom:15px;">
        <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
        <p>মূল্য: ৳<?php echo number_format($order['product_price'], 2); ?> / একক</p>
        <p>সর্বোচ্চ পরিমাণ: <?php echo $availableForEdit; ?></p>
    </div>
    <form method="POST" action="edit_order.php?id=<?php echo $orderId; ?>">
        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
        <div class="form-group">
            <label>পরিমাণ</label>
            <input type="number" name="quantity" min="1" max="<?php echo $availableForEdit; ?>" value="<?php echo $order['quantity']; ?>" required>
        </div>
        <div class="form-group">
            <label>ডেলিভারি ঠিকানা</label>
            <div class="input-with-voice">
                <input type="text" id="delivery_address" name="delivery_address" value="<?php echo htmlspecialchars($order['delivery_address']); ?>" required>
                <button type="button" class="voice-btn" data-target="delivery_address" onclick="startVoiceInput('delivery_address')">🎤</button>
            </div>
            <div class="location-status" id="address-preview-status"></div>
            <div class="map-box" id="address-preview-map"></div>
            <input type="hidden" id="delivery_lat" name="delivery_lat" value="<?php echo htmlspecialchars($order['delivery_lat'] ?? ''); ?>">
            <input type="hidden" id="delivery_lng" name="delivery_lng" value="<?php echo htmlspecialchars($order['delivery_lng'] ?? ''); ?>">

            <label class="map-opt-in">
                <input type="checkbox" id="enable-map-location" <?php echo ($order['delivery_lat'] !== null) ? 'checked' : ''; ?>>
                ম্যাপ থেকে আমার সঠিক অবস্থান (lat/lng) যোগ/পরিবর্তন করতে চাই <span class="opt-in-hint">(ঐচ্ছিক)</span>
            </label>
            <p class="privacy-note">🔒 উপরের ম্যাপে শুধু আপনার লেখা ঠিকানা অনুযায়ী একটা আনুমানিক জায়গা দেখানো হয় (যাচাই করার জন্য) — এটি সংরক্ষিত হয় না। এই বক্সে টিক না দিলে শুধু আপনার লেখা ঠিকানাই সংরক্ষিত হবে।</p>

            <div id="map-location-section" style="display:<?php echo ($order['delivery_lat'] !== null) ? 'block' : 'none'; ?>;">
                <button type="button" id="use-location-btn" class="location-btn">📍 বর্তমান অবস্থান ব্যবহার করুন</button>
                <div class="location-status" id="location-status"></div>
                <div class="map-box" id="location-map"></div>
            </div>
        </div>
        <button type="submit" class="btn" style="width:100%;">✅ পরিবর্তন সংরক্ষণ করুন</button>
        <a href="my_orders.php" class="btn btn-small" style="width:100%; text-align:center; margin-top:10px; background:#888;">✖ বাতিল করে ফিরে যান</a>
    </form>
</div>
<script src="js/location.js"></script>
<script>
initAddressMapPreview({
    addressInputId: 'delivery_address',
    mapContainerId: 'address-preview-map',
    statusId: 'address-preview-status'
});

initAddressLocationPicker({
    buttonId: 'use-location-btn',
    statusId: 'location-status',
    addressInputId: 'delivery_address',
    latInputId: 'delivery_lat',
    lngInputId: 'delivery_lng',
    mapContainerId: 'location-map'
});

(function () {
    const toggle = document.getElementById('enable-map-location');
    const section = document.getElementById('map-location-section');
    const latInput = document.getElementById('delivery_lat');
    const lngInput = document.getElementById('delivery_lng');

    toggle.addEventListener('change', function () {
        if (toggle.checked) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
            latInput.value = '';
            lngInput.value = '';
        }
    });
})();
</script>
<?php include "includes/footer.php"; ?>