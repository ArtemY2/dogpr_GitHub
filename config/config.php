<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Base configuration
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('UPLOAD_DIR', BASE_PATH . '/uploads/pets/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Настройки для потерянных животных
define('LOST_PETS_UPLOAD_DIR', '/uploads/lost_pets/');
define('MAX_PET_PHOTOS', 5); // максимальное количество фото
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '1111');
define('DB_NAME', 'dog_breeds_db');

// Check directories with error handling
function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        try {
            if (!mkdir($path, 0775, true)) {
                error_log("Failed to create directory: " . $path);
            }
        } catch (Exception $e) {
            error_log("Error creating directory: " . $e->getMessage());
        }
    }
}

// Create required directories
$directories = [
    BASE_PATH . '/uploads',
    BASE_PATH . '/uploads/pets'
];

foreach ($directories as $dir) {
    ensureDirectoryExists($dir);
}

// Database connection
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Set default timezone
date_default_timezone_set('Asia/Seoul');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}