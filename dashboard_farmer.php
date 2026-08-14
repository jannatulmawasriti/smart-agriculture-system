<?php
require_once "config.php";
require_once "includes/auth.php";

requireRole('farmer');

$uid = $_SESSION['user_id'];

$totalProducts = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE farmer_id=$uid")
)['c'];

$totalOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE farmer_id=$uid")
)['c'];

$pendingOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE farmer_id=$uid AND status='Pending'")
)['c'];

$completedOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE farmer_id=$uid AND status='Delivered'")
)['c'];

/* Cancelled Orders */
$cancelledOrders = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE farmer_id=$uid AND status='Cancelled'")
)['c'];

$totalEarnings = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE farmer_id=$uid AND status='Delivered'")
)['s'];

$pageTitle = "কৃষক ড্যাশবোর্ড";

include "includes/header.php";
?>

<h2 style="color:#2e7d32; margin-bottom:20px;">
    👨‍🌾 স্বাগতম, <?php echo htmlspecialchars($_SESSION['name']); ?>!
</h2>

<div class="card-grid">

    <div class="stat-card">
        <h3><?php echo $totalProducts; ?></h3>
        <p>মোট পণ্য</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $totalOrders; ?></h3>
        <p>মোট অর্ডার</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $pendingOrders; ?></h3>
        <p>অপেক্ষমাণ অর্ডার</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $completedOrders; ?></h3>
        <p>সম্পন্ন অর্ডার</p>
    </div>

    <div class="stat-card">
        <h3><?php echo $cancelledOrders; ?></h3>
        <p>বাতিল অর্ডার</p>
    </div>

    <div class="stat-card">
        <h3>৳<?php echo number_format($totalEarnings, 2); ?></h3>
        <p>মোট আয়</p>
    </div>

</div>

<div class="feature-grid" style="margin-top:30px;">

    <div class="feature-card">
        <div class="icon">📦</div>
        <h3>পণ্য ব্যবস্থাপনা</h3>
        <a href="my_products.php" class="btn">আমার পণ্য দেখুন</a>
    </div>

    <div class="feature-card">
        <div class="icon">📋</div>
        <h3>অর্ডার ব্যবস্থাপনা</h3>
        <a href="manage_orders.php" class="btn">অর্ডার দেখুন</a>
    </div>

    <div class="feature-card">
        <div class="icon">🧪</div>
        <h3>সার ও পানি ক্যালকুলেটর</h3>
        <a href="fertilizer_calculator.php" class="btn">ব্যবহার করুন</a>
    </div>

    <div class="feature-card">
        <div class="icon">🍃</div>
        <h3>রোগ শনাক্তকরণ</h3>
        <a href="disease_detection.php" class="btn">ছবি আপলোড করুন</a>
    </div>

</div>

<?php include "includes/footer.php"; ?>