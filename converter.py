import tensorflow as tf
import os
import logging
import traceback
from datetime import datetime

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('converter.log')
    ]
)
logger = logging.getLogger(__name__)

def convert_to_tflite(keras_model_path, output_path):
    """
    Конвертация Keras модели InceptionV3 в TFLite формат
    
    Args:
        keras_model_path (str): Путь к файлу .keras модели
        output_path (str): Путь для сохранения .tflite модели
    """
    try:
        # Проверяем существование исходной модели
        if not os.path.exists(keras_model_path):
            raise FileNotFoundError(f"Модель не найдена: {keras_model_path}")
            
        logger.info(f"Начинаем конвертацию модели: {keras_model_path}")
        logger.info(f"Размер исходного файла: {os.path.getsize(keras_model_path) / (1024*1024):.2f} MB")
        
        # Загружаем модель
        logger.info("Загружаем Keras модель...")
        model = tf.keras.models.load_model(keras_model_path)
        logger.info("Модель успешно загружена")
        
        # Проверяем входную размерность
        input_shape = model.input_shape
        logger.info(f"Входная размерность модели: {input_shape}")
        
        if input_shape[1:3] != (299, 299):
            logger.warning("Внимание: входная размерность модели не 299x299!")
        
        # Создаем конвертер
        logger.info("Инициализация конвертера...")
        converter = tf.lite.TFLiteConverter.from_keras_model(model)
        
        # Настраиваем оптимизации
        logger.info("Настройка параметров конвертации...")
        converter.optimizations = [tf.lite.Optimize.DEFAULT]
        converter.target_spec.supported_types = [tf.float32]
        
        # Дополнительные настройки
        converter.target_spec.supported_ops = [
            tf.lite.OpsSet.TFLITE_BUILTINS,  # Использовать встроенные операции
            tf.lite.OpsSet.SELECT_TF_OPS  # Использовать операции TF если нужно
        ]
        
        # Включаем оптимизацию размера модели
        converter.target_spec.supported_ops = [tf.lite.OpsSet.TFLITE_BUILTINS_INT8]
        converter.optimization_default_target_spec = tf.lite.Spec()
        converter.representative_dataset = None
        
        # Конвертируем модель
        logger.info("Начало конвертации...")
        start_time = datetime.now()
        
        try:
            tflite_model = converter.convert()
            logger.info("Конвертация успешно завершена")
        except Exception as e:
            logger.error(f"Ошибка при конвертации: {e}")
            logger.error(traceback.format_exc())
            raise
            
        conversion_time = (datetime.now() - start_time).total_seconds()
        logger.info(f"Время конвертации: {conversion_time:.2f} секунд")
        
        # Сохраняем модель
        logger.info(f"Сохранение модели в {output_path}...")
        try:
            with open(output_path, 'wb') as f:
                f.write(tflite_model)
        except Exception as e:
            logger.error(f"Ошибка при сохранении модели: {e}")
            raise
            
        # Проверяем размер полученной модели
        tflite_size = os.path.getsize(output_path) / (1024*1024)
        logger.info(f"Размер TFLite модели: {tflite_size:.2f} MB")
        
        # Проверяем модель
        logger.info("Проверка сконвертированной модели...")
        interpreter = tf.lite.Interpreter(model_path=output_path)
        interpreter.allocate_tensors()
        
        # Получаем информацию о входном тензоре
        input_details = interpreter.get_input_details()
        output_details = interpreter.get_output_details()
        
        logger.info(f"Входной тензор: shape={input_details[0]['shape']}, dtype={input_details[0]['dtype']}")
        logger.info(f"Выходной тензор: shape={output_details[0]['shape']}, dtype={output_details[0]['dtype']}")
        
        logger.info("Конвертация успешно завершена!")
        return True
        
    except Exception as e:
        logger.error(f"Ошибка в процессе конвертации: {str(e)}")
        logger.error(traceback.format_exc())
        return False

if __name__ == "__main__":
    # Пути к файлам
    keras_model = "best_model.keras"  # Путь к вашей модели
    tflite_model = "model_01.tflite"  # Путь для сохранения
    
    # Проверяем наличие CUDA
    logger.info(f"TensorFlow version: {tf.__version__}")
    logger.info(f"GPU available: {tf.config.list_physical_devices('GPU')}")
    
    # Запускаем конвертацию
    success = convert_to_tflite(keras_model, tflite_model)
    
    if success:
        logger.info("Программа успешно завершена")
    else:
        logger.error("Программа завершена с ошибками")