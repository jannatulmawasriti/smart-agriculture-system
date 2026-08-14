import os

# TensorFlow import করার আগে
os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"

import json
import tensorflow as tf
from tensorflow.keras import layers, models


# ============================================================
# 1. CONFIGURATION
# ============================================================

# Leaf detector dataset
DATASET_DIR = "leaf_detector_dataset"

# Low-RAM PC এর জন্য
IMG_SIZE = (128, 128)

# RAM কম হলে 4 রাখা ভালো
BATCH_SIZE = 4

# শুরুতে 10 epochs
EPOCHS = 10

# 20% validation
VALIDATION_SPLIT = 0.2

# Output model
MODEL_OUTPUT = "leaf_detector.keras"

# Output labels
LABELS_OUTPUT = "leaf_detector_labels.json"


# ============================================================
# 2. MAIN FUNCTION
# ============================================================

def main():

    print("=" * 60)
    print("🌿 LEAF / NON-LEAF DETECTOR TRAINING")
    print("=" * 60)


    # ========================================================
    # 3. DATASET CHECK
    # ========================================================

    if not os.path.isdir(DATASET_DIR):

        print("\n❌ Dataset পাওয়া যায়নি!")

        print("\nExpected location:")
        print(os.path.abspath(DATASET_DIR))

        return


    # Check leaf folder
    leaf_dir = os.path.join(
        DATASET_DIR,
        "leaf"
    )

    # Check non_leaf folder
    non_leaf_dir = os.path.join(
        DATASET_DIR,
        "non_leaf"
    )


    if not os.path.isdir(leaf_dir):

        print("\n❌ 'leaf' folder পাওয়া যায়নি!")
        print(
            "Expected:",
            os.path.abspath(leaf_dir)
        )

        return


    if not os.path.isdir(non_leaf_dir):

        print("\n❌ 'non_leaf' folder পাওয়া যায়নি!")
        print(
            "Expected:",
            os.path.abspath(non_leaf_dir)
        )

        return


    print("\n📂 Dataset location:")
    print(
        os.path.abspath(DATASET_DIR)
    )


    # ========================================================
    # 4. LOAD TRAINING DATA
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
    # 5. LOAD VALIDATION DATA
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
    # 6. CLASS INFORMATION
    # ========================================================

    class_names = train_ds.class_names

    num_classes = len(class_names)


    print("\n" + "=" * 60)

    print(
        f"✅ মোট ক্লাস: {num_classes}"
    )

    print("🏷️ Classes:")


    for i, class_name in enumerate(class_names):

        print(
            f"   {i}: {class_name}"
        )


    print("=" * 60)


    # ========================================================
    # 7. PREFETCH
    # ========================================================

    AUTOTUNE = tf.data.AUTOTUNE


    train_ds = (

        train_ds

        .shuffle(500)

        .prefetch(
            buffer_size=AUTOTUNE
        )

    )


    val_ds = (

        val_ds

        .prefetch(
            buffer_size=AUTOTUNE
        )

    )


    # ========================================================
    # 8. DATA AUGMENTATION
    # ========================================================

    print(
        "\n🔄 Data augmentation প্রস্তুত হচ্ছে..."
    )


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
    # 9. LOAD MOBILENETV2
    # ========================================================

    print(
        "\n📦 MobileNetV2 লোড হচ্ছে..."
    )


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
    # 10. BUILD MODEL
    # ========================================================

    print(
        "\n🏗️ Leaf Detector model তৈরি হচ্ছে..."
    )


    inputs = tf.keras.Input(

        shape=IMG_SIZE + (3,)

    )


    # Data augmentation
    x = data_augmentation(
        inputs
    )


    # MobileNetV2 preprocessing
    #
    # [0,255] -> [-1,1]
    #
    # Model-এর ভিতরেই preprocessing রাখা হয়েছে।

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
    # 11. COMPILE
    # ========================================================

    print(
        "\n⚙️ Model compile হচ্ছে..."
    )


    model.compile(

        optimizer=tf.keras.optimizers.Adam(

            learning_rate=0.001

        ),

        loss="sparse_categorical_crossentropy",

        metrics=["accuracy"]

    )


    # ========================================================
    # 12. MODEL SUMMARY
    # ========================================================

    model.summary()


    # ========================================================
    # 13. CALLBACKS
    # ========================================================

    callbacks = [

        tf.keras.callbacks.EarlyStopping(

            monitor="val_accuracy",

            patience=3,

            restore_best_weights=True

        )

    ]


    # ========================================================
    # 14. TRAINING
    # ========================================================

    print("\n" + "=" * 60)

    print(
        "🚀 LEAF DETECTOR TRAINING STARTED"
    )

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
    # 15. SAVE MODEL
    # ========================================================

    print(
        "\n💾 Leaf Detector model save করা হচ্ছে..."
    )


    model.save(
        MODEL_OUTPUT
    )


    # ========================================================
    # 16. SAVE LABELS
    # ========================================================

    print(
        "🏷️ Leaf Detector labels save করা হচ্ছে..."
    )


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
    # 17. TRAINING RESULTS
    # ========================================================

    train_accuracy = history.history[
        "accuracy"
    ]

    val_accuracy = history.history[
        "val_accuracy"
    ]


    best_train_acc = (
        max(train_accuracy) * 100
    )

    best_val_acc = (
        max(val_accuracy) * 100
    )


    actual_epochs = len(
        train_accuracy
    )


    # ========================================================
    # 18. FINAL OUTPUT
    # ========================================================

    print("\n")

    print("=" * 60)

    print(
        "🎉 LEAF DETECTOR TRAINING COMPLETED!"
    )

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
        "\n📦 Model saved:"
    )

    print(
        os.path.abspath(
            MODEL_OUTPUT
        )
    )


    print(
        "\n🏷️ Labels saved:"
    )

    print(
        os.path.abspath(
            LABELS_OUTPUT
        )
    )


    print("\n" + "=" * 60)

    print(
        "✅ Leaf Detector তৈরি হয়ে গেছে!"
    )

    print("=" * 60)


# ============================================================
# RUN PROGRAM
# ============================================================

if __name__ == "__main__":

    main()