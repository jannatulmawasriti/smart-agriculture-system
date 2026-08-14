<?php

require_once "config.php";
require_once "includes/auth.php";
require_once "includes/treatment_library.php";

$showResult = false;
$error = "";
$disease = [];
$sourceUsed = "demo";


// ============================================================
// LOCAL LEAF DETECTOR API
// ============================================================

function callLeafDetectorAPI($imagePath)
{
    if (!file_exists($imagePath)) {
        return null;
    }

    $apiUrl = "http://127.0.0.1:5000/predict_leaf";

    $ch = curl_init($apiUrl);

    $cfile = new CURLFile(
        realpath($imagePath)
    );

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        ["image" => $cfile]
    );

    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        30
    );

    $response = curl_exec($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);


    if (
        $curlError ||
        $httpCode < 200 ||
        $httpCode >= 300 ||
        !$response
    ) {
        return null;
    }


    $data = json_decode(
        $response,
        true
    );


    if (
        !$data ||
        empty($data["success"])
    ) {
        return null;
    }


    return $data;
}


// ============================================================
// LOCAL DISEASE DETECTOR API
// ============================================================

function callLocalModelAPI(
    $imagePath,
    $apiUrl
) {
    global $treatmentLibrary;


    if (!file_exists($imagePath)) {
        return null;
    }


    $ch = curl_init($apiUrl);

    $cfile = new CURLFile(
        realpath($imagePath)
    );


    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        [
            "image" => $cfile
        ]
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        30
    );


    $response = curl_exec($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);


    if (
        $curlError ||
        $httpCode < 200 ||
        $httpCode >= 300 ||
        !$response
    ) {
        return null;
    }


    $data = json_decode(
        $response,
        true
    );


    if (
        !$data ||
        empty($data["success"])
    ) {
        return null;
    }


    $rawClassName =
        $data["disease_name"] ?? "Unknown";


    // ========================================================
    // Treatment Library
    // ========================================================

    if (
        isset(
            $treatmentLibrary[$rawClassName]
        )
    ) {

        $info =
            $treatmentLibrary[$rawClassName];


        return [

            "name" =>
                $info["display_name"],

            "confidence" =>
                round(
                    floatval(
                        $data["confidence"] ?? 0
                    ),
                    1
                ),

            "symptoms" =>
                $info["symptoms"],

            "treatment" =>
                $info["treatment"],

            "prevention" =>
                $info["prevention"]

        ];
    }


    // ========================================================
    // যদি treatment library-তে class না থাকে
    // ========================================================

    return [

        "name" =>
            $rawClassName,

        "confidence" =>
            round(
                floatval(
                    $data["confidence"] ?? 0
                ),
                1
            ),

        "symptoms" =>
            "এই রোগের জন্য বিস্তারিত তথ্য এখনো treatment library-তে যোগ করা হয়নি।",

        "treatment" =>
            "নির্দিষ্ট চিকিৎসার জন্য কৃষি বিশেষজ্ঞের পরামর্শ নিন।",

        "prevention" =>
            "নিয়মিত ফসল পর্যবেক্ষণ করুন।"

    ];
}


// ============================================================
// IMAGE UPLOAD
// ============================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_FILES["leaf_image"])
) {

    $file =
        $_FILES["leaf_image"];


    $allowed = [
        "jpg",
        "jpeg",
        "png"
    ];


    $ext =
        strtolower(
            pathinfo(
                $file["name"],
                PATHINFO_EXTENSION
            )
        );


    // ========================================================
    // File Error Check
    // ========================================================

    if (
        $file["error"] !== 0
    ) {

        $error =
            "ছবি আপলোড করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";

    }

    elseif (
        !in_array(
            $ext,
            $allowed
        )
    ) {

        $error =
            "শুধুমাত্র JPG, JPEG অথবা PNG ছবি আপলোড করা যাবে।";

    }

    else {

        // ====================================================
        // Save Image
        // ====================================================

        $newName =
            "leaf_" .
            time() .
            "_" .
            rand(100, 999) .
            "." .
            $ext;


        $target =
            "uploads/disease/" .
            $newName;


        if (
            move_uploaded_file(
                $file["tmp_name"],
                $target
            )
        ) {


            // =================================================
            // STEP 1
            // LEAF / NON-LEAF CHECK
            // =================================================

            $leafResult =
                callLeafDetectorAPI(
                    $target
                );


            // =================================================
            // Leaf detector কাজ না করলে
            // =================================================

            if (
                $leafResult === null
            ) {

                $error =
                    "Leaf Detector AI server থেকে ফলাফল পাওয়া যায়নি। Flask server চালু আছে কিনা নিশ্চিত করুন।";

            }

            else {

                $leafClass =
                    strtolower(
                        trim(
                            $leafResult["prediction"] ??
                            $leafResult["class"] ??
                            $leafResult["label"] ??
                            ""
                        )
                    );


                $leafConfidence =
                    floatval(
                        $leafResult["confidence"] ?? 0
                    );


                // =================================================
                // STEP 2
                // NON-LEAF হলে STOP
                // =================================================

                if (
                    $leafClass !== "leaf"
                ) {

                    $error =
                        "❌ এটি একটি leaf image নয়। দয়া করে গাছের পাতার একটি পরিষ্কার ছবি আপলোড করুন।";

                }

                // =================================================
                // STEP 3
                // LEAF হলে Disease Detector
                // =================================================

                else {

                    $picked = null;


                    if (
                        !empty(
                            $GLOBALS["LOCAL_AI_API_URL"]
                        )
                    ) {

                        $picked =
                            callLocalModelAPI(
                                $target,
                                $GLOBALS["LOCAL_AI_API_URL"]
                            );


                        if (
                            $picked !== null
                        ) {

                            $sourceUsed =
                                "local";
                        }
                    }


                    // =================================================
                    // Disease AI result
                    // =================================================

                    if (
                        $picked === null
                    ) {

                        $error =
                            "Disease AI server থেকে ফলাফল পাওয়া যায়নি।";

                    }

                    else {

                        // =================================================
                        // Save Database
                        // =================================================

                        $stmt =
                            mysqli_prepare(
                                $conn,
                                "INSERT INTO disease_predictions
                                (
                                    user_id,
                                    image_path,
                                    disease_name,
                                    confidence,
                                    symptoms,
                                    treatment,
                                    prevention
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?)"
                            );


                        $uid =
                            isLoggedIn()
                            ? $_SESSION["user_id"]
                            : null;


                        mysqli_stmt_bind_param(
                            $stmt,
                            "issdsss",
                            $uid,
                            $target,
                            $picked["name"],
                            $picked["confidence"],
                            $picked["symptoms"],
                            $picked["treatment"],
                            $picked["prevention"]
                        );


                        mysqli_stmt_execute(
                            $stmt
                        );


                        // =================================================
                        // Show Result
                        // =================================================

                        $disease =
                            $picked;


                        $disease["image"] =
                            $target;


                        $showResult =
                            true;
                    }
                }
            }

        }

        else {

            $error =
                "ছবি সংরক্ষণ করতে ব্যর্থ হয়েছে।";

        }
    }
}


// ============================================================
// SOURCE LABEL
// ============================================================

$sourceLabel = [

    "local" =>
        '<span style="
            font-size:0.8rem;
            color:#2e7d32;
        ">
        (আপনার নিজের ট্রেইন করা মডেল দ্বারা বিশ্লেষিত)
        </span>',

    "demo" =>
        '<span style="
            font-size:0.8rem;
            color:#999;
        ">
        (ডেমো)
        </span>'

];


$pageTitle =
    "রোগ শনাক্তকরণ";


include "includes/header.php";

?>



<!-- =========================================================
     UPLOAD CARD
========================================================= -->

<div class="card">

    <h2 style="
        color:#2e7d32;
        margin-bottom:10px;
    ">
        🍃 স্মার্ট রোগ শনাক্তকরণ
    </h2>


    <p style="
        margin-bottom:15px;
        color:#555;
    ">
        গাছের পাতার একটি পরিষ্কার ছবি আপলোড করুন।
        প্রথমে AI যাচাই করবে ছবিটি leaf কিনা।
        এরপর leaf হলে রোগ শনাক্ত করবে।
    </p>


    <?php if ($error): ?>

        <div
            class="alert alert-error"
            style="
                margin-bottom:15px;
            "
        >

            <?php
            echo htmlspecialchars(
                $error
            );
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="disease_detection.php"
        enctype="multipart/form-data"
    >

        <div class="form-group">

            <label>
                পাতার ছবি নির্বাচন করুন
            </label>


            <input
                type="file"
                name="leaf_image"
                accept=".jpg,.jpeg,.png"
                required
            >

        </div>


        <button
            type="submit"
            class="btn"
            style="width:100%;"
        >
            🔍 রোগ শনাক্ত করুন
        </button>

    </form>

</div>



<?php if ($showResult): ?>

<!-- =========================================================
     RESULT
========================================================= -->

<div
    class="result-box"
    style="margin-top:20px;"
>

    <h3>
        📋 শনাক্তকরণ ফলাফল

        <?php
        echo $sourceLabel[
            $sourceUsed
        ];
        ?>

    </h3>


    <img
        src="<?php
        echo htmlspecialchars(
            $disease["image"]
        );
        ?>"
        style="
            max-width:250px;
            border-radius:8px;
            margin-bottom:15px;
        "
    >


    <div class="result-item">

        <strong>
            রোগের নাম:
        </strong>

        <?php
        echo htmlspecialchars(
            $disease["name"]
        );
        ?>

    </div>


    <div class="result-item">

        <strong>
            নির্ভুলতার হার:
        </strong>

        <?php
        echo $disease["confidence"];
        ?>%

    </div>


    <div class="result-item">

        <strong>
            লক্ষণ:
        </strong>

        <?php
        echo htmlspecialchars(
            $disease["symptoms"]
        );
        ?>

    </div>


    <div class="result-item">

        <strong>
            চিকিৎসা:
        </strong>

        <?php
        echo htmlspecialchars(
            $disease["treatment"]
        );
        ?>

    </div>


    <div class="result-item">

        <strong>
            প্রতিরোধ:
        </strong>

        <?php
        echo htmlspecialchars(
            $disease["prevention"]
        );
        ?>

    </div>

</div>

<?php endif; ?>



<?php

// ============================================================
// HISTORY
// ============================================================

if (
    isLoggedIn()
):

    $uid =
        $_SESSION["user_id"];


    $hist =
        mysqli_query(
            $conn,
            "SELECT *
             FROM disease_predictions
             WHERE user_id=$uid
             ORDER BY created_at DESC
             LIMIT 5"
        );


    if (
        mysqli_num_rows($hist) > 0
    ):

?>

<div
    class="card"
    style="margin-top:20px;"
>

    <h3 style="
        color:#2e7d32;
        margin-bottom:10px;
    ">
        🕘 আমার পূর্ববর্তী শনাক্তকরণ
    </h3>


    <table>

        <tr>

            <th>
                রোগের নাম
            </th>

            <th>
                নির্ভুলতা
            </th>

            <th>
                তারিখ
            </th>

        </tr>


        <?php

        while (
            $row =
            mysqli_fetch_assoc(
                $hist
            )
        ):

        ?>

        <tr>

            <td>

                <?php
                echo htmlspecialchars(
                    $row["disease_name"]
                );
                ?>

            </td>


            <td>

                <?php
                echo $row["confidence"];
                ?>%

            </td>


            <td>

                <?php

                echo date(
                    "d M Y",
                    strtotime(
                        $row["created_at"]
                    )
                );

                ?>

            </td>

        </tr>


        <?php endwhile; ?>

    </table>

</div>

<?php

    endif;

endif;

?>


<?php

include "includes/footer.php";

?>