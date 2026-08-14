<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('admin');

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM disease_predictions WHERE id=$id");
    header("Location: admin_disease_records.php");
    exit();
}

$records = mysqli_query($conn, "SELECT d.*, u.name AS user_name FROM disease_predictions d LEFT JOIN users u ON d.user_id=u.id ORDER BY d.created_at DESC");

$pageTitle = "রোগ শনাক্তকরণ রেকর্ড ব্যবস্থাপনা";
include "includes/header.php";
?>
<h2 style="color:#2e7d32; margin-bottom:15px;">🩺 রোগ শনাক্তকরণ রেকর্ড ব্যবস্থাপনা (অ্যাডমিন)</h2>
<div class="card">
<table>
    <tr><th>ছবি</th><th>ইউজার</th><th>রোগের নাম</th><th>নির্ভুলতা</th><th>তারিখ</th><th>কার্যক্রম</th></tr>
    <?php if (mysqli_num_rows($records) === 0): ?>
        <tr><td colspan="6">কোনো রেকর্ড নেই।</td></tr>
    <?php else: ?>
        <?php while ($r = mysqli_fetch_assoc($records)): ?>
        <tr>
            <td><img src="<?php echo htmlspecialchars($r['image_path']); ?>" alt="leaf" style="width:60px;height:60px;object-fit:cover;border-radius:6px;"></td>
            <td><?php echo htmlspecialchars($r['user_name'] ?? 'অজানা'); ?></td>
            <td><?php echo htmlspecialchars($r['disease_name']); ?></td>
            <td><?php echo htmlspecialchars($r['confidence']); ?>%</td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
            <td><a href="admin_disease_records.php?action=delete&id=<?php echo $r['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('মুছে ফেলতে চান?');">মুছুন</a></td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
</table>
</div>
<?php include "includes/footer.php"; ?>