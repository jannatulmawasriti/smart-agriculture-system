<?php
require_once "config.php";
require_once "includes/auth.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT id, name, password, role, status FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $error = "আপনার অ্যাকাউন্টটি নিষ্ক্রিয় করা হয়েছে। অ্যাডমিনের সাথে যোগাযোগ করুন।";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'farmer') header("Location: dashboard_farmer.php");
            elseif ($user['role'] === 'buyer') header("Location: dashboard_buyer.php");
            else header("Location: dashboard_admin.php");
            exit();
        }
    } else {
        $error = "ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।";
    }
}

$pageTitle = "লগইন";
include "includes/header.php";
?>
<div class="form-box">
    <h2>লগইন করুন</h2>
    <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" action="login.php">
        <div class="form-group"><label>ইমেইল</label><input type="email" name="email" required></div>
        <div class="form-group"><label>পাসওয়ার্ড</label><input type="password" name="password" required></div>
        <button type="submit" class="btn" style="width:100%;">লগইন</button>
    </form>
    <p style="text-align:center; margin-top:15px;">অ্যাকাউন্ট নেই? <a href="register.php" style="color:#2e7d32; font-weight:600;">রেজিস্ট্রেশন করুন</a></p>
    <p style="text-align:center; margin-top:8px; font-size:0.85rem; color:#777;">অ্যাডমিন ডেমো: admin@agri.com / admin123</p>
</div>
<?php include "includes/footer.php"; ?>
