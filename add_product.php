<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('farmer');

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $description = trim($_POST['description']);
    $imagePath = null;

    if ($name === "" || $price <= 0) {
        $error = "অনুগ্রহ করে সঠিক তথ্য পূরণ করুন।";
    } else {
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

        $uid = $_SESSION['user_id'];
        $stmt = mysqli_prepare($conn, "INSERT INTO products (farmer_id, name, category, price, quantity, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issdiss", $uid, $name, $category, $price, $quantity, $description, $imagePath);
        if (mysqli_stmt_execute($stmt)) {
            $success = "পণ্য সফলভাবে যুক্ত হয়েছে।";
        } else {
            $error = "পণ্য যুক্ত করতে ব্যর্থ হয়েছে।";
        }
    }
}

$pageTitle = "নতুন পণ্য যুক্ত করুন";
include "includes/header.php";
?>
<div class="form-box">
    <h2>➕ নতুন পণ্য যুক্ত করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?> <a href="my_products.php">আমার পণ্য দেখুন</a></div><?php endif; ?>
    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <div class="form-group">
            <label>পণ্যের নাম</label>
            <div class="input-with-voice">
                <input type="text" id="pname" name="name" required>
                <button type="button" class="voice-btn" data-target="pname" onclick="startVoiceInput('pname')">🎤</button>
            </div>
        </div>
        <div class="form-group"><label>ক্যাটাগরি (যেমনঃ সবজি, ফল, শস্য)</label><input type="text" name="category"></div>
        <div class="form-group"><label>মূল্য (৳ প্রতি একক)</label><input type="number" step="0.01" name="price" required></div>
        <div class="form-group"><label>পরিমাণ (স্টক)</label><input type="number" name="quantity" required></div>
        <div class="form-group"><label>বিবরণ</label><textarea name="description" rows="4"></textarea></div>
        <div class="form-group"><label>পণ্যের ছবি</label><input type="file" name="image" accept=".jpg,.jpeg,.png"></div>
        <button type="submit" class="btn" style="width:100%;">পণ্য যুক্ত করুন</button>
    </form>
</div>
<?php include "includes/footer.php"; ?>
