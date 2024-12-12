<?php
require_once 'config/config.php';

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Проверяем соединение с базой данных
try {
    // Получаем список потерянных питомцев с правильной обработкой ошибок
    $sql = "SELECT * FROM lost_pets ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("데이터베이스 연결 오류가 발생했습니다.");
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>실종 동물 찾기</title>
    <link rel="stylesheet" href="/static/css/header.css">
    <link rel="stylesheet" href="/static/css/styles.css">
    <link rel="stylesheet" href="/static/css/lost-pets.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="search-section">
            <div class="section-header">
                <h1>실종 동물 찾기</h1>
                <p class="section-description">등록된 실종 동물 목록입니다</p>
            </div>

            <?php if (empty($pets)): ?>
                <div class="no-pets-message">
                    등록된 실종 동물이 없습니다.
                </div>
            <?php else: ?>
                <div class="pets-grid">
                    <?php foreach ($pets as $pet): ?>
                        <div class="pet-card">
                            <div class="pet-image">
                                <?php if (!empty($pet['main_photo'])): ?>
                                    <img src="/uploads/pets/<?php echo htmlspecialchars($pet['main_photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($pet['name']); ?>"
                                         onerror="this.src='/static/images/no-image.jpg'">
                                <?php else: ?>
                                    <img src="/static/images/no-image.jpg" alt="No image available">
                                <?php endif; ?>
                            </div>
                            <div class="pet-info">
                                <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                                <p>종류: <?php echo $pet['pet_type'] === 'dog' ? '강아지' : '고양이'; ?></p>
                                <p>품종: <?php echo htmlspecialchars($pet['breed']); ?></p>
                                <p>나이: <?php echo htmlspecialchars($pet['age']); ?></p>
                                <p>특징: <?php echo htmlspecialchars($pet['features']); ?></p>
                                <div class="contact-button">
                                    <a href="tel:<?php echo htmlspecialchars($pet['owner_phone']); ?>" 
                                       class="btn btn-primary">연락하기</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>