# 🐶 Dog Breed Classifier

This is a **dog breed recognition system** built as a graduation project. It uses deep learning to classify dog breeds from images and provides a simple web interface for users to upload and identify dogs.

---

## 📌 Project Summary

- Classifies **multiple dog breeds** from images
- Based on **InceptionV3**, **VGG-16**, and **PCA**
- Includes a **Flask web app**
- Uses **MySQL** to store predictions
- Designed for **local deployment** (can be extended to cloud)

---

## 🧠 Model Architecture

- **InceptionV3** and **VGG-16** pretrained on ImageNet
- Feature extraction + dimensionality reduction using **PCA**
- Custom classifier layers added for breed prediction
- Model trained with **early stopping** and **learning rate scheduling**

---

## 🧪 Technologies Used

- Python (TensorFlow, Keras, NumPy, scikit-learn)
- Flask (for web interface)
- MySQL (for database)
- HTML/CSS (simple frontend)
- PIL / OpenCV (image preprocessing)

---

## ⚙️ Project Structure

