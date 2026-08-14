<?php
require_once "config.php";
require_once "includes/auth.php";

$fertilizer_result = "";
$water_result = "";
$recommendation = "";
$showResult = false;

$cropData = [
    "ধান" => ["urea" => 12, "tsp" => 6, "mop" => 5, "water" => 400],
    "গম" => ["urea" => 8, "tsp" => 5, "mop" => 4, "water" => 250],
    "ভুট্টা" => ["urea" => 14, "tsp" => 7, "mop" => 6, "water" => 350],
    "আলু" => ["urea" => 10, "tsp" => 8, "mop" => 9, "water" => 300],
    "সবজি" => ["urea" => 7, "tsp" => 5, "mop" => 4, "water" => 200],
];

$soilFactor = ["দোআঁশ" => 1.0, "বেলে" => 1.2, "এঁটেল" => 0.85, "পলি" => 0.95];
$moistureFactor = ["শুষ্ক" => 1.3, "স্বাভাবিক" => 1.0, "ভেজা" => 0.6];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $crop = $_POST['crop_type'];
    $soil = $_POST['soil_type'];
    $fieldSize = floatval($_POST['field_size']);
    $moisture = $_POST['moisture_level'];

    if ($fieldSize > 0 && isset($cropData[$crop]) && isset($soilFactor[$soil]) && isset($moistureFactor[$moisture])) {
        $base = $cropData[$crop];
        $sf = $soilFactor[$soil];
        $mf = $moistureFactor[$moisture];

        $urea = round($base['urea'] * $fieldSize * $sf, 1);
        $tsp = round($base['tsp'] * $fieldSize * $sf, 1);
        $mop = round($base['mop'] * $fieldSize * $sf, 1);
        $water = round($base['water'] * $fieldSize * $mf, 1);

        $fertilizer_result = "ইউরিয়া: {$urea} কেজি, টিএসপি: {$tsp} কেজি, এমওপি: {$mop} কেজি";
        $water_result = "{$water} লিটার (প্রতি সেচে, আনুমানিক)";
        $recommendation = "মাটির আর্দ্রতা '{$moisture}' অনুযায়ী সেচের সময়সূচি ঠিক করুন। সার প্রয়োগের পর হালকা সেচ দিন এবং অতিরিক্ত সার প্রয়োগ থেকে বিরত থাকুন।";
        $showResult = true;

        if (isLoggedIn()) {
            $uid = $_SESSION['user_id'];
            $stmt = mysqli_prepare($conn, "INSERT INTO fertilizer_calculations (user_id, crop_type, soil_type, field_size, moisture_level, fertilizer_result, water_result) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issdsss", $uid, $crop, $soil, $fieldSize, $moisture, $fertilizer_result, $water_result);
            mysqli_stmt_execute($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO fertilizer_calculations (user_id, crop_type, soil_type, field_size, moisture_level, fertilizer_result, water_result) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdsss", $crop, $soil, $fieldSize, $moisture, $fertilizer_result, $water_result);
            mysqli_stmt_execute($stmt);
        }
    }
}

$pageTitle = "সার ও পানি ক্যালকুলেটর";
include "includes/header.php";
?>
<div class="card">
    <h2 style="color:#2e7d32; margin-bottom:20px;">🧪 ইন্টেলিজেন্ট সার ও পানি ক্যালকুলেটর</h2>
    <form method="POST" action="fertilizer_calculator.php">
        <div class="form-group">
            <label>ফসলের ধরন</label>
            <select name="crop_type" required>
                <?php foreach ($cropData as $crop => $v): ?><option value="<?php echo $crop; ?>"><?php echo $crop; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>মাটির ধরন</label>
            <select name="soil_type" required>
                <?php foreach ($soilFactor as $soil => $v): ?><option value="<?php echo $soil; ?>"><?php echo $soil; ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>জমির পরিমাণ (বিঘা)</label>
            <div class="input-with-voice">
                <input type="text" id="field_size" name="field_size" required>
                <button type="button" class="voice-btn" data-target="field_size" onclick="startVoiceInput('field_size')">🎤</button>
            </div>
        </div>
        <div class="form-group">
            <label>মাটির আর্দ্রতার অবস্থা</label>
            <select name="moisture_level" required>
                <?php foreach ($moistureFactor as $m => $v): ?><option value="<?php echo $m; ?>"><?php echo $m; ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn" style="width:100%;">হিসাব করুন</button>
    </form>
</div>
<?php if ($showResult): ?>
<div class="result-box" style="margin-top:20px;">
    <h3>📋 ফলাফল</h3>
    <div class="result-item"><strong>প্রয়োজনীয় সার:</strong> <?php echo $fertilizer_result; ?></div>
    <div class="result-item"><strong>প্রয়োজনীয় পানি:</strong> <?php echo $water_result; ?></div>
    <div class="result-item"><strong>পরামর্শ:</strong> <?php echo $recommendation; ?></div>
</div>
<?php endif; ?>
<?php if (isLoggedIn()):
    $uid = $_SESSION['user_id'];
    $hist = mysqli_query($conn, "SELECT * FROM fertilizer_calculations WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");
    if (mysqli_num_rows($hist) > 0): ?>
<div class="card" style="margin-top:20px;">
    <h3 style="color:#2e7d32; margin-bottom:10px;">🕘 আমার সাম্প্রতিক হিসাব</h3>
    <table>
        <tr><th>ফসল</th><th>মাটি</th><th>জমি (বিঘা)</th><th>সার</th><th>পানি</th><th>তারিখ</th></tr>
        <?php while ($row = mysqli_fetch_assoc($hist)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['crop_type']); ?></td>
            <td><?php echo htmlspecialchars($row['soil_type']); ?></td>
            <td><?php echo htmlspecialchars($row['field_size']); ?></td>
            <td><?php echo htmlspecialchars($row['fertilizer_result']); ?></td>
            <td><?php echo htmlspecialchars($row['water_result']); ?></td>
            <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php endif; endif; ?>
<?php include "includes/footer.php"; ?>
