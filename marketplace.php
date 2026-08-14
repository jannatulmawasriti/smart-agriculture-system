<?php
require_once "config.php";
require_once "includes/auth.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

$sql = "SELECT p.*, u.name AS farmer_name FROM products p JOIN users u ON p.farmer_id = u.id WHERE p.quantity > 0";
if ($search !== "") {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (p.name LIKE '%$safeSearch%' OR p.category LIKE '%$safeSearch%')";
}
if ($category !== "") {
    $safeCat = mysqli_real_escape_string($conn, $category);
    $sql .= " AND p.category = '$safeCat'";
}
$sql .= " ORDER BY p.created_at DESC";
$products = mysqli_query($conn, $sql);

$categories = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");

$pageTitle = "কৃষি বাজার";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">🛒 কৃষি বাজার</h2>
<form method="GET" action="marketplace.php" class="search-bar">
    <div class="input-with-voice" style="flex:1;">
        <input type="text" id="search" name="search" placeholder="পণ্য অথবা ক্যাটাগরি খুঁজুন..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="button" class="voice-btn" data-target="search" onclick="startVoiceInput('search')">🎤</button>
    </div>
    <select name="category">
        <option value="">সব ক্যাটাগরি</option>
        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
            <option value="<?php echo htmlspecialchars($c['category']); ?>" <?php echo $category === $c['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['category']); ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit" class="btn">খুঁজুন</button>
</form>
<div class="card-grid">
    <?php if (mysqli_num_rows($products) === 0): ?>
        <p>কোনো পণ্য পাওয়া যায়নি।</p>
    <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <div class="product-card">
            <img src="<?php echo $p['image'] ? htmlspecialchars($p['image']) : 'https://via.placeholder.com/300x160?text=কোনো+ছবি+নেই'; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
            <div class="info">
                <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                <p style="color:#777; font-size:0.9rem;">বিক্রেতা: <?php echo htmlspecialchars($p['farmer_name']); ?></p>
                <p class="price">৳ <?php echo number_format($p['price'], 2); ?> / একক</p>
                <p style="font-size:0.9rem; color:#555;">স্টক: <?php echo $p['quantity']; ?></p>
                <a href="product_details.php?id=<?php echo $p['id']; ?>" class="btn btn-small" style="margin-top:10px;">বিস্তারিত দেখুন</a>
            </div>
        </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
<?php include "includes/footer.php"; ?>
