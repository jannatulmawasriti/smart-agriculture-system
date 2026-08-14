<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('farmer');

$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id=? AND farmer_id=?");
mysqli_stmt_bind_param($stmt, "ii", $id, $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);

if (!$product) {
    echo "<script>window.location.href='my_products.php';</script>";
    exit();
}

$error = ""; $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $description = trim($_POST['description']);
    $imagePath = $product['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ["jpg", "jpeg", "png"])) {
            $newName = "product_" . time() . "_" . rand(100, 999) . "." . $ext;
            $target = "uploads/products/" . $newName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = $target;
            }
        }
    }

    $stmt2 = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, price=?, quantity=?, description=?, image=? WHERE id=? AND farmer_id=?");
    mysqli_stmt_bind_param($stmt2, "ssdissii", $name, $category, $price, $quantity, $description, $imagePath, $id, $uid);
    if (mysqli_stmt_execute($stmt2)) {
        $success = "পণ্য সফলভাবে আপডেট হয়েছে।";
        $product['name'] = $name; $product['category'] = $category; $product['price'] = $price;
        $product['quantity'] = $quantity; $product['description'] = $description; $product['image'] = $imagePath;
    } else {
        $error = "আপডেট ব্যর্থ হয়েছে।";
    }
}

$pageTitle = "পণ্য সম্পাদনা";
include "includes/header.php";
?>
<div class="form-box">
    <h2>✏️ পণ্য সম্পাদনা করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <form method="POST" action="edit_product.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
        <div class="form-group"><label>পণ্যের নাম</label><input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
        <div class="form-group"><label>ক্যাটাগরি</label><input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>"></div>
        <div class="form-group"><label>মূল্য (৳)</label><input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required></div>
        <div class="form-group"><label>স্টক পরিমাণ</label><input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required></div>
        <div class="form-group"><label>বিবরণ</label><textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea></div>
        <div class="form-group"><label>নতুন ছবি (ঐচ্ছিক)</label><input type="file" name="image" accept=".jpg,.jpeg,.png"></div>
        <button type="submit" class="btn" style="width:100%;">আপডেট করুন</button>
    </form>
</div>
<?php include "includes/footer.php"; ?>
