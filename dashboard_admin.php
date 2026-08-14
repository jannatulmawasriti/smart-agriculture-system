<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$totalFarmers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='farmer'"))['c'];
$totalBuyers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='buyer'"))['c'];
$totalProducts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products"))['c'];
$totalOrders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$diseaseRecords = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM disease_predictions"))['c'];
$fertilizerRecords = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM fertilizer_calculations"))['c'];

$pageTitle = "অ্যাডমিন ড্যাশবোর্ড";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:20px;">🛠️ অ্যাডমিন প্যানেল</h2>
<div class="card-grid">
    <div class="stat-card"><h3><?php echo $totalUsers; ?></h3><p>মোট ইউজার</p></div>
    <div class="stat-card"><h3><?php echo $totalFarmers; ?></h3><p>মোট কৃষক</p></div>
    <div class="stat-card"><h3><?php echo $totalBuyers; ?></h3><p>মোট ক্রেতা</p></div>
    <div class="stat-card"><h3><?php echo $totalProducts; ?></h3><p>মোট পণ্য</p></div>
    <div class="stat-card"><h3><?php echo $totalOrders; ?></h3><p>মোট অর্ডার</p></div>
    <div class="stat-card"><h3><?php echo $diseaseRecords; ?></h3><p>রোগ শনাক্তকরণ রেকর্ড</p></div>
    <div class="stat-card"><h3><?php echo $fertilizerRecords; ?></h3><p>সার হিসাব রেকর্ড</p></div>
</div>
<div class="feature-grid" style="margin-top:30px;">
    <div class="feature-card"><div class="icon">👥</div><h3>ইউজার ব্যবস্থাপনা</h3><a href="admin_users.php" class="btn">ইউজার দেখুন</a></div>
    <div class="feature-card"><div class="icon">📦</div><h3>পণ্য ব্যবস্থাপনা</h3><a href="admin_products.php" class="btn">পণ্য দেখুন</a></div>
    <div class="feature-card"><div class="icon">📋</div><h3>অর্ডার ব্যবস্থাপনা</h3><a href="admin_orders.php" class="btn">অর্ডার দেখুন</a></div>
    <div class="feature-card"><div class="icon">🩺</div><h3>রোগ শনাক্তকরণ রেকর্ড ব্যবস্থাপনা</h3><a href="admin_disease_records.php" class="btn">রেকর্ড দেখুন</a></div>
    <div class="feature-card"><div class="icon">🌱</div><h3>সার হিসাব রেকর্ড ব্যবস্থাপনা</h3><a href="admin_fertilizer_records.php" class="btn">রেকর্ড দেখুন</a></div>
</div>
<?php include "includes/footer.php"; ?>