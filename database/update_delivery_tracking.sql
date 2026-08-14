-- =========================================================
-- আপডেট স্ক্রিপ্ট: ঠিকানার লাইভ লোকেশন + ডেলিভারি ট্র্যাকিং
-- =========================================================
-- আপনার আগে থেকেই ডাটাবেজ তৈরি করা থাকলে (আগের ভার্সন থেকে আপডেট
-- করছেন), phpMyAdmin এর SQL ট্যাবে গিয়ে নিচের পুরো কোডটি চালান।
-- নতুন করে ইন্সটল করলে এই ফাইলটি চালানোর দরকার নেই — এটি
-- database/smart_agriculture.sql ফাইলেই যোগ করা আছে।
-- =========================================================

USE smart_agriculture;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS delivery_lat DECIMAL(10,7) DEFAULT NULL AFTER delivery_address,
    ADD COLUMN IF NOT EXISTS delivery_lng DECIMAL(10,7) DEFAULT NULL AFTER delivery_lat,
    ADD COLUMN IF NOT EXISTS current_lat DECIMAL(10,7) DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS current_lng DECIMAL(10,7) DEFAULT NULL AFTER current_lat,
    ADD COLUMN IF NOT EXISTS location_updated_at DATETIME DEFAULT NULL AFTER current_lng;
