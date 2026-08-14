<?php
require_once "config.php";
require_once "includes/auth.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];

    if ($name === "" || $email === "" || $password === "") {
        $error = "অনুগ্রহ করে সকল প্রয়োজনীয় তথ্য পূরণ করুন।";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='" . mysqli_real_escape_string($conn, $email) . "'");
        if (mysqli_num_rows($check) > 0) {
            $error = "এই ইমেইল দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট আছে।";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $hashed, $phone, $address, $role);
            if (mysqli_stmt_execute($stmt)) {
                $success = "রেজিস্ট্রেশন সফল হয়েছে! এখন লগইন করুন।";
            } else {
                $error = "রেজিস্ট্রেশন ব্যর্থ হয়েছে। আবার চেষ্টা করুন।";
            }
        }
    }
}

$pageTitle = "রেজিস্ট্রেশন";
include "includes/header.php";
?>
<div class="form-box">
    <h2>নতুন অ্যাকাউন্ট তৈরি করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">লগইন পেজে যান</a></div><?php endif; ?>
    <form method="POST" action="register.php">
        <div class="form-group">
            <label>পূর্ণ নাম</label>
            <div class="input-with-voice">
                <input type="text" id="name" name="name" required>
                <button type="button" class="voice-btn" data-target="name" onclick="startVoiceInput('name')">🎤</button>
            </div>
        </div>
        <div class="form-group"><label>ইমেইল</label><input type="email" name="email" required></div>
        <div class="form-group"><label>পাসওয়ার্ড</label><input type="password" name="password" required minlength="6"></div>
        <div class="form-group"><label>মোবাইল নম্বর</label><input type="text" name="phone"></div>
        <div class="form-group"><label>ঠিকানা</label><input type="text" name="address"></div>
        <div class="form-group">
            <label>আপনি কে?</label>
            <select name="role" required>
                <option value="farmer">কৃষক (পণ্য বিক্রেতা)</option>
                <option value="buyer">ক্রেতা</option>
            </select>
        </div>
        <button type="submit" class="btn" style="width:100%;">রেজিস্ট্রেশন করুন</button>
    </form>
    <p style="text-align:center; margin-top:15px;">ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="login.php" style="color:#2e7d32; font-weight:600;">লগইন করুন</a></p>
</div>
<?php include "includes/footer.php"; ?>
