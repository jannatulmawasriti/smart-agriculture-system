<?php

require_once "config.php";
require_once "includes/auth.php";

requireRole('buyer');

$uid = $_SESSION['user_id'];

$totalOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE buyer_id=$uid")
)['c'];

$activeOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE buyer_id=$uid AND status IN ('Pending','Accepted','Processing')")
)['c'];

$completedOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE buyer_id=$uid AND status='Delivered'")
)['c'];

/* Cancelled Orders Count */
$cancelledOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE buyer_id=$uid AND status='Cancelled'")
)['c'];

$pageTitle = "ক্রেতা ড্যাশবোর্ড";

include "includes/header.php";

?>

<h2 style="color:#2e7d32; margin-bottom:20px;">
    🛍️ স্বাগতম,
    <?php echo htmlspecialchars($_SESSION['name']); ?>
    <span style="font-size:16px; color:#666;">(ক্রেতা)</span>!
</h2>

<div class="card-grid">

    <!-- Total Orders -->
    <div class="stat-card">
        <h3><?php echo $totalOrders; ?></h3>
        <p>মোট অর্ডার</p>
    </div>

    <!-- Active Orders -->
    <div class="stat-card">
        <h3><?php echo $activeOrders; ?></h3>
        <p>চলমান অর্ডার</p>
    </div>

    <!-- Completed Orders -->
    <div class="stat-card">
        <h3><?php echo $completedOrders; ?></h3>
        <p>সম্পন্ন অর্ডার</p>
    </div>

    <!-- Cancelled Orders -->
    <div class="stat-card">
        <h3><?php echo $cancelledOrders; ?></h3>
        <p>বাতিল অর্ডার</p>
    </div>

</div>

<div class="feature-grid" style="margin-top:30px;">

    <div class="feature-card">
        <div class="icon">🛒</div>
        <h3>বাজার দেখুন</h3>
        <a href="marketplace.php" class="btn">এখনই যান</a>
    </div>

    <div class="feature-card">
        <div class="icon">📦</div>
        <h3>অর্ডার হিস্টোরি</h3>
        <a href="my_orders.php" class="btn">দেখুন</a>
    </div>

    <div class="feature-card">
        <div class="icon">🧪</div>
        <h3>সার ও পানি ক্যালকুলেটর</h3>
        <a href="fertilizer_calculator.php" class="btn">ব্যবহার করুন</a>
    </div>

</div>

<?php include "includes/footer.php"; ?>