<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $breed = $_POST['breed'];
        $folder = $_POST['folder'];
        
        // Создаем запись в БД для каждого загруженного файла
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            $filename = $_FILES['photos']['name'][$key];
            $filesize = $_FILES['photos']['size'][$key];
            
            // Проверяем размер и тип файла
            if ($filesize > 5000000) {  // 5MB limit
                continue;
            }
            
            $upload_path = "static/breeds/{$folder}/" . $filename;
            
            if (move_uploaded_file($tmp_name, $upload_path)) {
                // Добавляем запись в БД
                $stmt = $db->prepare("INSERT INTO breed_images (breed_name, folder_number, filename) VALUES (?, ?, ?)");
                $stmt->execute([$breed, $folder, $filename]);
            }
        }
        $success = "사진이 성공적으로 업로드되었습니다.";
    } catch (Exception $e) {
        $error = "오류: " . $e->getMessage();
    }
}

// Получаем список пород из конфигурации
$breeds = [
    ['name' => 'Chihuahua', 'folder' => '1'],
    ['name' => 'Yorkshire_terrier', 'folder' => '2'],
    ['name' => 'Maltese', 'folder' => '3'],
    ['name' => 'Jindo', 'folder' => '4'],
    ['name' => 'Border_Collie', 'folder' => '5'],
    ['name' => 'Pomeranian', 'folder' => '6'],
    ['name' => 'Poodle', 'folder' => '7']
];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>품종 사진 업로드</title>
    <link rel="stylesheet" href="static/css/styles.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        .preview-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .progress-bar {
            height: 20px;
            background: #eee;
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
        }
        .progress {
            height: 100%;
            background: #32c3f3;
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>품종 사진 업로드</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="breed">품종 선택:</label>
                <select name="breed" id="breed" required>
                    <?php foreach ($breeds as $breed): ?>
                        <option value="<?php echo $breed['name']; ?>" 
                                data-folder="<?php echo $breed['folder']; ?>">
                            <?php echo $breed['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="folder" id="folder" value="1">

            <div class="form-group">
                <label for="photos">사진 선택 (최대 100장):</label>
                <input type="file" id="photos" name="photos[]" multiple accept="image/*" required>
            </div>

            <div id="preview" class="preview-container"></div>
            
            <div class="progress-bar">
                <div class="progress" id="progress"></div>
            </div>

            <button type="submit" class="btn btn-primary">업로드</button>
        </form>
    </div>

    <script>
        // Обновление скрытого поля folder при выборе породы
        document.getElementById('breed').addEventListener('change', function() {
            const folder = this.options[this.selectedIndex].dataset.folder;
            document.getElementById('folder').value = folder;
        });

        // Предпросмотр выбранных изображений
        document.getElementById('photos').addEventListener('change', function(e) {
            const preview = document.getElementById('preview');
            preview.innerHTML = '';
            
            const files = Array.from(e.target.files);
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML += `
                        <img src="${e.target.result}" class="preview-image" alt="Preview">
                    `;
                }
                reader.readAsDataURL(file);
            });
        });

        // Отображение прогресса загрузки
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const progress = document.getElementById('progress');
            let width = 0;
            const interval = setInterval(() => {
                if (width >= 1000) {
                    clearInterval(interval);
                } else {
                    width++;
                    progress.style.width = width + '%';
                }
            }, 50);
        });
    </script>
</body>
</html>