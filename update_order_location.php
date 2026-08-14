<?php
require_once "config.php";
require_once "includes/auth.php";

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || getRole() !== 'farmer') {
    echo json_encode(["success" => false, "message" => "অনুমতি নেই।"]);
    exit();
}

$orderId = intval($_POST['order_id'] ?? 0);
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$uid = $_SESSION['user_id'];

if ($orderId <= 0 || $lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(["success" => false, "message" => "সঠিক তথ্য পাওয়া যায়নি।"]);
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE orders SET current_lat=?, current_lng=?, location_updated_at=NOW() WHERE id=? AND farmer_id=?");
mysqli_stmt_bind_param($stmt, "ddii", $lat, $lng, $orderId, $uid);
$ok = mysqli_stmt_execute($stmt);

if ($ok && mysqli_stmt_affected_rows($stmt) >= 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "লোকেশন আপডেট ব্যর্থ হয়েছে।"]);
}
