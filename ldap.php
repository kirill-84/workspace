function updateVideoSource() {
    const video = document.getElementById('bestiaryVideo');
    const desktopSrc = "https://cdn.moscow-bestiary.ru/videos/bestiary.mp4";
    const mobileSrc = "https://cdn.moscow-bestiary.ru/videos/bestiary-mob.mp4";
    var ratioClass = document.querySelector('.ratio');
    
    // Определяем текущий источник
    let currentSrc = '';
    const sources = video.getElementsByTagName('source');
    if (sources.length > 0) {
        currentSrc = sources[0].dataset.src || sources[0].src;
    }
    
    // Определяем новый источник на основе ширины окна
    const newSrc = window.innerWidth > 576 ? desktopSrc : mobileSrc;
    
    // Если источник не изменился, не перезагружаем видео
    if (currentSrc === newSrc) {
        return;
    }
    
    // Останавливаем текущее воспроизведение
    video.pause();
    
    // Удаляем существующие <source> элементы
    while (video.firstChild) {
        video.removeChild(video.firstChild);
    }
    
    // Создаем новый элемент источника
    const source = document.createElement('source');
    source.dataset.src = newSrc;
    source.type = 'video/mp4';
    video.appendChild(source);
    
    // Обновляем соотношение сторон
    if (window.innerWidth > 576) {
        ratioClass.classList.remove('ratio-1x1');
        ratioClass.classList.add('ratio-16x9');
    } else {
        ratioClass.classList.remove('ratio-16x9');
        ratioClass.classList.add('ratio-1x1');
    }
    
    // Если видео уже не ленивое, устанавливаем src непосредственно
    if (!video.classList.contains('lazy')) {
        source.src = newSrc;
        video.load();
        // Добавляем обработчик события загрузки
        const playPromise = video.play();
        // Обрабатываем возможное отклонение promise
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.log("Автовоспроизведение предотвращено:", error);
                // Не делаем ничего - пусть пользователь запустит видео сам
            });
        }
    }
}

function setupLazyLoading() {
    var lazyVideos = [].slice.call(document.querySelectorAll("video.lazy"));
    if ("IntersectionObserver" in window) {
        var lazyVideoObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    for (var source of entry.target.children) {
                        if (typeof source.tagName === "string" && source.tagName === "SOURCE") {
                            source.src = source.dataset.src;
                        }
                    }
                    entry.target.load();
                    const playPromise = entry.target.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(error => {
                            console.log("Автовоспроизведение предотвращено:", error);
                        });
                    }
                    entry.target.classList.remove("lazy");
                    lazyVideoObserver.unobserve(entry.target);
                }
            });
        });
        lazyVideos.forEach(function (lazyVideo) {
            lazyVideoObserver.observe(lazyVideo);
        });
    }
}

// Добавляем функцию debounce для предотвращения слишком частых вызовов
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Используем debounce для функции обновления источника
const debouncedUpdateVideoSource = debounce(updateVideoSource, 250);

// Привязываем обработчик события изменения размера с debounce
window.addEventListener('resize', debouncedUpdateVideoSource);

document.addEventListener('DOMContentLoaded', function () {
    updateVideoSource(); // Устанавливаем источник при загрузке страницы
    setupLazyLoading();  // Настройка ленивой загрузки
});