<?php
require_once '../config/config.php';
require_once '../LostPetModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$petModel = new LostPetModel($db);

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['main_photo']) || $_FILES['main_photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Main photo is required for upload');
        }

        $petId = $petModel->addPet($_POST, $_FILES);
        $_SESSION['success'] = 'Pet successfully added and awaiting moderation';
        header('Location: submit_lost_pet.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

$stmt = $db->query("SELECT breed_name FROM breeds ORDER BY breed_name");
$breeds = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Pet | Dog Breed Identifier</title>
    <link rel="stylesheet" href="../static/css/styles.css">
    <link rel="stylesheet" href="../static/css/header.css">
    <link rel="stylesheet" href="../static/css/footer.css">
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h1>Add a New Pet</h1>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form action="submit_lost_pet.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Pet Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="breed">Breed:</label>
                    <select name="breed" id="breed" required>
                        <option value="">Select breed</option>
                        <?php foreach ($breeds as $breed): ?>
                            <option value="<?php echo htmlspecialchars($breed); ?>">
                                <?php echo htmlspecialchars($breed); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="text" id="age" name="age" required>
                </div>

                <div class="form-group">
                    <label for="features">Special Features:</label>
                    <textarea id="features" name="features" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="registration_number">Registration Number:</label>
                    <input type="text" id="registration_number" name="registration_number">
                </div>

                <div class="form-group">
                    <label for="main_photo">Main Photo:</label>
                    <input type="file" id="main_photo" name="main_photo" accept="image/jpeg, image/png" required>
                </div>

                <div class="form-group">
                    <label for="additional_photos">Additional Photos:</label>
                    <input type="file" id="additional_photos" name="additional_photos[]" accept="image/jpeg, image/png" multiple>
                </div>

                <div class="form-group">
                    <label for="owner_phone">Contact Phone:</label>
                    <input type="text" id="owner_phone" name="owner_phone" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add Pet</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
