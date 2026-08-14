<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM fertilizer_calculations WHERE id=$id");
    header("Location: admin_fertilizer_records.php");
    exit();
}

$records = mysqli_query($conn, "SELECT f.*, u.name AS user_name FROM fertilizer_calculations f LEFT JOIN users u ON f.user_id=u.id ORDER BY f.created_at DESC");

$pageTitle = "সার হিসাব রেকর্ড ব্যবস্থাপনা";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">🌱 সার হিসাব রেকর্ড ব্যবস্থাপনা (অ্যাডমিন)</h2>
<div class="card">
<table>
    <tr><th>ইউজার</th><th>ফসল</th><th>মাটির ধরন</th><th>জমির আকার</th><th>আর্দ্রতা</th><th>সার ফলাফল</th><th>পানি ফলাফল</th><th>তারিখ</th><th>কার্যক্রম</th></tr>
    <?php if (mysqli_num_rows($records) === 0): ?>
        <tr><td colspan="9">কোনো রেকর্ড নেই।</td></tr>
    <?php else: ?>
        <?php while ($f = mysqli_fetch_assoc($records)): ?>
        <tr>
            <td><?php echo htmlspecialchars($f['user_name'] ?? 'অজানা'); ?></td>
            <td><?php echo htmlspecialchars($f['crop_type']); ?></td>
            <td><?php echo htmlspecialchars($f['soil_type']); ?></td>
            <td><?php echo htmlspecialchars($f['field_size']); ?></td>
            <td><?php echo htmlspecialchars($f['moisture_level']); ?></td>
            <td><?php echo htmlspecialchars($f['fertilizer_result']); ?></td>
            <td><?php echo htmlspecialchars($f['water_result']); ?></td>
            <td><?php echo htmlspecialchars($f['created_at']); ?></td>
            <td><a href="admin_fertilizer_records.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('মুছে ফেলতে চান?');">মুছুন</a></td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>