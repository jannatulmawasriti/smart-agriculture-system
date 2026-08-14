import os

# TensorFlow import করার আগে
os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"

import json
import numpy as np

from flask import Flask, request, jsonify
from PIL import Image

import tensorflow as tf


# ============================================================
# CONFIGURATION
# ============================================================

# Disease Detection Model
MODEL_PATH = "leaf_disease_model.keras"
LABELS_PATH = "labels.json"

# Leaf / Non-Leaf Detector
LEAF_DETECTOR_MODEL_PATH = "leaf_detector.keras"
LEAF_DETECTOR_LABELS_PATH = "leaf_detector_labels.json"

# Image size
IMG_SIZE = (128, 128)


# ============================================================
# FLASK APP
# ============================================================

app = Flask(__name__)


# ============================================================
# LOAD DISEASE MODEL
# ============================================================

print("📦 Disease Detection Model লোড হচ্ছে...")

try:

    model = tf.keras.models.load_model(
        MODEL_PATH
    )

    print("✅ Disease Model successfully loaded.")

except Exception as e:

    print("❌ Disease Model load failed:")
    print(e)

    raise


# ============================================================
# LOAD DISEASE LABELS
# ============================================================

with open(
    LABELS_PATH,
    "r",
    encoding="utf-8"
) as f:

    class_names = json.load(f)


print(
    f"✅ Disease classes: {len(class_names)}"
)

print(
    "🏷️ Disease Classes:",
    class_names
)


# ============================================================
# LOAD LEAF DETECTOR MODEL
# ============================================================

print("\n🍃 Leaf Detector model লোড হচ্ছে...")

try:

    leaf_detector = tf.keras.models.load_model(
        LEAF_DETECTOR_MODEL_PATH
    )

    print(
        "✅ Leaf Detector successfully loaded."
    )

except Exception as e:

    print(
        "❌ Leaf Detector load failed:"
    )

    print(e)

    raise


# ============================================================
# LOAD LEAF DETECTOR LABELS
# ============================================================

with open(
    LEAF_DETECTOR_LABELS_PATH,
    "r",
    encoding="utf-8"
) as f:

    leaf_detector_class_names = json.load(f)


print(
    "🍃 Leaf Detector Classes:",
    leaf_detector_class_names
)


# ============================================================
# HEALTH CHECK
# ============================================================

@app.route(
    "/health",
    methods=["GET"]
)
def health():

    return jsonify({

        "status": "ok",

        "disease_classes":
            len(class_names),

        "leaf_detector_classes":
            len(leaf_detector_class_names)

    })


# ============================================================
# LEAF / NON-LEAF DETECTION API
# ============================================================

@app.route(
    "/predict_leaf",
    methods=["POST"]
)
def predict_leaf():

    # --------------------------------------------------------
    # Check image
    # --------------------------------------------------------

    if "image" not in request.files:

        return jsonify({

            "success": False,

            "error":
                "কোনো ছবি পাওয়া যায়নি।"

        }), 400


    try:

        # ----------------------------------------------------
        # Receive image
        # ----------------------------------------------------

        file = request.files["image"]


        # ----------------------------------------------------
        # Open image
        # ----------------------------------------------------

        img = Image.open(
            file.stream
        ).convert("RGB")


        # ----------------------------------------------------
        # Resize
        # ----------------------------------------------------

        img = img.resize(
            IMG_SIZE
        )


        # ----------------------------------------------------
        # Convert to numpy
        # ----------------------------------------------------

        arr = np.array(
            img,
            dtype=np.float32
        )


        # ----------------------------------------------------
        # Add batch dimension
        # ----------------------------------------------------

        arr = np.expand_dims(
            arr,
            axis=0
        )


        print(
            "🍃 Leaf Detector Input shape:",
            arr.shape
        )


        # ----------------------------------------------------
        # Prediction
        # ----------------------------------------------------

        predictions = leaf_detector.predict(
            arr,
            verbose=0
        )[0]


        # ----------------------------------------------------
        # Highest probability
        # ----------------------------------------------------

        top_idx = int(
            np.argmax(predictions)
        )


        confidence = (
            float(predictions[top_idx])
            * 100
        )


        prediction = (
            leaf_detector_class_names[
                top_idx
            ]
        )


        # ----------------------------------------------------
        # Print result
        # ----------------------------------------------------

        print(
            f"🍃 Leaf Detector Prediction: {prediction}"
        )

        print(
            f"🎯 Confidence: {confidence:.2f}%"
        )


        # ----------------------------------------------------
        # Return result
        # ----------------------------------------------------

        return jsonify({

            "success": True,

            "prediction":
                prediction,

            "confidence":
                round(
                    confidence,
                    2
                )

        })


    except Exception as e:

        print(
            "❌ Leaf Detector error:"
        )

        print(e)


        return jsonify({

            "success": False,

            "error":
                str(e)

        }), 500


# ============================================================
# DISEASE PREDICTION API
# ============================================================

@app.route(
    "/predict",
    methods=["POST"]
)
def predict():

    # --------------------------------------------------------
    # Check image
    # --------------------------------------------------------

    if "image" not in request.files:

        return jsonify({

            "success": False,

            "error":
                "কোনো ছবি পাওয়া যায়নি। image field দরকার।"

        }), 400


    try:

        # ----------------------------------------------------
        # Receive image
        # ----------------------------------------------------

        file = request.files["image"]


        # ----------------------------------------------------
        # Open image
        # ----------------------------------------------------

        img = Image.open(
            file.stream
        ).convert("RGB")


        # ----------------------------------------------------
        # Resize exactly like training
        # ----------------------------------------------------

        img = img.resize(
            IMG_SIZE
        )


        # ----------------------------------------------------
        # Convert to numpy
        # ----------------------------------------------------

        arr = np.array(
            img,
            dtype=np.float32
        )


        # ----------------------------------------------------
        # Add batch dimension
        # ----------------------------------------------------

        arr = np.expand_dims(
            arr,
            axis=0
        )


        print(
            "📐 Disease Model Input shape:",
            arr.shape
        )


        # ----------------------------------------------------
        # Disease prediction
        # ----------------------------------------------------

        predictions = model.predict(
            arr,
            verbose=0
        )[0]


        # ----------------------------------------------------
        # Highest probability
        # ----------------------------------------------------

        top_idx = int(
            np.argmax(predictions)
        )


        confidence = (
            float(predictions[top_idx])
            * 100
        )


        disease_name = (
            class_names[
                top_idx
            ]
        )


        # ----------------------------------------------------
        # Top 3 predictions
        # ----------------------------------------------------

        top3_idx = (
            predictions
            .argsort()[-3:][::-1]
        )


        top3 = []


        for i in top3_idx:

            top3.append({

                "name":
                    class_names[i],

                "confidence":
                    round(
                        float(
                            predictions[i]
                        ) * 100,
                        2
                    )

            })


        # ----------------------------------------------------
        # Print result
        # ----------------------------------------------------

        print(
            f"🌿 Disease Prediction: {disease_name}"
        )

        print(
            f"🎯 Confidence: {confidence:.2f}%"
        )


        # ----------------------------------------------------
        # Return JSON
        # ----------------------------------------------------

        return jsonify({

            "success": True,

            "disease_name":
                disease_name,

            "confidence":
                round(
                    confidence,
                    2
                ),

            "top3":
                top3

        })


    except Exception as e:

        print(
            "❌ Disease Prediction error:"
        )

        print(e)


        return jsonify({

            "success": False,

            "error":
                str(e)

        }), 500


# ============================================================
# START FLASK SERVER
# ============================================================

if __name__ == "__main__":

    print(
        "\n=================================================="
    )

    print(
        "🚀 Smart Agriculture AI Server"
    )

    print(
        "=================================================="
    )

    print(
        "🌐 Server: http://127.0.0.1:5000"
    )

    print(
        "🍃 Leaf API: http://127.0.0.1:5000/predict_leaf"
    )

    print(
        "🌿 Disease API: http://127.0.0.1:5000/predict"
    )

    print(
        "==================================================\n"
    )


    app.run(

        host="0.0.0.0",

        port=5000,

        debug=False

    )