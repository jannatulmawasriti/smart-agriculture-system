import os
import tensorflow as tf

DATASET_DIR = "leaf_detector_dataset"

VALID_EXTENSIONS = {
    ".jpg",
    ".jpeg",
    ".png",
    ".bmp",
    ".gif"
}

bad_files = []
total = 0
valid = 0

print("=" * 60)
print("🔍 TENSORFLOW IMAGE CHECK")
print("=" * 60)

for class_name in ["leaf", "non_leaf"]:

    folder = os.path.join(
        DATASET_DIR,
        class_name
    )

    print(f"\n📂 Checking: {class_name}")

    for filename in os.listdir(folder):

        path = os.path.join(
            folder,
            filename
        )

        if not os.path.isfile(path):
            continue

        total += 1

        ext = os.path.splitext(
            filename
        )[1].lower()

        if ext not in VALID_EXTENSIONS:

            bad_files.append(
                (path, "Invalid extension")
            )

            continue

        try:

            image_bytes = tf.io.read_file(path)

            image = tf.io.decode_image(
                image_bytes,
                channels=3,
                expand_animations=False
            )

            _ = image.numpy()

            valid += 1

        except Exception as e:

            bad_files.append(
                (path, str(e))
            )


print("\n" + "=" * 60)
print("📊 RESULT")
print("=" * 60)

print(
    f"Total files : {total}"
)

print(
    f"Valid files : {valid}"
)

print(
    f"Bad files   : {len(bad_files)}"
)


if bad_files:

    print("\n❌ BAD FILES:")
    print("-" * 60)

    for path, reason in bad_files:

        print("\nFILE:")
        print(path)

        print("\nREASON:")
        print(reason)

else:

    print(
        "\n✅ All images are readable by TensorFlow!"
    )

print("\n" + "=" * 60)