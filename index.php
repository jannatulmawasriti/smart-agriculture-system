<?php
require_once "config.php";
require_once "includes/auth.php";
$pageTitle = "হোম";
include "includes/header.php";
?>
<div class="hero">
    <h1>🌾 স্মার্ট কৃষি ও বাজার ব্যবস্থাপনা সিস্টেম</h1>
    <p>কৃষক ও ক্রেতাদের জন্য একটি সম্পূর্ণ ডিজিটাল প্ল্যাটফর্ম — সার-পানি হিসাব, রোগ শনাক্তকরণ এবং সরাসরি পণ্য বিক্রয়</p>
</div>
<div class="feature-grid">
    <div class="feature-card">
        <div class="icon">🧪</div>
        <h3>সার ও পানি ক্যালকুলেটর</h3>
        <p>ফসল ও মাটির ধরন অনুযায়ী সার ও পানির পরিমাণ জানুন</p>
        <a href="fertilizer_calculator.php" class="btn">এখনই ব্যবহার করুন</a>
    </div>
    <div class="feature-card">
        <div class="icon">🍃</div>
        <h3>রোগ শনাক্তকরণ</h3>
        <p>পাতার ছবি আপলোড করে ফসলের রোগ শনাক্ত করুন</p>
        <a href="disease_detection.php" class="btn">ছবি আপলোড করুন</a>
    </div>
    <div class="feature-card">
        <div class="icon">🛒</div>
        <h3>কৃষি বাজার</h3>
        <p>সরাসরি কৃষকের কাছ থেকে তাজা পণ্য কিনুন</p>
        <a href="marketplace.php" class="btn">বাজার দেখুন</a>
    </div>
</div>
<?php if (!isLoggedIn()): ?>
<div class="card" style="margin-top:30px; text-align:center;">
    <h3>আপনি কি কৃষক অথবা ক্রেতা?</h3>
    <p style="margin:10px 0;">অ্যাকাউন্ট তৈরি করে পণ্য বিক্রি করুন অথবা সরাসরি অর্ডার করুন</p>
    <a href="register.php" class="btn">রেজিস্ট্রেশন করুন</a>
    <a href="login.php" class="btn btn-secondary">লগইন করুন</a>
</div>
<?php endif; ?>
<?php include "includes/footer.php"; ?>
