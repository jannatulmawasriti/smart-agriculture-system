<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">🌾 স্মার্ট কৃষি বাজার</a>
        <ul class="nav-links">
            <li><a href="index.php">হোম</a></li>
            <li><a href="fertilizer_calculator.php">সার ও পানি ক্যালকুলেটর</a></li>
            <li><a href="disease_detection.php">রোগ শনাক্তকরণ</a></li>
            <li><a href="marketplace.php">বাজার</a></li>

            <?php if (isLoggedIn()): ?>
                <?php if (getRole() === 'farmer'): ?>
                    <li><a href="dashboard_farmer.php">ড্যাশবোর্ড</a></li>
                <?php elseif (getRole() === 'buyer'): ?>
                    <li><a href="dashboard_buyer.php">ড্যাশবোর্ড</a></li>
                <?php elseif (getRole() === 'admin'): ?>
                    <li><a href="dashboard_admin.php">অ্যাডমিন প্যানেল</a></li>
                <?php endif; ?>
                <li><a href="profile.php">প্রোফাইল</a></li>
                <li><a href="logout.php">লগআউট</a></li>
            <?php else: ?>
                <li><a href="login.php">লগইন</a></li>
                <li><a href="register.php">রেজিস্ট্রেশন</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
