<?php
require_once 'config/config.php';

class LostPetModel {
    private $db;
    private $upload_dir;

    public function __construct($db) {
        $this->db = $db;
        $this->upload_dir = UPLOAD_DIR;
    }

    public function getStatistics() {
        try {
            $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) as found,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
                FROM lost_pets";
            
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total' => $result['total'] ?? 0,
                'pending' => $result['pending'] ?? 0,
                'found' => $result['found'] ?? 0,
                'approved' => $result['approved'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("Error in getStatistics: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'found' => 0,
                'approved' => 0
            ];
        }
    }

    public function getPendingPets() {
        try {
            $sql = "SELECT lp.*, b.breed_name, 
                           GROUP_CONCAT(pap.file_name) as additional_photos
                    FROM lost_pets lp
                    JOIN breeds b ON lp.breed_id = b.breed_id
                    LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                    WHERE lp.status = 'pending'
                    GROUP BY lp.pet_id
                    ORDER BY lp.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPendingPets: " . $e->getMessage());
            return [];
        }
    }

    public function moderatePet($pet_id, $status, $notes = '') {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE lost_pets SET status = ?, updated_at = NOW() WHERE pet_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $pet_id]);

            if ($result && !empty($notes)) {
                $sql = "INSERT INTO moderation_queue (pet_id, status, moderator_notes) 
                        VALUES (?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$pet_id, $status, $notes]);
            }

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in moderatePet: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPetById($pet_id) {
        try {
            $sql = "SELECT lp.*, b.breed_name,
                          GROUP_CONCAT(pap.file_name) as additional_photos
                   FROM lost_pets lp
                   JOIN breeds b ON lp.breed_id = b.breed_id
                   LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                   WHERE lp.pet_id = ?
                   GROUP BY lp.pet_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pet_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPetById: " . $e->getMessage());
            return null;
        }
    }

    public function getSimilarPets($breed_name, $limit = 4) {
        try {
            $sql = "SELECT lp.*, b.breed_name,
                          GROUP_CONCAT(pap.file_name) as additional_photos
                   FROM lost_pets lp
                   JOIN breeds b ON lp.breed_id = b.breed_id
                   LEFT JOIN pet_additional_photos pap ON lp.pet_id = pap.pet_id
                   WHERE b.breed_name = ? AND lp.status = 'approved'
                   GROUP BY lp.pet_id
                   ORDER BY lp.created_at DESC
                   LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$breed_name, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSimilarPets: " . $e->getMessage());
            return [];
        }
    }

    public function addPet($data, $files) {
        try {
            $this->db->beginTransaction();

            // Получаем breed_id по названию породы
            $stmt = $this->db->prepare("SELECT breed_id FROM breeds WHERE breed_name = ?");
            $stmt->execute([$data['breed']]);
            $breed = $stmt->fetch();
            
            if (!$breed) {
                throw new Exception("Выбранная порода не найдена");
            }

            // Загружаем основное фото
            $main_photo = $this->uploadPhoto($files['main_photo']);
            if (!$main_photo) {
                throw new Exception("Ошибка при загрузке основного фото");
            }

            // Добавляем запись о питомце
            $sql = "INSERT INTO lost_pets (
                breed_id, 
                name, 
                age, 
                registration_number,
                features, 
                owner_phone, 
                main_photo, 
                original_photo_name,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $breed['breed_id'],
                $data['name'],
                $data['age'],
                $data['registration_number'],
                $data['features'],
                $data['owner_phone'],
                $main_photo['stored_name'],
                $main_photo['original_name']
            ]);

            $pet_id = $this->db->lastInsertId();

            // Загружаем дополнительные фото
            if (!empty($files['additional_photos']['name'][0])) {
                foreach ($files['additional_photos']['name'] as $key => $value) {
                    if ($files['additional_photos']['error'][$key] === 0) {
                        $photo = [
                            'name' => $files['additional_photos']['name'][$key],
                            'type' => $files['additional_photos']['type'][$key],
                            'tmp_name' => $files['additional_photos']['tmp_name'][$key],
                            'error' => $files['additional_photos']['error'][$key],
                            'size' => $files['additional_photos']['size'][$key]
                        ];

                        $photo_info = $this->uploadPhoto($photo);
                        if ($photo_info) {
                            $stmt = $this->db->prepare(
                                "INSERT INTO pet_additional_photos (pet_id, file_name, original_name) 
                                 VALUES (?, ?, ?)"
                            );
                            $stmt->execute([
                                $pet_id, 
                                $photo_info['stored_name'],
                                $photo_info['original_name']
                            ]);
                        }
                    }
                }
            }

            $this->db->commit();
            return $pet_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in addPet: " . $e->getMessage());
            throw $e;
        }
    }

    private function uploadPhoto($photo) {
        if (!isset($photo['tmp_name']) || !is_uploaded_file($photo['tmp_name'])) {
            return false;
        }
    
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($photo['type'], $allowed_types)) {
            throw new Exception('Неподдерживаемый тип файла');
        }
    
        // Создаем директорию, если она не существует
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/static/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
    
        // Генерируем уникальное имя файла
        $original_name = basename($photo['name']);
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Полный путь для сохранения файла
        $filepath = $upload_dir . $new_filename;
    
        // Сохраняем изображение
        if (!move_uploaded_file($photo['tmp_name'], $filepath)) {
            throw new Exception('Ошибка при сохранении файла');
        }
    
        return [
            'stored_name' => $new_filename,
            'original_name' => $original_name
        ];
    }
}