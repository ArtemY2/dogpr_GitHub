<?php
// Проверяем статус сессии перед её запуском
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
    <div class="header-content">
        <a href="/" class="logo-link">
            <div class="logo-container">
                <span class="logo-icon">🐕</span>
                <span class="site-title">Dog Breed Identifier</span>
            </div>
        </a>

        <button class="menu-toggle" id="menuToggle">☰</button>

        <nav class="nav-menu" id="navMenu">
            <ul>
                
                
                <li><a href="pages/submit_lost_pet.php" class="nav-link"><span class="icon">📋</span>Report Lost Pet</a></li>
                <li class="has-dropdown">
                    <a href="#" class="nav-link">
                        <span class="icon">🔍</span> Breeds
                        <span class="arrow">▼</span>
                    </a>
                    <ul class="dropdown">
                        <li><a href="/breeds.php?breed=Chihuahua">Chihuahua</a></li>
                        <li><a href="/breeds.php?breed=Yorkshire_terrier">Yorkshire Terrier</a></li>
                        <li><a href="/breeds.php?breed=Maltese">Maltese</a></li>
                        <li><a href="/breeds.php?breed=Jindo">Jindo</a></li>
                        <li><a href="/breeds.php?breed=Border_Collie">Border Collie</a></li>
                        <li><a href="/breeds.php?breed=Pomeranian">Pomeranian</a></li>
                        <li><a href="/breeds.php?breed=Poodle">Poodle</a></li>
                    </ul>
                </li>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                    <li>
                        <a href="/pages/lost_pets_admin.php" class="nav-link admin-panel">
                            <span class="icon">⚙️</span> Admin
                        </a>
                    </li>
                    <li>
                        <a href="/pages/admin_logout.php" class="nav-link admin-logout">
                            <span class="icon">🚪</span> Log-out
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="/pages/admin_login.php" class="nav-link admin-login-link">
                            <span class="icon">🔐</span> Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<script>
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('navMenu').classList.toggle('active');
});

// Закрытие меню при клике вне его
document.addEventListener('click', function(e) {
    const menu = document.getElementById('navMenu');
    const menuToggle = document.getElementById('menuToggle');
    
    if (!menu.contains(e.target) && !menuToggle.contains(e.target)) {
        menu.classList.remove('active');
    }
});
</script>