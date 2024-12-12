<?php
header('Content-Type: application/json');
require_once '../config/config.php';

try {
    $breed = $_GET['breed'] ?? '';
    
    $sql = "SELECT i.image_id, i.filename, i.created_at, lp.name as pet_name, 
            lp.breed, lp.features, lp.age, lp.id as pet_id
            FROM images i
            INNER JOIN lost_pets lp ON i.pet_id = lp.id
            WHERE lp.breed = :breed 
            AND lp.status = 'approved'
            ORDER BY i.created_at DESC
            LIMIT 4";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([':breed' => $breed]);
    $similar_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($similar_images as &$image) {
       
        $image['similarity'] = rand(75, 95) / 100;
    }

    echo json_encode([
        'success' => true,
        'data' => $similar_images
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}