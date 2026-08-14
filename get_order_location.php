<?php
require_once "config.php";
require_once "includes/auth.php";

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(["success" => false, "message" => "লগইন প্রয়োজন।"]);
    exit();
}

$orderId = intval($_GET['order_id'] ?? 0);
$uid = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT status, delivery_address, delivery_lat, delivery_lng, current_lat, current_lng, location_updated_at
    FROM orders WHERE id=? AND (buyer_id=? OR farmer_id=?)");
mysqli_stmt_bind_param($stmt, "iii", $orderId, $uid, $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
    echo json_encode(["success" => false, "message" => "অর্ডার পাওয়া যায়নি।"]);
    exit();
}

$statusBn = ['Pending' => 'অপেক্ষমাণ', 'Accepted' => 'গৃহীত', 'Processing' => 'প্রক্রিয়াধীন', 'Delivered' => 'পৌঁছে দেওয়া হয়েছে', 'Cancelled' => 'বাতিল'];

echo json_encode([
    "success" => true,
    "status" => $order['status'],
    "status_bn" => $statusBn[$order['status']] ?? $order['status'],
    "delivery_address" => $order['delivery_address'],
    "delivery_lat" => $order['delivery_lat'],
    "delivery_lng" => $order['delivery_lng'],
    "current_lat" => $order['current_lat'],
    "current_lng" => $order['current_lng'],
    "location_updated_at" => $order['location_updated_at'] ? date("d M Y, h:i A", strtotime($order['location_updated_at'])) : null,
]);
