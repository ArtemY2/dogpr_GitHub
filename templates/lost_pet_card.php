<?php
// Этот файл предполагает, что переменная $pet передана в шаблон
if (!isset($pet)) {
    return;
}
?>
<div class="pet-card">
    <div class="pet-image">
        <img src="<?php echo SITE_URL . $pet['main_photo']; ?>" 
             alt="<?php echo htmlspecialchars($pet['name']); ?>">
    </div>
    <div class="pet-info">
        <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
        <p class="pet-breed">
            <strong>Порода:</strong> 
            <?php echo htmlspecialchars($pet['breed']); ?>
        </p>
        <p class="pet-age">
            <strong>Возраст:</strong> 
            <?php echo htmlspecialchars($pet['age']); ?>
        </p>
        <?php if (!empty($pet['features'])): ?>
            <p class="pet-features">
                <strong>Особенности:</strong> 
                <?php echo htmlspecialchars($pet['features']); ?>
            </p>
        <?php endif; ?>
        
        <div class="pet-contact">
            <a href="tel:<?php echo htmlspecialchars($pet['owner_phone']); ?>" 
               class="btn btn-primary">
                Позвонить
            </a>
            <a href="view_lost_pet.php?id=<?php echo $pet['id']; ?>" 
               class="btn btn-secondary">
                Подробнее
            </a>
        </div>
    </div>
</div>