import os
from PIL import Image

DATASET_DIR = "leaf_detector_dataset"

VALID_EXTENSIONS = {
    ".jpg",
    ".jpeg",
    ".png",
    ".bmp",
    ".gif"
}

bad_files = []
total_files = 0
valid_images = 0


print("=" * 60)
print("🔍 CHECKING LEAF DETECTOR DATASET")
print("=" * 60)


for class_name in ["leaf", "non_leaf"]:

    class_dir = os.path.join(
        DATASET_DIR,
        class_name
    )

    print(f"\n📂 Checking: {class_name}")

    if not os.path.exists(class_dir):

        print(
            f"❌ Folder not found: {class_dir}"
        )

        continue


    for filename in os.listdir(class_dir):

        file_path = os.path.join(
            class_dir,
            filename
        )

        # Skip folders
        if not os.path.isfile(file_path):
            continue

        total_files += 1

        extension = os.path.splitext(
            filename
        )[1].lower()


        # ------------------------------------------------
        # Check extension
        # ------------------------------------------------

        if extension not in VALID_EXTENSIONS:

            bad_files.append({
                "path": file_path,
                "reason": "Invalid extension"
            })

            continue


        # ------------------------------------------------
        # Check actual image
        # ------------------------------------------------

        try:

            with Image.open(file_path) as img:

                img.verify()


            # Open again after verify
            with Image.open(file_path) as img:

                img.convert("RGB").load()


            valid_images += 1


        except Exception as e:

            bad_files.append({
                "path": file_path,
                "reason": str(e)
            })


# ========================================================
# RESULT
# ========================================================

print("\n" + "=" * 60)
print("📊 DATASET CHECK COMPLETED")
print("=" * 60)

print(
    f"Total files : {total_files}"
)

print(
    f"Valid images: {valid_images}"
)

print(
    f"Bad files   : {len(bad_files)}"
)


# ========================================================
# SHOW BAD FILES
# ========================================================

if bad_files:

    print("\n❌ BAD FILES FOUND:")
    print("-" * 60)

    for item in bad_files:

        print(
            f"\n📄 {item['path']}"
        )

        print(
            f"⚠️ Reason: {item['reason']}"
        )


else:

    print(
        "\n✅ No bad image found!"
    )


print("\n" + "=" * 60)