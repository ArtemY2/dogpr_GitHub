document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    const breedDropdown = document.getElementById('breedDropdown');
    const dropdown = breedDropdown?.nextElementSibling;

    // Обработчик для кнопки мобильного меню
    menuToggle?.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });

    // Обработчик для выпадающего списка пород на мобильных
    breedDropdown?.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            dropdown?.classList.toggle('active');
        }
    });

    // Закрытие меню при клике вне его
    document.addEventListener('click', (e) => {
        if (!navMenu?.contains(e.target) && !menuToggle?.contains(e.target)) {
            navMenu?.classList.remove('active');
            dropdown?.classList.remove('active');
        }
    });

    // Обработка изменения размера окна
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            navMenu?.classList.remove('active');
            dropdown?.classList.remove('active');
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    const breedDropdown = document.getElementById('breedDropdown');
    const dropdown = breedDropdown?.nextElementSibling;

    // Обработчик для кнопки мобильного меню
    menuToggle?.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });

    // Обработчик для выпадающего списка пород на мобильных
    breedDropdown?.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            dropdown?.classList.toggle('active');
        }
    });

    // Закрытие меню при клике вне его
    document.addEventListener('click', (e) => {
        if (!navMenu?.contains(e.target) && !menuToggle?.contains(e.target)) {
            navMenu?.classList.remove('active');
            dropdown?.classList.remove('active');
        }
    });

    // Обработка изменения размера окна
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            navMenu?.classList.remove('active');
            dropdown?.classList.remove('active');
        }
    });

    // Обработчики для вкладок поиска похожих собак
    const initTabs = () => {
        const tabs = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');

        // Инициализация форм при загрузке страницы
        if (window.lostDogsUI) {
            document.getElementById('search-form-container')?.insertAdjacentHTML('beforeend', lostDogsUI.createSearchForm());
            document.getElementById('report-form-container')?.insertAdjacentHTML('beforeend', lostDogsUI.createReportForm());
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Удаляем активный класс у всех вкладок
                tabs.forEach(t => t.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));

                // Активируем выбранную вкладку
                tab.classList.add('active');
                const targetId = `${tab.dataset.tab}-tab`;
                document.getElementById(targetId)?.classList.add('active');

                // Обновляем формы при переключении вкладок
                if (window.lostDogsUI) {
                    if (tab.dataset.tab === 'search') {
                        document.getElementById('search-form-container').innerHTML = lostDogsUI.createSearchForm();
                    } else if (tab.dataset.tab === 'report') {
                        document.getElementById('report-form-container').innerHTML = lostDogsUI.createReportForm();
                    }
                }
            });
        });
    };

    // Инициализируем вкладки
    initTabs();
});