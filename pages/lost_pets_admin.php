<?php
require_once '../config/config.php';  // Убедитесь, что здесь есть подключение к базе данных
require_once '../LostPetModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$petModel = new LostPetModel($db);

// Получаем статистику
try {
    $stats = $petModel->getStatistics();
} catch (Exception $e) {
    $error = $e->getMessage();
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Делаем вставку данных в БД
                    $petType = $_POST['pet_type'];
                    $name = $_POST['name'];
                    $registrationNumber = $_POST['registration_number'];
                    $age = $_POST['age'];
                    $breed = $_POST['breed'];
                    $features = $_POST['features'];
                    $ownerPhone = $_POST['owner_phone'];
                    
                    // Обрабатываем фото
                    $mainPhoto = $_FILES['main_photo']['name'];
                    $targetDir = '../uploads/pets/';
                    $targetFile = $targetDir . basename($mainPhoto);
                    move_uploaded_file($_FILES['main_photo']['tmp_name'], $targetFile);

                    // Вставка в таблицу
                    $sql = "INSERT INTO lost_pets (pet_type, name, registration_number, age, breed, features, owner_phone, main_photo, status)
                            VALUES (:pet_type, :name, :registration_number, :age, :breed, :features, :owner_phone, :main_photo, 'pending')";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':pet_type', $petType);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':registration_number', $registrationNumber);
                    $stmt->bindParam(':age', $age);
                    $stmt->bindParam(':breed', $breed);
                    $stmt->bindParam(':features', $features);
                    $stmt->bindParam(':owner_phone', $ownerPhone);
                    $stmt->bindParam(':main_photo', $mainPhoto);
                    $stmt->execute();

                    $_SESSION['success'] = 'Животное успешно добавлено';
                    break;
                
                case 'moderate':
                    $petModel->moderatePet($_POST['pet_id'], $_POST['status'], $_POST['notes']);
                    $_SESSION['success'] = 'Статус успешно обновлен';
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Получаем список животных на модерацию - оставляем только одну версию
try {
    $pending_pets = $petModel->getPendingPets();
} catch (Exception $e) {
    $error = $e->getMessage();
    $pending_pets = [];
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Dog Breed Identifier</title>
    <link rel="stylesheet" href="../static/css/admin.css">
    <link rel="stylesheet" href="../static/css/header.css">
    <style>
        /* Добавляем стили для админ-панели */
        .admin-header {
            background: white;
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .breadcrumbs {
            display: flex;
            gap: 10px;
            align-items: center;
            color: #666;
        }

        .breadcrumbs a {
            color: #32c3f3;
            text-decoration: none;
            transition: color 0.3s;
        }

        .admin-nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-item {
            padding: 15px 25px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: #4682b4;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .nav-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(50, 195, 243, 0.15);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #32c3f3, #4682b4);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.2em;
            color: #2d3748;
            margin: 0;
        }

        .card-action {
            color: #32c3f3;
            font-size: 0.9em;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="admin-container">
    <div class="admin-header">
        <div class="breadcrumbs">
            <a href="/">Home</a>
            <span>/</span>
            <span>Admin Panel</span>
        </div>
        <a href="/" class="btn btn-primary">Back to Home</a>
    </div>

    <nav class="admin-nav">
        <a href="?section=dashboard" class="nav-item active">Dashboard</a>
        <a href="?section=pets" class="nav-item">Pets</a>
        <a href="?section=moderation" class="nav-item">Moderation</a>
        <a href="?section=settings" class="nav-item">Settings</a>
    </nav>
</div>


<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Pets</h3>
        <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <h3>Under Moderation</h3>
        <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
    </div>
    <div class="stat-card">
        <h3>Found</h3>
        <div class="stat-number"><?php echo $stats['found'] ?? 0; ?></div>
    </div>
</div>


<!-- Секция модерации -->
<div class="section" id="moderation-section" <?php echo ($_GET['section'] ?? '') !== 'moderation' ? 'style="display:none;"' : ''; ?>>
    <div class="card-header">
        <h2 class="card-title">Moderation Request</h2>
    </div>

    <?php if (!empty($pending_pets)): ?>
        <div class="pets-grid">
            <?php foreach ($pending_pets as $pet): ?>
                <div class="moderation-card">
                    <div class="pet-image">
                        <?php 
                        $imagePath = '/static/uploads/' . $pet['main_photo'];
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
                            echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($pet['name']) . '">';
                        } else {
                            echo '<div class="no-image">Нет фото</div>';
                        }
                        ?>
                    </div>
                    <div class="pet-info">
    <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
    <div class="info-grid">
        <div class="info-item">
            <span class="label">Breed:</span>
            <span class="value"><?php echo htmlspecialchars($pet['breed_name']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Age:</span>
            <span class="value"><?php echo htmlspecialchars($pet['age']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Distinctive Marks:</span>
            <span class="value"><?php echo htmlspecialchars($pet['features']); ?></span>
        </div>
        <div class="info-item">
            <span class="label">Phone:</span>
            <span class="value"><?php echo htmlspecialchars($pet['owner_phone']); ?></span>
        </div>
    </div>

    <form class="moderation-form" method="POST">
        <input type="hidden" name="action" value="moderate">
        <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">

        <div class="form-group">
            <label>Moderator's Comment:</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>

        <div class="button-group">
            <button type="submit" name="status" value="approved" class="btn btn-success">
                <i class="fas fa-check"></i> Approve
            </button>
            <button type="submit" name="status" value="rejected" class="btn btn-danger">
                <i class="fas fa-times"></i> Reject
            </button>
        </div>
    </form>
</div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <p>No moderation requests</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Обновленные стили для секции модерации */
.moderation-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    display: grid;
    grid-template-columns: 300px 1fr;
}

.pet-image {
    position: relative;
    height: 100%;
    min-height: 300px;
    background: #f8f9fa;
}

.pet-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #adb5bd;
    font-size: 1.1em;
}

.pet-info {
    padding: 25px;
}

.pet-info h3 {
    color: #32c3f3;
    margin: 0 0 20px 0;
    font-size: 1.5em;
}

.info-grid {
    display: grid;
    gap: 15px;
    margin-bottom: 20px;
}

.info-item {
    display: grid;
    gap: 5px;
}

.info-item .label {
    color: #6c757d;
    font-size: 0.9em;
}

.info-item .value {
    color: #2d3748;
    font-weight: 500;
}

.moderation-form {
    border-top: 1px solid #edf2f7;
    padding-top: 20px;
    margin-top: 20px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    resize: vertical;
    min-height: 80px;
    margin-bottom: 15px;
}

.button-group {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(135deg, #48c774, #00d1b2);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ff4757);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .moderation-card {
        grid-template-columns: 1fr; 
    }

    .pet-image {
        min-height: 200px; 
    }

    .button-group {
        flex-direction: column; /* Кнопки размещаются по вертикали */
    }
}

/* Стиль для админ-меню и кнопок на мобильных устройствах */
@media (max-width: 430px) {
    /* Админ-меню */
    .admin-nav {
        flex-direction: column; /* Меню будет располагаться вертикально */
        gap: 15px; /* Уменьшаем промежутки между элементами для компактности */
        margin-bottom: 20px; /* Меньше отступы снизу */
        padding: 10px; /* Паддинг для удобного размещения элементов */
    }

    .admin-nav a {
        text-align: center; /* Ссылки центрируются для лучшего отображения на маленьких экранах */
    }

    /* Кнопки */
    .btn {
        padding: 12px; /* Паддинг кнопки */
        border: none; /* Без рамки */
        border-radius: 8px; /* Радиус углов */
        font-weight: 500; /* Вес шрифта */
        cursor: pointer; /* Курсор в виде руки */
        transition: transform 0.2s; /* Плавное изменение при наведении */
        display: flex; /* Гибкое расположение элементов внутри кнопки */
        align-items: center; /* Центрируем элементы по вертикали */
        justify-content: center; /* Центрируем элементы по горизонтали */
        gap: 8px; /* Расстояние между иконкой и текстом */
        font-size: 14px; /* Уменьшаем размер шрифта на маленьких экранах */
    }

    /* Дополнительные стили для кнопок при наведении */
    .btn:hover {
        transform: scale(1.05); /* Немного увеличиваем кнопку при наведении */
    }
}

/* Общие стили для кнопок и навигации */
.admin-nav,
.btn {
    transition: all 0.3s ease-in-out; /* Плавные анимации для изменений */
}



</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка переключения вкладок
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.section');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSection = this.getAttribute('href').split('section=')[1];
            
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            sections.forEach(section => {
                section.style.display = 'none';
            });
            
            document.getElementById(targetSection + '-section')?.style.display = 'block';
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = {
        'dashboard': document.querySelector('.section:not(#moderation-section)'),
        'moderation': document.querySelector('#moderation-section')
    };

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.href.split('section=')[1];
            
            // Обновляем активную вкладку
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Показываем нужный раздел
            Object.values(sections).forEach(s => {
                if (s) s.style.display = 'none';
            });
            if (sections[section]) {
                sections[section].style.display = 'block';
            }
        });
    });
});
</script>
<div class="section">
    <div class="card-header">
        <h2 class="card-title">Add New Animal</h2>
        <a href="#" class="card-action">Instructions</a>
    </div>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Breed:</label>
            <select name="breed" required>
                <option value="">Select Breed</option>
                <?php
                $stmt = $db->query("SELECT breed_name FROM breeds ORDER BY breed_name");
                while ($row = $stmt->fetch()) {
                    echo '<option value="' . htmlspecialchars($row['breed_name']) . '">' . 
                         htmlspecialchars($row['breed_name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Registration Number:</label>
            <input type="text" name="registration_number">
        </div>

        <div class="form-group">
            <label>Age:</label>
            <input type="text" name="age" required>
        </div>

        <div class="form-group">
            <label>Distinct Features:</label>
            <textarea name="features" required></textarea>
        </div>

        <div class="form-group">
            <label>Owner's Phone:</label>
            <input type="tel" name="owner_phone" required>
        </div>

        <div class="form-group">
            <label>Main Photo:</label>
            <input type="file" name="main_photo" accept=".jpg,.jpeg,.png" required>
        </div>

        <div class="form-group">
            <label>Additional Photos:</label>
            <input type="file" name="additional_photos[]" accept=".jpg,.jpeg,.png" multiple>
        </div>

        <button type="submit" class="btn btn-primary">Add</button>
    </form>
</div>
</body>
</html>
