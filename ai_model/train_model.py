
"""
পাতার রোগ শনাক্তকরণ মডেল ট্রেইনিং
Low-RAM Windows PC এর জন্য optimized version

Project folder:
C:\\xampp\\htdocs\\smart_agriculture_system\\ai_model

Dataset structure:

dataset/
    Tomato_Early_Blight/
        img001.jpg
        img002.jpg
        ...

    Tomato_Healthy/
        img001.jpg
        ...

    Potato_Late_Blight/
        ...

Training শেষ হলে তৈরি হবে:

leaf_disease_model.keras
labels.json
"""

import os

# ============================================================
# TensorFlow import করার আগে oneDNN বন্ধ
# ============================================================

os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"

import json
import tensorflow as tf
from tensorflow.keras import layers, models


# ============================================================
# 1. CONFIGURATION
# ============================================================

# Project folder থেকে dataset
DATASET_DIR = "dataset"

# কম RAM/CPU এর জন্য ছোট image size
IMG_SIZE = (128, 128)

# RAM কম তাই Batch Size 4
BATCH_SIZE = 4

# দ্রুত training এর জন্য 5 epochs
EPOCHS = 5

# Dataset-এর 20% validation
VALIDATION_SPLIT = 0.2

# নতুন Keras format
MODEL_OUTPUT = "leaf_disease_model.keras"

# Class labels
LABELS_OUTPUT = "labels.json"


# ============================================================
# 2. MAIN FUNCTION
# ============================================================

def main():

    print("=" * 60)
    print("🌿 LEAF DISEASE DETECTION MODEL TRAINING")
    print("=" * 60)

    # ========================================================
    # Dataset check
    # ========================================================

    if not os.path.isdir(DATASET_DIR):

        print(
            f"\n❌ Dataset পাওয়া যায়নি!"
        )

        print(
            f"Expected location:"
        )

        print(
            os.path.abspath(DATASET_DIR)
        )

        return

    print("\n📂 Dataset location:")
    print(os.path.abspath(DATASET_DIR))

    # ========================================================
    # 3. LOAD TRAINING DATA
    # ========================================================

    print("\n📦 Training dataset লোড হচ্ছে...")

    train_ds = tf.keras.utils.image_dataset_from_directory(

        DATASET_DIR,

        validation_split=VALIDATION_SPLIT,

        subset="training",

        seed=42,

        image_size=IMG_SIZE,

        batch_size=BATCH_SIZE
    )

    # ========================================================
    # 4. LOAD VALIDATION DATA
    # ========================================================

    print("\n📦 Validation dataset লোড হচ্ছে...")

    val_ds = tf.keras.utils.image_dataset_from_directory(

        DATASET_DIR,

        validation_split=VALIDATION_SPLIT,

        subset="validation",

        seed=42,

        image_size=IMG_SIZE,

        batch_size=BATCH_SIZE
    )

    # ========================================================
    # 5. CLASS INFORMATION
    # ========================================================

    class_names = train_ds.class_names

    num_classes = len(class_names)

    print("\n" + "=" * 60)

    print(
        f"✅ মোট ক্লাস: {num_classes}"
    )

    print(
        "🏷️ Disease Classes:"
    )

    for i, class_name in enumerate(class_names):

        print(
            f"   {i}: {class_name}"
        )

    print("=" * 60)

    # ========================================================
    # 6. PREFETCH
    #
    # এখানে disk cache ব্যবহার করা হচ্ছে না।
    # কারণ C/D drive-এর extra space নষ্ট হবে না।
    # ========================================================

    AUTOTUNE = tf.data.AUTOTUNE

    train_ds = (
        train_ds
        .shuffle(200)
        .prefetch(buffer_size=AUTOTUNE)
    )

    val_ds = (
        val_ds
        .prefetch(buffer_size=AUTOTUNE)
    )

    # ========================================================
    # 7. DATA AUGMENTATION
    # ========================================================

    print("\n🔄 Data augmentation প্রস্তুত হচ্ছে...")

    data_augmentation = models.Sequential([

        layers.RandomFlip(
            "horizontal"
        ),

        layers.RandomRotation(
            0.10
        ),

        layers.RandomZoom(
            0.10
        ),

        layers.RandomContrast(
            0.10
        )

    ])

    # ========================================================
    # 8. LOAD MOBILENETV2
    # ========================================================

    print("\n📦 MobileNetV2 লোড হচ্ছে...")

    base_model = tf.keras.applications.MobileNetV2(

        input_shape=IMG_SIZE + (3,),

        include_top=False,

        weights="imagenet"
    )

    # Pre-trained layers freeze
    base_model.trainable = False

    print(
        "✅ MobileNetV2 loaded."
    )

    print(
        "🔒 Pre-trained layers frozen."
    )

    # ========================================================
    # 9. BUILD MODEL
    # ========================================================

    print("\n🏗️ Model তৈরি হচ্ছে...")

    inputs = tf.keras.Input(

        shape=IMG_SIZE + (3,)

    )

    # Data augmentation
    x = data_augmentation(inputs)

    # --------------------------------------------------------
    # MobileNetV2 preprocessing
    #
    # [0, 255] -> [-1, 1]
    #
    # Model-এর ভিতরেই রাখা হয়েছে।
    # ফলে app.py-তে আবার preprocessing করতে হবে না।
    # --------------------------------------------------------

    x = layers.Rescaling(

        scale=1.0 / 127.5,

        offset=-1

    )(x)

    # MobileNetV2
    x = base_model(

        x,

        training=False

    )

    # Feature extraction
    x = layers.GlobalAveragePooling2D()(x)

    # Dropout
    x = layers.Dropout(
        0.3
    )(x)

    # Dense layer
    x = layers.Dense(

        128,

        activation="relu"

    )(x)

    # Dropout
    x = layers.Dropout(
        0.2
    )(x)

    # Output
    outputs = layers.Dense(

        num_classes,

        activation="softmax"

    )(x)

    # Final model
    model = tf.keras.Model(

        inputs=inputs,

        outputs=outputs

    )

    # ========================================================
    # 10. COMPILE
    # ========================================================

    print("\n⚙️ Model compile হচ্ছে...")

    model.compile(

        optimizer=tf.keras.optimizers.Adam(

            learning_rate=0.001

        ),

        loss="sparse_categorical_crossentropy",

        metrics=["accuracy"]

    )

    # ========================================================
    # 11. MODEL SUMMARY
    # ========================================================

    model.summary()

    # ========================================================
    # 12. CALLBACKS
    # ========================================================

    callbacks = [

        tf.keras.callbacks.EarlyStopping(

            monitor="val_accuracy",

            patience=2,

            restore_best_weights=True

        )

    ]

    # ========================================================
    # 13. TRAINING
    # ========================================================

    print("\n" + "=" * 60)

    print("🚀 TRAINING STARTED")

    print(
        f"📐 Image Size: {IMG_SIZE}"
    )

    print(
        f"📦 Batch Size: {BATCH_SIZE}"
    )

    print(
        f"🔁 Maximum Epochs: {EPOCHS}"
    )

    print("=" * 60)

    history = model.fit(

        train_ds,

        validation_data=val_ds,

        epochs=EPOCHS,

        callbacks=callbacks

    )

    # ========================================================
    # 14. SAVE MODEL
    # ========================================================

    print("\n💾 Model save করা হচ্ছে...")

    model.save(
        MODEL_OUTPUT
    )

    # ========================================================
    # 15. SAVE LABELS
    # ========================================================

    print("🏷️ Labels save করা হচ্ছে...")

    with open(

        LABELS_OUTPUT,

        "w",

        encoding="utf-8"

    ) as f:

        json.dump(

            class_names,

            f,

            ensure_ascii=False,

            indent=2

        )

    # ========================================================
    # 16. TRAINING RESULTS
    # ========================================================

    train_accuracy = history.history["accuracy"]

    val_accuracy = history.history["val_accuracy"]

    best_train_acc = max(
        train_accuracy
    ) * 100

    best_val_acc = max(
        val_accuracy
    ) * 100

    actual_epochs = len(
        train_accuracy
    )

    # ========================================================
    # 17. FINAL OUTPUT
    # ========================================================

    print("\n")

    print("=" * 60)

    print("🎉 TRAINING COMPLETED!")

    print("=" * 60)

    print(
        f"📊 Epochs Completed: {actual_epochs}"
    )

    print(
        f"🎯 Best Training Accuracy: "
        f"{best_train_acc:.2f}%"
    )

    print(
        f"🎯 Best Validation Accuracy: "
        f"{best_val_acc:.2f}%"
    )

    print(
        f"\n📦 Model saved:"
    )

    print(
        os.path.abspath(MODEL_OUTPUT)
    )

    print(
        f"\n🏷️ Labels saved:"
    )

    print(
        os.path.abspath(LABELS_OUTPUT)
    )

    print("\n" + "=" * 60)

    print("✅ এখন app.py চালাতে পারবে:")

    print(
        "python app.py"
    )

    print("=" * 60)


# ============================================================
# RUN PROGRAM
# ============================================================

if __name__ == "__main__":

    main()
