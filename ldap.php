Uncaught (in promise) DOMException: The fetching process for the media resource was aborted by the user agent at the user's request.

function updateVideoSource() {
    const video = document.getElementById('bestiaryVideo');
    // Функция asset() не должна использоваться с полными URL
    const desktopSrc = "https://cdn.moscow-bestiary.ru/videos/bestiary.mp4";
    const mobileSrc = "https://cdn.moscow-bestiary.ru/videos/bestiary-mob.mp4";
    var ratioClass = document.querySelector('.ratio');
    
    // Удаляем существующие <source> элементы
    while (video.firstChild) {
        video.removeChild(video.firstChild);
    }
    
    if (window.innerWidth > 576) {
        const source = document.createElement('source');
        source.dataset.src = desktopSrc;
        source.type = 'video/mp4';
        video.appendChild(source);
        ratioClass.classList.remove('ratio-1x1');
        ratioClass.classList.add('ratio-16x9');
    } else {
        const source = document.createElement('source');
        source.dataset.src = mobileSrc;
        source.type = 'video/mp4';
        video.appendChild(source);
        ratioClass.classList.remove('ratio-16x9');
        ratioClass.classList.add('ratio-1x1');
    }
    
    // Перезагружаем видео, чтобы применить новый источник
    video.load();
    
    // Если видео уже не имеет класса lazy, запускаем его
    if (!video.classList.contains('lazy')) {
        video.play();
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
                    entry.target.play(); // Запускаем видео после загрузки
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

window.addEventListener('resize', updateVideoSource);
document.addEventListener('DOMContentLoaded', function () {
    updateVideoSource(); // Устанавливаем источник при загрузке страницы
    setupLazyLoading();  // Настройка ленивой загрузки
});
