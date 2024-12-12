<?php
require_once '../config/config.php';
require_once '../LostPetModel.php';

$petModel = new LostPetModel($db);

// Получение ID животного из параметров
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pet_id) {
    header('Location: index.php');
    exit();
}

// Получение информации о животном
$pet = $petModel->getPetById($pet_id);

if (!$pet || $pet['status'] !== 'approved') {
    header('Location: index.php');
    exit();
}

// Получение похожих животных
$similar_pets = $petModel->getSimilarPets($pet['breed'], 4);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о животном - <?php echo htmlspecialchars($pet['name']); ?></title>
    <link rel="stylesheet" href="../static/css/style.css">
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="container">
        <div class="pet-details">
            <div class="pet-header">
                <h1><?php echo htmlspecialchars($pet['name']); ?></h1>
                <p class="breed"><?php echo htmlspecialchars($pet['breed']); ?></p>
            </div>

            <div class="pet-photos">
                <div class="main-photo">
                    <img src="<?php echo SITE_URL . $pet['main_photo']; ?>" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>">
                </div>

                <?php if (!empty($pet['additional_photos'])): ?>
                    <div class="additional-photos">
                        <?php foreach (explode(',', $pet['additional_photos']) as $photo): ?>
                            <div class="photo-thumbnail">
                                <img src="<?php echo SITE_URL . $photo; ?>" 
                                     alt="Дополнительное фото">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pet-info">
                <div class="info-section">
                    <h2>Информация о животном</h2>
                    <ul>
                        <li><strong>Возраст:</strong> <?php echo htmlspecialchars($pet['age']); ?></li>
                        <li><strong>Особые приметы:</strong> <?php echo htmlspecialchars($pet['features']); ?></li>
                        <?php if ($pet['registration_number']): ?>
                            <li><strong>Регистрационный номер:</strong> 
                                <?php echo htmlspecialchars($pet['registration_number']); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="contact-section">
                    <h2>Контактная информация</h2>
                    <p class="phone">
                        <strong>Телефон для связи:</strong>
                        <a href="tel:<?php echo htmlspecialchars($pet['owner_phone']); ?>">
                            <?php echo htmlspecialchars($pet['owner_phone']); ?>
                        </a>
                    </p>
                </div>
            </div>

            <?php if (!empty($similar_pets)): ?>
                <div class="similar-pets">
                    <h2>Похожие животные</h2>
                    <div class="pets-grid">
                    <?php foreach ($similar_pets as $similar_pet): ?>
                            <div class="pet-card">
                                <img src="<?php echo SITE_URL . $similar_pet['main_photo']; ?>" 
                                     alt="<?php echo htmlspecialchars($similar_pet['name']); ?>">
                                <div class="pet-card-info">
                                    <h3><?php echo htmlspecialchars($similar_pet['name']); ?></h3>
                                    <p>Порода: <?php echo htmlspecialchars($similar_pet['breed']); ?></p>
                                    <p>Возраст: <?php echo htmlspecialchars($similar_pet['age']); ?></p>
                                    <a href="view_lost_pet.php?id=<?php echo $similar_pet['id']; ?>" 
                                       class="btn btn-primary">Подробнее</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>

    <script>
        // Скрипт для увеличения фотографий при клике
        document.querySelectorAll('.photo-thumbnail img').forEach(img => {
            img.addEventListener('click', function() {
                const modal = document.createElement('div');
                modal.className = 'photo-modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <img src="${this.src}" alt="Увеличенное фото">
                    </div>
                `;
                document.body.appendChild(modal);

                modal.querySelector('.close-modal').onclick = function() {
                    modal.remove();
                };

                modal.onclick = function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                };
            });
        });
    </script>
</body>
</html>