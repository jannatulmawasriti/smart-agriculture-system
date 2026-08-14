<?php
require_once "config.php";
require_once "includes/auth.php";
requireLogin();

$success = "";
$error = "";
$uid = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, address=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $address, $uid);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name'] = $name;
        $success = "প্রোফাইল সফলভাবে আপডেট হয়েছে।";
    } else {
        $error = "আপডেট ব্যর্থ হয়েছে।";
    }
}

$result = mysqli_query($conn, "SELECT * FROM users WHERE id=" . intval($uid));
$user = mysqli_fetch_assoc($result);

$pageTitle = "প্রোফাইল";
include "includes/header.php";
?>
<div class="form-box">
    <h2>আমার প্রোফাইল</h2>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST" action="profile.php">
        <div class="form-group"><label>পূর্ণ নাম</label><input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
        <div class="form-group"><label>ইমেইল</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
        <div class="form-group"><label>মোবাইল নম্বর</label><input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
        <div class="form-group"><label>ঠিকানা</label><input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>"></div>
        <div class="form-group"><label>ভূমিকা</label><input type="text" value="<?php echo $user['role'] === 'farmer' ? 'কৃষক' : ($user['role'] === 'buyer' ? 'ক্রেতা' : 'অ্যাডমিন'); ?>" disabled></div>
        <button type="submit" class="btn" style="width:100%;">আপডেট করুন</button>
    </form>
</div>
<?php include "includes/footer.php"; ?>
