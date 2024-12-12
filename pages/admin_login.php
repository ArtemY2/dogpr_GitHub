<?php
require_once '../config/config.php';

// Удаляем лишний session_start() и проверяем статус сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Также удаляем повторяющуюся проверку администратора
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: lost_pets_admin.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = true;
        header('Location: lost_pets_admin.php');
        exit();
    } else {
        $error = 'Неверные учетные данные';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin Panel | Dog Breed Identifier</title>
    <link rel="stylesheet" href="../static/css/styles.css">
    <link rel="stylesheet" href="../static/css/header.css">
    <link rel="stylesheet" href="../static/css/footer.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0f7ff 0%, #87CEEB 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin-top: 80px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 123, 255, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            transform: translateY(-20px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #32c3f3;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .login-header .icon {
            font-size: 3em;
            color: #4682b4;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #4682b4;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(70, 130, 180, 0.2);
            border-radius: 12px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus {
            border-color: #32c3f3;
            box-shadow: 0 0 0 3px rgba(50, 195, 243, 0.1);
            outline: none;
        }

        .login-btn {
            background: linear-gradient(135deg, #32c3f3, #4682b4);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            width: 100%;
            font-size: 1.1em;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(50, 195, 243, 0.3);
        }

        .error-message {
            background: rgba(255, 99, 71, 0.1);
            border-left: 4px solid #ff6347;
            color: #ff6347;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95em;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.7em;
            }
        }
    </style>
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="icon">🔐</div>
            <h1>Admin Panel Login</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter your username" autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password" autocomplete="current-password">
            </div>

            <button type="submit" class="login-btn">Log In</button>
        </form>
    </div>
</div>

    <script src="../static/js/menu.js"></script>
</body>
</html>