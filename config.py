import os

# Базовые пути
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
UPLOAD_FOLDER = os.path.join(BASE_DIR, 'static', 'uploads')  # Изменено
MODELS_FOLDER = os.path.join(BASE_DIR, 'models')
BREEDS_FOLDER = os.path.join(BASE_DIR, 'static', 'breeds')
MODEL_PATH = os.path.join(MODELS_FOLDER, 'model.tflite')

# Конфигурация базы данных
DB_CONFIG = {
    'host': 'localhost',    
    'user': 'root',
    'password': '1111',
    'database': 'dog_breeds_db'
}

# config.py

APP_CONFIG = {
    'UPLOAD_FOLDER': UPLOAD_FOLDER,
    'MODELS_FOLDER': MODELS_FOLDER,
    'BREEDS_FOLDER': BREEDS_FOLDER,
    'ALLOWED_EXTENSIONS': {'png', 'jpg', 'jpeg'},
    'MAX_CONTENT_LENGTH': 16 * 1024 * 1024  # 16MB
}

# Список пород
BREEDS = [
    'Chihuahua',          # 1
    'Yorkshire_terrier',   # 2
    'Maltese',            # 3
    'Jindo',              # 4
    'Border_Collie',      # 5
    'Pomeranian',         # 6
    'Poodle'              # 7
]

# Словари для преобразования
FOLDER_TO_BREED = {str(i): breed for i, breed in enumerate(BREEDS, 1)}
BREED_TO_FOLDER = {breed: str(i) for i, breed in enumerate(BREEDS, 1)}