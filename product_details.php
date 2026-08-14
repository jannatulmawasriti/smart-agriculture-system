<?php
require_once "config.php";
require_once "includes/auth.php";

$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT p.*, u.name AS farmer_name, u.phone AS farmer_phone 
     FROM products p 
     JOIN users u ON p.farmer_id = u.id 
     WHERE p.id=?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$product = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($product);

if (!$p) {
    echo "<script>window.location.href='marketplace.php';</script>";
    exit();
}

$pageTitle = htmlspecialchars($p['name']);

include "includes/header.php";
?>

<div class="card" style="display:flex; gap:25px; flex-wrap:wrap;">

    <!-- Product Image -->
    <img 
        src="<?php echo $p['image'] 
            ? htmlspecialchars($p['image']) 
            : 'https://via.placeholder.com/350x250?text=কোনো+ছবি+নেই'; ?>" 
        alt="<?php echo htmlspecialchars($p['name']); ?>"
        style="
            width:320px;
            height:320px;
            max-width:100%;
            border-radius:10px;
            object-fit:contain;
            background:#f5f7f5;
            padding:10px;
            display:block;
        "
    >

    <!-- Product Information -->
    <div style="flex:1; min-width:250px;">

        <h2 style="color:#1b5e20;">
            <?php echo htmlspecialchars($p['name']); ?>
        </h2>

        <p style="color:#777; margin:6px 0;">
            ক্যাটাগরি:
            <?php echo htmlspecialchars($p['category']); ?>
        </p>

        <p class="price" style="font-size:1.4rem;">
            ৳ <?php echo number_format($p['price'], 2); ?> / একক
        </p>

        <p>
            স্টক পরিমাণ:
            <?php echo $p['quantity']; ?>
        </p>

        <p style="margin:15px 0;">
            <?php echo nl2br(htmlspecialchars($p['description'])); ?>
        </p>

        <p style="color:#555;">
            বিক্রেতা:
            <strong>
                <?php echo htmlspecialchars($p['farmer_name']); ?>
            </strong>
            (<?php echo htmlspecialchars($p['farmer_phone']); ?>)
        </p>

        <?php if (isLoggedIn() && getRole() === 'buyer'): ?>

            <a 
                href="place_order.php?product_id=<?php echo $p['id']; ?>" 
                class="btn" 
                style="margin-top:15px;"
            >
                এখনই অর্ডার করুন
            </a>

        <?php elseif (!isLoggedIn()): ?>

            <a 
                href="login.php" 
                class="btn" 
                style="margin-top:15px;"
            >
                অর্ডার করতে লগইন করুন
            </a>

        <?php endif; ?>

    </div>

</div>

<?php include "includes/footer.php"; ?>