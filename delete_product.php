<?php
require_once "config.php";
require_once "includes/auth.php";
requireRole('farmer');

$uid = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id=? AND farmer_id=?");
mysqli_stmt_bind_param($stmt, "ii", $id, $uid);
mysqli_stmt_execute($stmt);

header("Location: my_products.php");
exit();
?>
