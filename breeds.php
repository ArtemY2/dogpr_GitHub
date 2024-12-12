<?php
require_once 'config/config.php';

// Получаем breed_name из URL (например, breeds.php?breed=Chihuahua)
$breed_name = isset($_GET['breed']) ? $_GET['breed'] : 'Chihuahua';

try {
    // Подготовленный запрос для получения информации о породе по имени
    $stmt = $db->prepare("SELECT * FROM breeds WHERE breed_name = :breed_name");
    $stmt->bindParam(':breed_name', $breed_name, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $breed = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Если порода не найдена, перенаправляем на Chihuahua
        header('Location: breeds.php?breed=Chihuahua');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database query error: " . $e->getMessage());
    die("데이터베이스 오류가 발생했습니다");
}

// Убедимся, что данные не равны null, используем значения по умолчанию
$page_title = htmlspecialchars($breed['breed_name'] ?? 'Без имени');
$folder_name = htmlspecialchars($breed['folder_name'] ?? 'default'); // Используем folder_name
$breed_name = htmlspecialchars($breed['breed_name'] ?? 'Без имени');

// Формируем путь к изображению
$image_path = "static/breeds/{$folder_name}/main.jpg";
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> 정보</title>
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/styles.css">
    <link rel="stylesheet" href="static/css/page.css">
    <link rel="stylesheet" href="static/css/footer.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="page-container">
        <div class="breed-header">
            <h1><?php echo $page_title; ?></h1>
        </div>

        <div class="breed-image-section">
            <?php
            if (file_exists($image_path)) {
                echo '<img src="' . $image_path . '" alt="' . $breed_name . '" class="breed-image">';
            } else {
                echo '<img src="static/images/default.jpg" alt="Изображение не доступно" class="breed-image">';
            }
            ?>
        </div>

        <div class="info-section">
            <h2>역사와 유래 및 기원</h2>
            <div class="info-content">
                <?php echo nl2br(htmlspecialchars($breed['description'] ?? 'Описание не доступно')); ?>
            </div>
        </div>

        <div class="section-divider"></div>

        <div class="info-section">
            <h2>성격과 특징</h2>
            <div class="info-content">
                <div class="characteristics">
                    <?php
                    $characteristics = explode("\n", $breed['characteristics'] ?? '');
                    foreach ($characteristics as $characteristic) {
                        if (!empty(trim($characteristic))) {
                            list($title, $description) = array_pad(explode(':', $characteristic, 2), 2, '');
                            echo "<div class='characteristic-card'>";
                            echo "<h3>" . htmlspecialchars(trim($title)) . "</h3>";
                            echo "<p>" . htmlspecialchars(trim($description)) . "</p>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    
    <script src="static/js/menu.js"></script>
</body>
</html>
