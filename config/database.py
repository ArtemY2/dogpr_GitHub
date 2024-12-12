import mysql.connector
from mysql.connector import Error
import logging
from typing import Optional, Dict, Any

logger = logging.getLogger(__name__)

class Database:
    def __init__(self, config: Dict[str, str]):
        self.config = config

    def get_connection(self):
        try:
            connection = mysql.connector.connect(**self.config)
            return connection
        except Error as e:
            logger.error(f"Error connecting to MySQL: {e}")
            return None

    def get_breed_info(self, breed_name: str) -> Optional[Dict[str, Any]]:
        """Получает информацию о породе из базы данных"""
        connection = self.get_connection()
        if not connection:
            return None
        
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("""
                SELECT *
                FROM breeds
                WHERE breed_name = %s
            """, (breed_name,))
            return cursor.fetchone()
        except Error as e:
            logger.error(f"Error fetching breed info: {e}")
            return None
        finally:
            if connection.is_connected():
                cursor.close()
                connection.close()

    def get_breed_details(self, breed_name: str) -> Optional[Dict[str, Any]]:
        """Получает детальную информацию о породе"""
        connection = self.get_connection()
        if not connection:
            return None
        
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("""
                SELECT breed_id, breed_name, description, characteristics
                FROM breeds
                WHERE breed_name = %s
            """, (breed_name,))
            return cursor.fetchone()
        except Error as e:
            logger.error(f"Error fetching breed details: {e}")
            return None
        finally:
            if connection.is_connected():
                cursor.close()
                connection.close()

    def get_image_info(self, breed_name: str, filename: str) -> Optional[Dict[str, Any]]:
        """Получает информацию об изображении"""
        connection = self.get_connection()
        if not connection:
            return None
        
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("""
                SELECT i.*, b.breed_name
                FROM images i
                JOIN breeds b ON i.breed_id = b.breed_id
                WHERE b.breed_name = %s AND i.filename = %s
            """, (breed_name, filename))
            return cursor.fetchone()
        except Error as e:
            logger.error(f"Error fetching image info: {e}")
            return None
        finally:
            if connection.is_connected():
                cursor.close()
                connection.close()

    def get_pet_details(self, image_id: int) -> Optional[Dict[str, Any]]:
        """Получает детальную информацию о питомце"""
        connection = self.get_connection()
        if not connection:
            return None
        
        try:
            cursor = connection.cursor(dictionary=True)
            cursor.execute("""
                SELECT i.*, b.breed_name, b.description as breed_description
                FROM images i
                JOIN breeds b ON i.breed_id = b.breed_id
                WHERE i.image_id = %s
            """, (image_id,))
            return cursor.fetchone()
        except Error as e:
            logger.error(f"Error fetching pet details: {e}")
            return None
        finally:
            if connection.is_connected():
                cursor.close()
                connection.close()

    def test_connection(self) -> bool:
        """Проверяет подключение к базе данных"""
        try:
            connection = self.get_connection()
            if connection:
                cursor = connection.cursor()
                cursor.execute("SELECT 1")
                cursor.fetchone()
                cursor.close()
                connection.close()
                logger.info("Database connection successful")
                return True
            return False
        except Error as e:
            logger.error(f"Database connection test failed: {e}")
            return False