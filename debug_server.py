from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import sys
import logging
from logging.handlers import RotatingFileHandler
import traceback

# Настройка логирования
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s [%(name)s] [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        RotatingFileHandler('server.log', maxBytes=10000, backupCount=3)
    ]
)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app, resources={
    r"/*": {
        "origins": "*",
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type"]
    }
})

@app.route('/test', methods=['GET'])
def test():
    logger.info("Received test request")
    try:
        return jsonify({
            'status': 'ok',
            'message': 'Server is running'
        })
    except Exception as e:
        logger.error(f"Error in test endpoint: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/echo', methods=['POST'])
def echo():
    logger.info("Received echo request")
    try:
        data = request.get_json(silent=True) or {}
        logger.info(f"Received data: {data}")
        return jsonify({
            'status': 'ok',
            'received': data
        })
    except Exception as e:
        logger.error(f"Error in echo endpoint: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    try:
        port = 5001
        logger.info(f"Starting server on port {port}")
        
        # Проверяем, не занят ли порт
        import socket
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        result = sock.connect_ex(('localhost', port))
        if result == 0:
            logger.error(f"Port {port} is already in use")
            sys.exit(1)
        sock.close()
        
        app.run(
            host='localhost',
            port=port,
            debug=True,
            use_reloader=False  # Отключаем автоперезагрузку
        )
    except Exception as e:
        logger.error(f"Failed to start server: {str(e)}")
        logger.error(traceback.format_exc())
        sys.exit(1)