<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('buyer');

$productId = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT p.*, u.name AS farmer_name FROM products p JOIN users u ON p.farmer_id=u.id WHERE p.id=?");
mysqli_stmt_bind_param($stmt, "i", $productId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);

if (!$product) {
    echo "<script>window.location.href='marketplace.php';</script>";
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $quantity = intval($_POST['quantity']);
    $address = trim($_POST['delivery_address']);
    $lat = ($_POST['delivery_lat'] ?? '') !== '' ? floatval($_POST['delivery_lat']) : null;
    $lng = ($_POST['delivery_lng'] ?? '') !== '' ? floatval($_POST['delivery_lng']) : null;
    $uid = $_SESSION['user_id'];

    if ($quantity <= 0 || $quantity > $product['quantity']) {
        $error = "সঠিক পরিমাণ প্রবেশ করান। সর্বোচ্চ স্টক: " . $product['quantity'];
    } elseif ($address === "") {
        $error = "ডেলিভারি ঠিকানা দিতে হবে।";
    } else {
        $total = $quantity * $product['price'];
        $stmt2 = mysqli_prepare($conn, "INSERT INTO orders (buyer_id, product_id, farmer_id, quantity, total_price, delivery_address, delivery_lat, delivery_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "iiiidsdd", $uid, $productId, $product['farmer_id'], $quantity, $total, $address, $lat, $lng);
        if (mysqli_stmt_execute($stmt2)) {
            $newQty = $product['quantity'] - $quantity;
            mysqli_query($conn, "UPDATE products SET quantity=$newQty WHERE id=$productId");
            header("Location: my_orders.php");
            exit();
        } else {
            $error = "অর্ডার সম্পন্ন করতে ব্যর্থ হয়েছে।";
        }
    }
}

$pageTitle = "অর্ডার করুন";
include "includes/header.php";
?>
<div class="form-box">
    <h2>🛒 অর্ডার নিশ্চিত করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <div class="card" style="margin-bottom:15px;">
        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
        <p>বিক্রেতা: <?php echo htmlspecialchars($product['farmer_name']); ?></p>
        <p>মূল্য: ৳<?php echo number_format($product['price'], 2); ?> / একক</p>
        <p>স্টক: <?php echo $product['quantity']; ?></p>
    </div>
    <form method="POST" action="place_order.php?product_id=<?php echo $productId; ?>">
        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
        <div class="form-group"><label>পরিমাণ</label><input type="number" name="quantity" min="1" max="<?php echo $product['quantity']; ?>" required></div>
        <div class="form-group">
            <label>ডেলিভারি ঠিকানা</label>
            <div class="input-with-voice">
                <input type="text" id="delivery_address" name="delivery_address" required>
                <button type="button" class="voice-btn" data-target="delivery_address" onclick="startVoiceInput('delivery_address')">🎤</button>
            </div>
            <div class="location-status" id="address-preview-status"></div>
            <div class="map-box" id="address-preview-map"></div>
            <input type="hidden" id="delivery_lat" name="delivery_lat">
            <input type="hidden" id="delivery_lng" name="delivery_lng">

            <label class="map-opt-in">
                <input type="checkbox" id="enable-map-location">
                ম্যাপ থেকে আমার সঠিক অবস্থান (lat/lng) যোগ করতে চাই <span class="opt-in-hint">(ঐচ্ছিক)</span>
            </label>
            <p class="privacy-note">🔒 উপরের ম্যাপে শুধু আপনার লেখা ঠিকানা অনুযায়ী একটা আনুমানিক জায়গা দেখানো হয় (যাচাই করার জন্য) — এটি সংরক্ষিত হয় না। এই বক্সে টিক না দিলে শুধু আপনার লেখা ঠিকানাই সংরক্ষিত হবে, কোনো সঠিক GPS-লোকেশন বিক্রেতার কাছে শেয়ার হবে না।</p>

            <div id="map-location-section" style="display:none;">
                <button type="button" id="use-location-btn" class="location-btn">📍 বর্তমান অবস্থান ব্যবহার করুন</button>
                <div class="location-status" id="location-status"></div>
                <div class="map-box" id="location-map"></div>
            </div>
        </div>
        <button type="submit" class="btn" style="width:100%;">অর্ডার নিশ্চিত করুন</button>
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

// ডিফল্টে ম্যাপ-লোকেশন বন্ধ — টিক দিলে তবেই বাটন/ম্যাপ দেখা যাবে ও lat/lng সেভ হবে।
// টিক তুলে ফেললে আগে যোগ করা lat/lng-ও মুছে যাবে, যাতে ভুলবশত অবস্থান সেভ না হয়।
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
