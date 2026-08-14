import os

# TensorFlow import করার আগে
os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"

import json
import numpy as np
import tensorflow as tf
from PIL import Image


# ============================================================
# CONFIGURATION
# ============================================================

MODEL_PATH = "leaf_detector.keras"

LABELS_PATH = "leaf_detector_labels.json"

IMG_SIZE = (128, 128)


# ============================================================
# LOAD MODEL
# ============================================================

print("=" * 60)
print("🌿 LEAF / NON-LEAF DETECTOR TEST")
print("=" * 60)

print("\n📦 Model loading...")

model = tf.keras.models.load_model(
    MODEL_PATH
)

print("✅ Model loaded.")


# ============================================================
# LOAD LABELS
# ============================================================

with open(
    LABELS_PATH,
    "r",
    encoding="utf-8"
) as f:

    class_names = json.load(f)


print(
    "🏷️ Classes:",
    class_names
)


# ============================================================
# ASK IMAGE PATH
# ============================================================

image_path = input(
    "\n📷 Enter image path: "
).strip()


# Remove quotation marks if user pastes a path
image_path = image_path.strip('"')


# ============================================================
# CHECK IMAGE
# ============================================================

if not os.path.isfile(image_path):

    print(
        "\n❌ Image file পাওয়া যায়নি!"
    )

    print(
        "Path:",
        image_path
    )

    exit()


# ============================================================
# OPEN IMAGE
# ============================================================

try:

    img = Image.open(
        image_path
    ).convert("RGB")

except Exception as e:

    print(
        "\n❌ Image open করা যায়নি!"
    )

    print(e)

    exit()


# ============================================================
# RESIZE
# ============================================================

img = img.resize(
    IMG_SIZE
)


# ============================================================
# NUMPY ARRAY
# ============================================================

arr = np.array(
    img,
    dtype=np.float32
)


# Add batch dimension
arr = np.expand_dims(
    arr,
    axis=0
)


print(
    "\n📐 Input shape:",
    arr.shape
)


# ============================================================
# PREDICTION
# ============================================================

predictions = model.predict(
    arr,
    verbose=0
)[0]


# Highest probability
top_idx = int(
    np.argmax(predictions)
)


confidence = (
    float(predictions[top_idx])
    * 100
)


predicted_class = class_names[
    top_idx
]


# ============================================================
# SHOW ALL PROBABILITIES
# ============================================================

print("\n" + "=" * 60)

print("🔍 PREDICTION RESULT")

print("=" * 60)


for i, class_name in enumerate(
    class_names
):

    probability = (
        float(predictions[i])
        * 100
    )

    print(
        f"{class_name:10s} : "
        f"{probability:.2f}%"
    )


# ============================================================
# FINAL RESULT
# ============================================================

print("\n" + "=" * 60)

print(
    f"🎯 Prediction: {predicted_class}"
)

print(
    f"📊 Confidence: {confidence:.2f}%"
)

print("=" * 60)


# ============================================================
# SIMPLE MESSAGE
# ============================================================

if predicted_class == "leaf":

    print(
        "\n🌿 এটি একটি LEAF image বলে model মনে করছে।"
    )

elif predicted_class == "non_leaf":

    print(
        "\n❌ এটি একটি NON-LEAF image বলে model মনে করছে।"
    )

else:

    print(
        "\n⚠️ Unknown class."
    )