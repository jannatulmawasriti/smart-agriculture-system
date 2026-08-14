<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'toggle') {
        $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE id=$id"));
        if ($u) {
            $newStatus = $u['status'] === 'active' ? 'inactive' : 'active';
            mysqli_query($conn, "UPDATE users SET status='$newStatus' WHERE id=$id");
        }
    } elseif ($_GET['action'] === 'delete') {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role != 'admin'");
    }
    header("Location: admin_users.php");
    exit();
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$roleBn = ['farmer' => 'কৃষক', 'buyer' => 'ক্রেতা', 'admin' => 'অ্যাডমিন'];

$pageTitle = "ইউজার ব্যবস্থাপনা";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">👥 ইউজার ব্যবস্থাপনা</h2>
<div class="card">
<table>
    <tr><th>নাম</th><th>ইমেইল</th><th>ভূমিকা</th><th>ফোন</th><th>অবস্থা</th><th>কার্যক্রম</th></tr>
    <?php while ($u = mysqli_fetch_assoc($users)): ?>
    <tr>
        <td><?php echo htmlspecialchars($u['name']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo $roleBn[$u['role']]; ?></td>
        <td><?php echo htmlspecialchars($u['phone']); ?></td>
        <td><?php echo $u['status'] === 'active' ? '✅ সক্রিয়' : '⛔ নিষ্ক্রিয়'; ?></td>
        <td>
            <?php if ($u['role'] !== 'admin'): ?>
                <a href="admin_users.php?action=toggle&id=<?php echo $u['id']; ?>" class="btn btn-small btn-secondary"><?php echo $u['status'] === 'active' ? 'নিষ্ক্রিয় করুন' : 'সক্রিয় করুন'; ?></a>
                <a href="admin_users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('আপনি কি নিশ্চিত এই ইউজারকে মুছে ফেলতে চান?');">মুছুন</a>
            <?php else: ?>
                <span style="color:#999;">—</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>
