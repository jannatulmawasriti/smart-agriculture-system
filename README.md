# 🌾 Smart Agriculture System

**Smart Agriculture System** is a PHP + MySQL based agriculture and marketplace web application developed for XAMPP.  
The system includes farmer/buyer/admin features, fertilizer calculation, marketplace, order management, delivery tracking, voice input, and **AI-based leaf disease detection**.

---

## 🛠️ Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP
- **AI Server:** Python + Flask
- **AI Framework:** TensorFlow / Keras
- **AI Model:** MobileNetV2 Transfer Learning
- **Image Processing:** Pillow + NumPy
- **Map:** OpenStreetMap
- **Voice Input:** JavaScript Web Speech API

---

# 🚀 1. Run the Website

## Step 1: Start XAMPP

Open **XAMPP Control Panel** and start:

- Apache
- MySQL

## Step 2: Put the Project in htdocs

Place the project folder here:

```text
C:\xampp\htdocs\smart_agriculture_system
```

## Step 3: Create the Database

Open:

```text
http://localhost/phpmyadmin
```

Then:

1. Create/import the database using:
   `database/smart_agriculture.sql`
2. Click **Import** → select the SQL file → **Go**.

If you already have an older database and want the delivery tracking fields, run:

```text
database/update_delivery_tracking.sql
```

## Step 4: Check Database Configuration

Open:

```text
config.php
```

The default local configuration is:

```php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "smart_agriculture";
```

Change these values only if your MySQL configuration is different.

## Step 5: Open the Website

Go to:

```text
http://localhost/smart_agriculture_system/
```

---

# 🤖 2. AI Disease Detection System

The current project uses **two AI models in sequence**.

### Model 1: Leaf Detector

This model checks whether the uploaded image is:

```text
leaf
non_leaf
```

If the image is not a leaf, the disease detection process stops.

### Model 2: Disease Detector

If the image is classified as a leaf, the second model predicts the plant disease.

The current disease model contains **31 classes**.

### AI Flow

```text
User uploads image
        ↓
PHP disease_detection.php
        ↓
Leaf Detector API
/predict_leaf
        ↓
Is it a leaf?
   ┌────┴────┐
   │         │
  NO        YES
   │         │
 Stop       ↓
           Disease Detector
           /predict
              ↓
        Disease + Confidence
              ↓
        Treatment Library
              ↓
          Show Result
```

---

# 📂 3. AI Model Folder Structure

The AI files should be kept inside:

```text
C:\xampp\htdocs\smart_agriculture_system\ai_model
```

Recommended structure:

```text
ai_model/
│
├── dataset/
│   ├── Apple___Apple_scab/
│   ├── Apple___Black_rot/
│   ├── Apple___Cedar_apple_rust/
│   ├── Apple___healthy/
│   ├── ...
│   └── Tomato___healthy/
│
├── leaf_detector_dataset/
│   ├── leaf/
│   └── non_leaf/
│
├── train_model.py
├── train_leaf_detector.py
├── test_leaf_detector.py
├── check_dataset.py
├── check_tensorflow_images.py
├── app.py
├── requirements.txt
│
├── leaf_disease_model.keras
├── labels.json
├── leaf_detector.keras
└── leaf_detector_labels.json
```

---

# 🌿 4. Disease Dataset Structure

The disease dataset must contain one folder for each class.

Example:

```text
dataset/
│
├── Apple___Apple_scab/
│   ├── image1.jpg
│   ├── image2.jpg
│   └── ...
│
├── Apple___healthy/
│   ├── image1.jpg
│   └── ...
│
├── Potato___Late_blight/
│   ├── image1.jpg
│   └── ...
│
└── Tomato___healthy/
    ├── image1.jpg
    └── ...
```

**Important:** The folder name becomes the class label used by the model.

Supported image types should be normal image files such as:

```text
.jpg
.jpeg
.png
```

---

# 🍃 5. Leaf Detector Dataset Structure

The leaf detector uses two folders:

```text
leaf_detector_dataset/
│
├── leaf/
│   ├── leaf1.jpg
│   ├── leaf2.jpg
│   └── ...
│
└── non_leaf/
    ├── person.jpg
    ├── road.jpg
    ├── object.jpg
    └── ...
```

The purpose is to prevent non-leaf images from being sent to the disease model.

---

# 🐍 6. Python Environment Setup

Open CMD inside the `ai_model` folder:

```bat
cd C:\xampp\htdocs\smart_agriculture_system\ai_model
```

Create a virtual environment if needed:

```bat
python -m venv venv
```

Activate it:

```bat
venv\Scripts\activate
```

If activation works, the command line will normally show:

```text
(venv)
```

Then install the required packages:

```bat
pip install -r requirements.txt
```

Current requirements:

```text
tensorflow==2.16.1
flask==3.0.3
pillow==10.3.0
numpy==1.26.4
```

---

# 🧠 7. Train the Disease Model

Make sure the `dataset` folder is inside `ai_model`.

Run:

```bat
python train_model.py
```

Current training configuration:

```text
Image Size: 128 × 128
Batch Size: 4
Maximum Epochs: 5
Validation Split: 20%
Model: MobileNetV2
Optimizer: Adam
```

The training script creates:

```text
leaf_disease_model.keras
labels.json
```

The current model is a **31-class disease classification model**.

---

# 🍃 8. Train the Leaf Detector

Make sure this structure exists:

```text
leaf_detector_dataset/
├── leaf/
└── non_leaf/
```

Run:

```bat
python train_leaf_detector.py
```

Current configuration:

```text
Image Size: 128 × 128
Batch Size: 4
Maximum Epochs: 10
Validation Split: 20%
Model: MobileNetV2
Classes: leaf, non_leaf
```

The script creates:

```text
leaf_detector.keras
leaf_detector_labels.json
```

---

# 🧪 9. Test the Leaf Detector

Run:

```bat
python test_leaf_detector.py
```

This can be used to check whether an image is classified as:

```text
leaf
```

or:

```text
non_leaf
```

---

# 🌐 10. Start the AI Flask Server

Inside the `ai_model` folder, activate the virtual environment and run:

```bat
python app.py
```

The server runs on:

```text
http://127.0.0.1:5000
```

Available endpoints:

### Health Check

```text
GET /health
```

### Leaf / Non-Leaf Detection

```text
POST /predict_leaf
```

### Disease Detection

```text
POST /predict
```

The AI server must remain running while using the AI disease detection feature.

---

# 🔗 11. PHP + AI Connection

The PHP disease detection page is:

```text
disease_detection.php
```

It first calls:

```text
http://127.0.0.1:5000/predict_leaf
```

If the result is:

```text
leaf
```

then it calls the disease API configured in:

```text
config.php
```

Current configuration:

```php
$LOCAL_AI_API_URL = "http://127.0.0.1:5000/predict";
```

Therefore the complete connection is:

```text
Browser
   ↓
PHP
   ↓
Leaf Detector
   ↓
Disease Detector
   ↓
Treatment Library
   ↓
MySQL
   ↓
Result shown to user
```

---

# 🛑 12. Non-Leaf Image Protection

The system now checks the uploaded image before disease detection.

For example:

```text
Person photo
      ↓
Leaf Detector
      ↓
non_leaf
      ↓
Disease detection STOPPED
```

The user receives a message asking for a clear plant leaf image.

This prevents random/non-leaf images from being directly classified as plant diseases.

---

# 📊 13. Current Disease Classes

The disease model currently contains 31 classes:

```text
1. Apple___Apple_scab
2. Apple___Black_rot
3. Apple___Cedar_apple_rust
4. Apple___healthy
5. Bacterial Leaf Blight
6. Brown Spot
7. Corn_(maize)___Cercospora_leaf_spot Gray_leaf_spot
8. Corn_(maize)___Common_rust_
9. Corn_(maize)___healthy
10. Grape___Black_rot
11. Grape___Esca_(Black_Measles)
12. Grape___Leaf_blight_(Isariopsis_Leaf_Spot)
13. Grape___healthy
14. Healthy Rice Leaf
15. Leaf Blast
16. Leaf scald
17. Orange___Haunglongbing_(Citrus_greening)
18. Potato___Early_blight
19. Potato___Late_blight
20. Potato___healthy
21. Sheath Blight
22. Strawberry___Leaf_scorch
23. Strawberry___healthy
24. Tomato___Bacterial_spot
25. Tomato___Early_blight
26. Tomato___Late_blight
27. Tomato___Septoria_leaf_spot
28. Tomato___Spider_mites Two-spotted_spider_mite
29. Tomato___Tomato_Yellow_Leaf_Curl_Virus
30. Tomato___Tomato_mosaic_virus
31. Tomato___healthy
```

Leaf detector classes:

```text
1. leaf
2. non_leaf
```

---

# 🗃️ 14. Disease Result and Treatment Information

After disease prediction, the PHP system uses:

```text
includes/treatment_library.php
```

to provide:

- Disease name
- Confidence
- Symptoms
- Treatment
- Prevention

The prediction information is also stored in the database table:

```text
disease_predictions
```

---

# 🌱 15. Main Website Features

- User registration and login
- Farmer dashboard
- Buyer dashboard
- Admin dashboard
- Fertilizer and water calculator
- AI leaf disease detection
- Leaf/non-leaf image validation
- Disease treatment information
- Marketplace
- Product management
- Direct order system
- Order status tracking
- Delivery location tracking
- Live delivery map
- Voice input for forms
- Voice search
- MySQL database
- Admin management

---

# 📍 16. Delivery Tracking

The project supports delivery location tracking.

For an existing database, run:

```text
database/update_delivery_tracking.sql
```

The system uses delivery/location fields such as:

```text
delivery_lat
delivery_lng
current_lat
current_lng
location_updated_at
```

---

# 🔧 17. Common Problems

## Problem: `venv\Scripts\activate` not found

Make sure CMD is inside:

```text
C:\xampp\htdocs\smart_agriculture_system\ai_model
```

Then run:

```bat
python -m venv venv
```

After that:

```bat
venv\Scripts\activate
```

---

## Problem: Flask server is not responding

Run:

```bat
python app.py
```

Then check:

```text
http://127.0.0.1:5000/health
```

If the server is working, it should return a JSON health response.

---

## Problem: Model file not found

Check that these files are inside `ai_model`:

```text
leaf_disease_model.keras
labels.json
leaf_detector.keras
leaf_detector_labels.json
```

---

## Problem: Disease detection says Leaf Detector result not found

Make sure:

1. Flask is running.
2. `leaf_detector.keras` exists.
3. `leaf_detector_labels.json` exists.
4. Port `5000` is not blocked.
5. PHP cURL is enabled in XAMPP/PHP.

---

## Problem: Disease result is incorrect

Possible reasons:

- Dataset images are low quality.
- Dataset has too few images.
- Classes are unbalanced.
- Uploaded image is unclear.
- Lighting/background is very different from training images.

Use clear leaf images and improve the dataset if necessary.

---

# 📁 18. Main Project Structure

```text
smart_agriculture_system/
│
├── config.php
├── database/
│   ├── smart_agriculture.sql
│   └── update_delivery_tracking.sql
│
├── ai_model/
│   ├── dataset/
│   ├── leaf_detector_dataset/
│   ├── train_model.py
│   ├── train_leaf_detector.py
│   ├── test_leaf_detector.py
│   ├── check_dataset.py
│   ├── check_tensorflow_images.py
│   ├── app.py
│   ├── requirements.txt
│   ├── leaf_disease_model.keras
│   ├── labels.json
│   ├── leaf_detector.keras
│   └── leaf_detector_labels.json
│
├── disease_detection.php
├── fertilizer_calculator.php
├── marketplace.php
├── product_details.php
├── place_order.php
├── my_orders.php
├── manage_orders.php
│
├── dashboard_farmer.php
├── dashboard_buyer.php
├── dashboard_admin.php
│
├── admin_users.php
├── admin_products.php
├── admin_orders.php
│
├── css/
├── js/
├── includes/
└── uploads/
```

---

# ▶️ 19. Daily Startup Order

Every time you want to use the complete system:

### Terminal 1 — XAMPP

Start:

```text
Apache
MySQL
```

### Terminal 2 — AI Server

```bat
cd C:\xampp\htdocs\smart_agriculture_system\ai_model
venv\Scripts\activate
python app.py
```

### Browser

Open:

```text
http://localhost/smart_agriculture_system/
```

Now the website and AI disease detection can work together.

---

# ⚠️ Important Notes

- Do not delete the `.keras` model files after training unless you plan to retrain them.
- `labels.json` must match `leaf_disease_model.keras`.
- `leaf_detector_labels.json` must match `leaf_detector.keras`.
- If you retrain a model, keep its new label file with that model.
- The Flask terminal must stay open while AI detection is being used.
- The current PHP flow requires the image to pass the leaf detector before disease prediction.
- The current local AI setup uses the trained models; do not assume cloud AI fallback is active unless it is explicitly implemented in the current PHP code.

---

## ✅ Quick Commands

```bat
cd C:\xampp\htdocs\smart_agriculture_system\ai_model

venv\Scripts\activate

pip install -r requirements.txt

python train_model.py

python train_leaf_detector.py

python test_leaf_detector.py

python app.py
```

---

## 👨‍💻 Project Summary

The Smart Agriculture System combines a PHP/MySQL web application with a Python/TensorFlow AI service.

The AI part uses a **two-stage approach**:

**Stage 1:** Check whether the uploaded image is a leaf.  
**Stage 2:** If it is a leaf, identify the disease from the trained 31-class disease model.

This makes the disease detection process more controlled and reduces incorrect predictions from non-leaf images.
Dataset Link:https://www.kaggle.com/datasets/vipoooool/new-plant-diseases-dataset
