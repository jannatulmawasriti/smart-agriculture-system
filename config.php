<?php

// ================================
// ডাটাবেজ কানেকশন কনফিগারেশন (XAMPP)
// ================================

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "smart_agriculture";

$conn = mysqli_connect(
    $DB_HOST,
    $DB_USER,
    $DB_PASS,
    $DB_NAME
);

if (!$conn) {
    die(
        "ডাটাবেজ কানেকশন ব্যর্থ হয়েছে। "
        . "অনুগ্রহ করে XAMPP চালু আছে কিনা এবং ডাটাবেজ ইম্পোর্ট করা হয়েছে কিনা যাচাই করুন। "
        . "ত্রুটি: "
        . mysqli_connect_error()
    );
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ================================
// রোগ শনাক্তকরণের জন্য AI API
// ================================

// Third-party crop.health API ব্যবহার করছি না
$CROP_HEALTH_API_KEY = "";


// ================================
// নিজের Training করা AI Model
// ================================
//
// Flask app.py অবশ্যই চালু থাকতে হবে:
//
// python app.py
//
// Flask API:
// http://127.0.0.1:5000/predict
//

$LOCAL_AI_API_URL = "http://127.0.0.1:5000/predict";

?>