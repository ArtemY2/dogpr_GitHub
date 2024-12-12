from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import os
import sys
import traceback
import numpy as np
from PIL import Image
import tflite_runtime.interpreter as tflite
import logging
from werkzeug.utils import secure_filename
import cv2
import time

from config import *
from database import Database

# Настройка логирования
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler('debug.log')
    ]
)
logger = logging.getLogger(__name__)

# Инициализация приложения
app = Flask(__name__, static_folder='static')
CORS(app)

# Применяем конфигурацию
for key, value in APP_CONFIG.items():
    app.config[key] = value

# Создание необходимых директорий
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
os.makedirs(MODELS_FOLDER, exist_ok=True)
os.makedirs(BREEDS_FOLDER, exist_ok=True)

# Инициализация базы данных
db = Database(DB_CONFIG)

# Глобальные переменные
interpreter = None

def get_interpreter():
    global interpreter
    if interpreter is None:
        try:
            interpreter = tflite.Interpreter(model_path=MODEL_PATH)
            interpreter.allocate_tensors()
            logger.info("Model loaded successfully")
        except Exception as e:
            logger.error(f"Error loading model: {e}")
            return None
    return interpreter

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in APP_CONFIG['ALLOWED_EXTENSIONS']

def preprocess_image(image_path):
    try:
        img = Image.open(image_path)
        img = img.convert('RGB')
        img = img.resize((299, 299))
        img_array = np.array(img, dtype=np.float32) / 255.0
        return np.expand_dims(img_array, axis=0)
    except Exception as e:
        logger.error(f"Error preprocessing image: {e}")
        return None

def extract_image_features(image_path):
    try:
        img = cv2.imread(image_path)
        if img is None:
            return None
        
        img = cv2.resize(img, (299, 299))
        img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        
        features = img.flatten().astype(np.float32) / 255.0
        return features / np.linalg.norm(features)
    except Exception as e:
        logger.error(f"Error extracting features: {e}")
        return None

@app.route('/similar-images/<breed>', methods=['GET'])
def get_similar_images(breed):
    try:
        # Запрашиваем данные из обеих таблиц - lost_pets и images
        sql = """
            SELECT lp.id as pet_id,
                   lp.pet_type,
                   lp.name as pet_name,
                   lp.age,
                   lp.breed,
                   lp.features as short_description,
                   i.filename as image_path,
                   i.image_id,
                   lp.owner_phone
            FROM breed_images lp
            JOIN images i ON lp.id = i.pet_id
            WHERE lp.breed = %s 
            AND lp.status = 'approved'
            ORDER BY lp.created_at DESC
            LIMIT 4
        """
        
        connection = db.get_connection()
        cursor = connection.cursor(dictionary=True)
        cursor.execute(sql, (breed,))
        similar_images = cursor.fetchall()

        # Добавляем показатель сходства
        for image in similar_images:
            image['similarity'] = 0.8

        return jsonify(similar_images)

    except Exception as e:
        logger.error(f"Error in get_similar_images: {str(e)}")
        return jsonify([])

def find_similar_images(query_image_path, top_breed, top_k=5):
    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        connection = db.get_connection()
        if not connection:
            logger.error("Failed to get database connection")
            return []
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Получаем одобренные потерянные собаки этой породы
            cursor.execute("""
                SELECT lp.*, 
                       lp.main_photo,
                       GROUP_CONCAT(pap.file_name) as additional_photos
                FROM lost_pets lp
                LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                WHERE lp.breed_id = (SELECT breed_id FROM breeds WHERE breed_name = %s)
                AND lp.status = 'approved'
                GROUP BY lp.pet_id
            """, (top_breed,))
            
            lost_pets = cursor.fetchall()
            logger.info(f"Found {len(lost_pets)} approved lost pets for breed {top_breed}")

            similarities = []
            for pet in lost_pets:
                # Путь к главному фото в папке uploads
                main_photo_path = os.path.join(UPLOAD_FOLDER, pet['main_photo'])
                logger.info(f"Looking for main photo at: {main_photo_path}")
                
                if os.path.exists(main_photo_path):
                    features = extract_image_features(main_photo_path)
                    if features is not None:
                        similarity = float(np.dot(query_features, features))
                        pet_info = {
                            'pet_id': pet['pet_id'],
                            'name': pet['name'],
                            'age': pet['age'],
                            'breed': top_breed,
                            'features': pet['features'],
                            'image_path': pet['main_photo'],
                            'similarity': similarity,
                            'owner_phone': pet['owner_phone']
                        }
                        
                        if pet['additional_photos']:
                            additional_photos = pet['additional_photos'].split(',')
                            pet_info['additional_photos'] = additional_photos
                        
                        similarities.append(pet_info)
                        logger.info(f"Added pet with similarity {similarity}")
                else:
                    logger.error(f"Main photo not found: {main_photo_path}")

            similarities.sort(key=lambda x: x['similarity'], reverse=True)
            return similarities[:top_k]

        finally:
            cursor.close()
            connection.close()

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []
    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        connection = db.get_connection()
        if not connection:
            logger.error("Failed to get database connection")
            return []
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Получаем folder_name для породы
            breed_folder = BREED_TO_FOLDER.get(top_breed)
            if not breed_folder:
                logger.error(f"No folder found for breed: {top_breed}")
                return []
            
            # Получаем одобренные потерянные собаки этой породы
            cursor.execute("""
                SELECT lp.*, 
                       lp.original_photo_name as main_photo,
                       GROUP_CONCAT(pap.file_name) as additional_photos
                FROM lost_pets lp
                LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                WHERE lp.breed_id = %s 
                AND lp.status = 'approved'
                GROUP BY lp.pet_id
            """, (breed_folder,))
            
            lost_pets = cursor.fetchall()
            logger.info(f"Found {len(lost_pets)} approved lost pets for breed {top_breed}")

            similarities = []
            for pet in lost_pets:
                # Путь к фото в папке породы
                main_photo_path = os.path.join(BREEDS_FOLDER, breed_folder, pet['main_photo'])
                logger.info(f"Looking for main photo at: {main_photo_path}")
                
                if os.path.exists(main_photo_path):
                    features = extract_image_features(main_photo_path)
                    if features is not None:
                        similarity = float(np.dot(query_features, features))
                        pet_info = {
                            'pet_id': pet['pet_id'],
                            'name': pet['name'],
                            'age': pet['age'],
                            'breed': top_breed,
                            'features': pet['features'],
                            'folder': breed_folder,
                            'image_path': pet['main_photo'],
                            'similarity': similarity,
                            'owner_phone': pet['owner_phone']
                        }
                        
                        if pet['additional_photos']:
                            # Дополнительные фото тоже из папки породы
                            additional_photos = pet['additional_photos'].split(',')
                            pet_info['additional_photos'] = [f"{breed_folder}/{photo}" for photo in additional_photos]
                        
                        similarities.append(pet_info)
                        logger.info(f"Added pet with similarity {similarity}")
                else:
                    logger.error(f"Main photo not found: {main_photo_path}")

            similarities.sort(key=lambda x: x['similarity'], reverse=True)
            return similarities[:top_k]

        finally:
            cursor.close()
            connection.close()

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []
    try:
        connection = None
        cursor = None
        
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        similarities = []
        connection = db.get_connection()
        
        if not connection:
            logger.error("Failed to get database connection")
            return []

        cursor = connection.cursor(dictionary=True)

        # Получаем только потерянных собак с одобренным статусом
        cursor.execute("""
            SELECT b.breed_id, b.folder_name,
                   lp.pet_id, lp.name, lp.age, lp.features, 
                   lp.owner_phone, lp.original_photo_name
            FROM breeds b
            JOIN lost_pets lp ON lp.breed_id = b.breed_id
            WHERE b.breed_name = %s 
            AND lp.status = 'approved'
        """, (top_breed,))
        
        lost_pets = cursor.fetchall()
        
        if not lost_pets:
            logger.error(f"No lost pets found for breed: {top_breed}")
            return []

        breed_id = lost_pets[0]['breed_id']
        folder_name = lost_pets[0]['folder_name']

        # Обрабатываем каждую потерянную собаку
        for pet in lost_pets:
            img_path = os.path.join(BREEDS_FOLDER, folder_name, pet['original_photo_name'])
            logger.info(f"Processing image: {img_path}")
            
            if os.path.exists(img_path):
                features = extract_image_features(img_path)
                if features is not None:
                    similarity = float(np.dot(query_features, features))
                    
                    # Получаем дополнительные фото для этого питомца
                    cursor.execute("""
                        SELECT file_name, original_name 
                        FROM pet_additional_photos 
                        WHERE pet_id = %s
                    """, (pet['pet_id'],))
                    additional_photos = [p['original_name'] for p in cursor.fetchall()]

                    pet_info = {
                        'pet_id': pet['pet_id'],
                        'name': pet['name'],
                        'age': pet['age'],
                        'breed': top_breed,
                        'features': pet['features'],
                        'folder': folder_name,
                        'image_path': pet['original_photo_name'],
                        'similarity': similarity,
                        'owner_phone': pet['owner_phone']
                    }
                    
                    if additional_photos:
                        pet_info['additional_photos'] = additional_photos
                    
                    similarities.append(pet_info)
                    logger.info(f"Added lost pet {pet['name']} with similarity {similarity}")

        # Сортируем по схожести
        similarities.sort(key=lambda x: x['similarity'], reverse=True)
        return similarities[:top_k]

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []
        
    finally:
        if cursor:
            cursor.close()
        if connection and connection.is_connected():
            connection.close()
    try:
        connection = None
        cursor = None
        
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        similarities = []
        connection = db.get_connection()
        
        if not connection:
            logger.error("Failed to get database connection")
            return []

        cursor = connection.cursor(dictionary=True)

        # Первый запрос: получение информации о породе
        cursor.execute("""
            SELECT b.breed_id, b.folder_name, bp.file_name,
                   lp.pet_id, lp.name, lp.age, lp.features, lp.owner_phone,
                   lp.original_photo_name
            FROM breeds b
            JOIN breed_photos bp ON b.breed_id = bp.breed_id
            LEFT JOIN lost_pets lp ON lp.breed_id = b.breed_id
            WHERE b.breed_name = %s AND (lp.status = 'approved' OR lp.status IS NULL)
        """, (top_breed,))
        
        photos_data = cursor.fetchall()
        
        if not photos_data:
            logger.error(f"No breed or photos found for: {top_breed}")
            return []

        breed_id = photos_data[0]['breed_id']
        folder_name = photos_data[0]['folder_name']

        # Обрабатываем каждое фото
        for photo_data in photos_data:
            img_path = os.path.join(BREEDS_FOLDER, folder_name, photo_data['file_name'])
            logger.info(f"Processing image: {img_path}")
            
            if os.path.exists(img_path):
                features = extract_image_features(img_path)
                if features is not None:
                    similarity = float(np.dot(query_features, features))
                    
                    if photo_data['pet_id']:  # Если есть связанный питомец
                        # Получаем дополнительные фото для этого питомца
                        cursor.execute("""
                            SELECT file_name 
                            FROM pet_additional_photos 
                            WHERE pet_id = %s
                        """, (photo_data['pet_id'],))
                        additional_photos = [p['file_name'] for p in cursor.fetchall()]

                        pet_info = {
                            'pet_id': photo_data['pet_id'],
                            'name': photo_data['name'],
                            'age': photo_data['age'],
                            'breed': top_breed,
                            'features': photo_data['features'],
                            'folder': folder_name,
                            'image_path': photo_data['file_name'],
                            'similarity': similarity,
                            'owner_phone': photo_data['owner_phone']
                        }
                        
                        if additional_photos:
                            pet_info['additional_photos'] = additional_photos
                        
                        similarities.append(pet_info)
                        logger.info(f"Added pet with similarity {similarity}")

        # Сортируем по схожести
        similarities.sort(key=lambda x: x['similarity'], reverse=True)
        return similarities[:top_k]

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []
        
    finally:
        if cursor:
            cursor.close()
        if connection and connection.is_connected():
            connection.close()
    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        similarities = []
        connection = db.get_connection()
        
        if not connection:
            logger.error("Failed to get database connection")
            return []

        # Получаем breed_id и folder_name
        with connection.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT breed_id, folder_name 
                FROM breeds 
                WHERE breed_name = %s
            """, (top_breed,))
            breed_info = cursor.fetchone()

        if not breed_info:
            logger.error(f"No breed found: {top_breed}")
            return []

        breed_id = breed_info['breed_id']
        folder_name = breed_info['folder_name']

        # Получаем фотографии породы
        with connection.cursor(dictionary=True) as cursor:
            cursor.execute("""
                SELECT file_name 
                FROM breed_photos 
                WHERE breed_id = %s
            """, (breed_id,))
            breed_photos = cursor.fetchall()

        logger.info(f"Found {len(breed_photos)} photos for breed {top_breed}")

        for photo in breed_photos:
            img_path = os.path.join(BREEDS_FOLDER, folder_name, photo['file_name'])
            logger.info(f"Processing image: {img_path}")
            
            if os.path.exists(img_path):
                features = extract_image_features(img_path)
                if features is not None:
                    similarity = float(np.dot(query_features, features))
                    
                    # Получаем информацию о потерянном питомце
                    with connection.cursor(dictionary=True) as cursor:
                        cursor.execute("""
                            SELECT * 
                            FROM lost_pets 
                            WHERE breed_id = %s 
                            AND original_photo_name = %s 
                            AND status = 'approved'
                        """, (breed_id, photo['file_name']))
                        lost_pet = cursor.fetchone()
                    
                    if lost_pet:
                        # Получаем дополнительные фотографии
                        with connection.cursor(dictionary=True) as cursor:
                            cursor.execute("""
                                SELECT file_name 
                                FROM pet_additional_photos 
                                WHERE pet_id = %s
                            """, (lost_pet['pet_id'],))
                            additional_photos = [p['file_name'] for p in cursor.fetchall()]

                        pet_info = {
                            'pet_id': lost_pet['pet_id'],
                            'name': lost_pet['name'],
                            'age': lost_pet['age'],
                            'breed': top_breed,
                            'features': lost_pet['features'],
                            'folder': folder_name,
                            'image_path': photo['file_name'],
                            'similarity': similarity,
                            'owner_phone': lost_pet['owner_phone']
                        }
                        
                        if additional_photos:
                            pet_info['additional_photos'] = additional_photos
                        
                        similarities.append(pet_info)
                        logger.info(f"Added pet with similarity {similarity}")

        # Закрываем соединение
        if connection.is_connected():
            connection.close()

        # Сортируем по схожести
        similarities.sort(key=lambda x: x['similarity'], reverse=True)
        return similarities[:top_k]

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        if connection and connection.is_connected():
            connection.close()
        return []
    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        connection = db.get_connection()
        if not connection:
            logger.error("Failed to get database connection")
            return []
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Сначала получаем breed_id и folder_name
            cursor.execute("""
                SELECT breed_id, folder_name 
                FROM breeds 
                WHERE breed_name = %s
            """, (top_breed,))
            breed_info = cursor.fetchone()
            
            if not breed_info:
                logger.error(f"No breed found: {top_breed}")
                return []
            
            breed_id = breed_info['breed_id']
            folder_name = breed_info['folder_name']
            
            # Получаем все фото породы
            cursor.execute("""
                SELECT file_name 
                FROM breed_photos 
                WHERE breed_id = %s
            """, (breed_id,))
            breed_photos = cursor.fetchall()
            
            logger.info(f"Found {len(breed_photos)} photos for breed {top_breed}")

            similarities = []
            for photo in breed_photos:
                # Проверяем фото
                img_path = os.path.join(BREEDS_FOLDER, folder_name, photo['file_name'])
                logger.info(f"Processing image: {img_path}")
                
                if os.path.exists(img_path):
                    features = extract_image_features(img_path)
                    if features is not None:
                        similarity = float(np.dot(query_features, features))
                        
                        # Ищем связанного питомца
                        cursor.execute("""
                            SELECT * 
                            FROM lost_pets 
                            WHERE breed_id = %s 
                            AND original_photo_name = %s 
                            AND status = 'approved'
                        """, (breed_id, photo['file_name']))
                        lost_pet = cursor.fetchone()
                        
                        if lost_pet:
                            # Получаем дополнительные фото
                            cursor.execute("""
                                SELECT file_name 
                                FROM pet_additional_photos 
                                WHERE pet_id = %s
                            """, (lost_pet['pet_id'],))
                            additional_photos = [p['file_name'] for p in cursor.fetchall()]
                            
                            pet_info = {
                                'pet_id': lost_pet['pet_id'],
                                'name': lost_pet['name'],
                                'age': lost_pet['age'],
                                'breed': top_breed,
                                'features': lost_pet['features'],
                                'folder': folder_name,
                                'image_path': photo['file_name'],
                                'similarity': similarity,
                                'owner_phone': lost_pet['owner_phone']
                            }
                            
                            if additional_photos:
                                pet_info['additional_photos'] = additional_photos
                            
                            similarities.append(pet_info)
                            logger.info(f"Added pet with similarity {similarity}")

            similarities.sort(key=lambda x: x['similarity'], reverse=True)
            return similarities[:top_k]

        finally:
            if cursor:
                cursor.close()
            if connection.is_connected():
                connection.close()

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []

    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        connection = db.get_connection()
        if not connection:
            logger.error("Failed to get database connection")
            return []
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Получаем данные о породе и фотографиях для нее
            cursor.execute("""
                SELECT bp.*, b.breed_name, b.folder_name
                FROM breed_photos bp
                JOIN breeds b ON bp.breed_id = b.breed_id
                JOIN lost_pets lp ON lp.breed_id = b.breed_id
                WHERE b.breed_name = %s 
                AND lp.status = 'approved'
                GROUP BY bp.photo_id
            """, (top_breed,))
            
            breed_photos = cursor.fetchall()
            logger.info(f"Found {len(breed_photos)} photos for breed {top_breed}")

            similarities = []
            for photo in breed_photos:
                # Ищем фото в папке породы
                img_path = os.path.join(BREEDS_FOLDER, photo['folder_name'], photo['file_name'])
                logger.info(f"Processing image: {img_path}")
                
                if os.path.exists(img_path):
                    features = extract_image_features(img_path)
                    if features is not None:
                        similarity = float(np.dot(query_features, features))
                        
                        # Получаем информацию о потерянном питомце
                        cursor.execute("""
                            SELECT lp.* 
                            FROM lost_pets lp
                            WHERE lp.breed_id = %s 
                            AND lp.original_photo_name = %s 
                            AND lp.status = 'approved'
                        """, (photo['breed_id'], photo['file_name']))
                        
                        pet_info = cursor.fetchone()
                        
                        if pet_info:
                            # Получаем дополнительные фотографии
                            cursor.execute("""
                                SELECT file_name 
                                FROM pet_additional_photos 
                                WHERE pet_id = %s
                            """, (pet_info['pet_id'],))
                            
                            additional_photos = cursor.fetchall()
                            
                            similar_info = {
                                'pet_id': pet_info['pet_id'],
                                'name': pet_info['name'],
                                'age': pet_info['age'],
                                'breed': top_breed,
                                'features': pet_info['features'],
                                'folder': photo['folder_name'],
                                'image_path': photo['file_name'],
                                'similarity': similarity,
                                'owner_phone': pet_info['owner_phone']
                            }
                            
                            if additional_photos:
                                similar_info['additional_photos'] = [p['file_name'] for p in additional_photos]
                            
                            similarities.append(similar_info)
                            logger.info(f"Added pet with similarity {similarity}")
                else:
                    logger.error(f"Image not found: {img_path}")

            similarities.sort(key=lambda x: x['similarity'], reverse=True)
            return similarities[:top_k]

        finally:
            cursor.close()
            connection.close()

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []


    try:
        # Получаем признаки запроса
        query_features = extract_image_features(query_image_path)
        if query_features is None:
            logger.error("Failed to extract features from query image")
            return []

        connection = db.get_connection()
        if not connection:
            logger.error("Failed to get database connection")
            return []
            
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Сначала получаем breed_id и folder_name
            cursor.execute("""
                SELECT breed_id, folder_name 
                FROM breeds 
                WHERE breed_name = %s
            """, (top_breed,))
            breed_result = cursor.fetchone()
            
            if not breed_result:
                logger.error(f"No breed found: {top_breed}")
                return []

            breed_id = breed_result['breed_id']
            folder_name = breed_result['folder_name']
            breed_folder = os.path.join(BREEDS_FOLDER, folder_name)
            
            # Получаем одобренные потерянные собаки этой породы
            cursor.execute("""
                SELECT lp.*, 
                       lp.original_photo_name as main_photo,
                       GROUP_CONCAT(pap.file_name) as additional_photos
                FROM lost_pets lp
                LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                WHERE lp.breed_id = %s 
                AND lp.status = 'approved'
                GROUP BY lp.pet_id
            """, (breed_id,))
            
            lost_pets = cursor.fetchall()
            logger.info(f"Found {len(lost_pets)} approved lost pets for breed {top_breed}")

            similarities = []
            for pet in lost_pets:
                # Ищем фото в папке породы
                orig_name = pet['main_photo']
                img_path = os.path.join(breed_folder, orig_name)
                logger.info(f"Looking for image at: {img_path}")
                
                if os.path.exists(img_path):
                    features = extract_image_features(img_path)
                    if features is not None:
                        similarity = float(np.dot(query_features, features))
                        pet_info = {
                            'pet_id': pet['pet_id'],
                            'name': pet['name'],
                            'age': pet['age'],
                            'breed': top_breed,
                            'features': pet['features'],
                            'folder': folder_name,
                            'image_path': orig_name,
                            'similarity': similarity,
                            'owner_phone': pet['owner_phone']
                        }
                        
                        if pet['additional_photos']:
                            additional_photos = pet['additional_photos'].split(',')
                            pet_info['additional_photos'] = additional_photos
                        
                        similarities.append(pet_info)
                        logger.info(f"Added pet with similarity {similarity}")
                else:
                    logger.error(f"Image not found: {img_path}")

            similarities.sort(key=lambda x: x['similarity'], reverse=True)
            return similarities[:top_k]

        finally:
            cursor.close()
            connection.close()

    except Exception as e:
        logger.error(f"Error in find_similar_images: {str(e)}")
        logger.error(traceback.format_exc())
        return []


def predict(filepath, top_breed, filename):
    try:
        # Step 1: Find similar images based on the query image
        similar_images = find_similar_images(filepath, top_breed)
        logger.info(f"Found {len(similar_images)} similar images")

        # Step 2: Prepare response with predictions and similar images
        response = {
    'predictions': results,
    'similar_images': similar_images,
    'image_path': f'/static/static/breeds/{filename}'  # Обновляем путь
}

        logger.debug(f"Response data: {response}")
        return jsonify(response), 200

    except Exception as e:
        logger.error(f"Error in predict: {str(e)}")
        logger.error(traceback.format_exc())
        return jsonify({'error': str(e)}), 500


@app.route('/breed-details/<breed>', methods=['GET'])
def get_breed_details(breed):
    """Получает детальную информацию о породе"""
    details = db.get_breed_details(breed)
    if details:
        return jsonify({
            'breed_name': details['breed_name'],
            'description': details['description'] or '정보가 없습니다'
        }), 200
    return jsonify({'error': '품종 정보를 찾을 수 없습니다'}), 404

@app.route('/breed-characteristics/<breed>', methods=['GET'])
def get_breed_characteristics(breed):
    """Получает характеристики породы"""
    details = db.get_breed_details(breed)
    if details:
        return jsonify({
            'breed_name': details['breed_name'],
            'content': details['characteristics'] or '정보가 없습니다'
        }), 200
    return jsonify({'error': '품종 특징을 찾을 수 없습니다'}), 404

@app.route('/pet-details/<int:image_id>', methods=['GET'])
def get_pet_details(image_id):
    """Получает детальную информацию о питомце"""
    details = db.get_pet_details(image_id)
    if details:
        return jsonify(details), 200
    return jsonify({'error': '이미지를 찾을 수 없습니다'}), 404

@app.route('/predict', methods=['POST'])
def predict():
    try:
        logger.info("Starting prediction request")
        
        if 'image' not in request.files:
            return jsonify({'error': '이미지를 선택해주세요'}), 400

        file = request.files['image']
        if not file or not file.filename:
            return jsonify({'error': '파일이 선택되지 않았습니다'}), 400

        if not allowed_file(file.filename):
            return jsonify({'error': '지원되지 않는 파일 형식입니다'}), 400

        # Сохраняем изображение с уникальным именем
        filename = secure_filename(f"{int(time.time())}_{file.filename}")
        filepath = os.path.join(UPLOAD_FOLDER, filename)
        file.save(filepath)
        logger.info(f"File saved to {filepath}")

        # Предобработка изображения
        img_array = preprocess_image(filepath)
        if img_array is None:
            return jsonify({'error': '이미지 처리 오류'}), 500

        # Получаем предсказания
        interpreter = get_interpreter()
        if interpreter is None:
            return jsonify({'error': '모델 로딩 오류'}), 500

        try:
            input_details = interpreter.get_input_details()
            output_details = interpreter.get_output_details()
            
            interpreter.set_tensor(input_details[0]['index'], img_array)
            interpreter.invoke()
            predictions = interpreter.get_tensor(output_details[0]['index'])[0]
            logger.info("Model prediction successful")
        except Exception as e:
            logger.error(f"Error during model prediction: {str(e)}")
            return jsonify({'error': '모델 예측 오류'}), 500

        # Формируем результаты
        results = []
        for idx, prob in enumerate(predictions):
            if idx < len(BREEDS):
                breed_name = BREEDS[idx]
                breed_info = db.get_breed_info(breed_name)
                
                result = {
                    'breed': breed_name,
                    'probability': float(prob * 100)
                }
                
                if breed_info:
                    result.update({
                        'description': breed_info['description'],
                        'characteristics': breed_info['characteristics']
                    })
                
                results.append(result)

        # Сортируем по вероятности
        results.sort(key=lambda x: x['probability'], reverse=True)
        top_breed = results[0]['breed']
        
        logger.info(f"Top predicted breed: {top_breed} with probability: {results[0]['probability']}%")

        # Получаем похожие изображения
        try:
            similar_images = find_similar_images(filepath, top_breed)
            logger.info(f"Found {len(similar_images)} similar images")
        except Exception as e:
            logger.error(f"Error finding similar images: {str(e)}")
            similar_images = []

        # Готовим ответ с правильными путями к изображениям
        formatted_similar_images = []
        for img in similar_images:
            # Используем пути относительно /static/breeds/
            img_info = {
                'pet_id': img['pet_id'],
                'name': img['name'],
                'age': img['age'],
                'breed': img['breed'],
                'features': img['features'],
                'image_path': img['image_path'],
                'similarity': img['similarity'],
                'owner_phone': img['owner_phone']
            }
            
            if 'additional_photos' in img:
                img_info['additional_photos'] = img['additional_photos']
            
            formatted_similar_images.append(img_info)

        response = {
            'predictions': results,
            'similar_images': formatted_similar_images,
            'image_path': f'/static/uploads/{filename}'
        }

        return jsonify(response), 200

    except Exception as e:
        logger.error(f"Error in predict: {str(e)}")
        logger.error(traceback.format_exc())
        return jsonify({'error': str(e)}), 500
    try:
        logger.info("Starting prediction request")
        
        if 'image' not in request.files:
            return jsonify({'error': '이미지를 선택해주세요'}), 400

        file = request.files['image']
        if not file or not file.filename:
            return jsonify({'error': '파일이 선택되지 않았습니다'}), 400

        if not allowed_file(file.filename):
            return jsonify({'error': '지원되지 않는 파일 형식입니다'}), 400

        # Создаем папку pets, если её нет
        pets_folder = os.path.join(app.config['UPLOAD_FOLDER'], 'pets')
        os.makedirs(pets_folder, exist_ok=True)

        # Сохраняем изображение в папку pets с уникальным именем
        filename = secure_filename(f"{int(time.time())}_{file.filename}")
        filepath = os.path.join(pets_folder, filename)
        file.save(filepath)
        logger.info(f"File saved to {filepath}")

        # Предобработка изображения
        img_array = preprocess_image(filepath)
        if img_array is None:
            return jsonify({'error': '이미지 처리 오류'}), 500

        # Получаем предсказания
        interpreter = get_interpreter()
        if interpreter is None:
            return jsonify({'error': '모델 로딩 오류'}), 500

        try:
            input_details = interpreter.get_input_details()
            output_details = interpreter.get_output_details()
            
            interpreter.set_tensor(input_details[0]['index'], img_array)
            interpreter.invoke()
            predictions = interpreter.get_tensor(output_details[0]['index'])[0]
            logger.info("Model prediction successful")
        except Exception as e:
            logger.error(f"Error during model prediction: {str(e)}")
            return jsonify({'error': '모델 예측 오류'}), 500

        # Формируем результаты
        results = []
        for idx, prob in enumerate(predictions):
            if idx < len(BREEDS):
                breed_name = BREEDS[idx]
                breed_info = db.get_breed_info(breed_name)
                
                result = {
                    'breed': breed_name,
                    'probability': float(prob * 100)
                }
                
                if breed_info:
                    result.update({
                        'description': breed_info['description'],
                        'characteristics': breed_info['characteristics']
                    })
                
                results.append(result)

        # Сортируем по вероятности
        results.sort(key=lambda x: x['probability'], reverse=True)
        top_breed = results[0]['breed']
        
        logger.info(f"Top predicted breed: {top_breed} with probability: {results[0]['probability']}%")

        # Получаем похожие изображения
        try:
            similar_images = find_similar_images(filepath, top_breed)
            logger.info(f"Found {len(similar_images)} similar images")
        except Exception as e:
            logger.error(f"Error finding similar images: {str(e)}")
            similar_images = []

        response = {
            'predictions': results,
            'similar_images': similar_images,
            'image_path': f'/uploads/pets/{filename}'  # Обновленный путь к загруженному файлу
        }

        return jsonify(response), 200

    except Exception as e:
        logger.error(f"Error in predict: {str(e)}")
        logger.error(traceback.format_exc())
        return jsonify({'error': str(e)}), 500


def init_app():
    """Инициализация приложения"""
    try:
        # Проверяем наличие необходимых директорий
        for directory in [UPLOAD_FOLDER, MODELS_FOLDER, BREEDS_FOLDER]:
            if not os.path.exists(directory):
                os.makedirs(directory)

        # Проверяем подключение к базе данных
        if not db.test_connection():
            logger.error("Database connection test failed")
            sys.exit(1)

        # Проверяем наличие модели
        if not os.path.exists(MODEL_PATH):
            logger.error(f"Model file not found: {MODEL_PATH}")
            sys.exit(1)

        # Пробуем загрузить модель
        interpreter = get_interpreter()
        if interpreter is None:
            logger.error("Failed to load model")
            sys.exit(1)

        logger.info("Application initialized successfully")
    except Exception as e:
        logger.error(f"Error initializing application: {e}")
        sys.exit(1)

if __name__ == '__main__':
    init_app()
    app.run(host='0.0.0.0', port=5001, debug=False)